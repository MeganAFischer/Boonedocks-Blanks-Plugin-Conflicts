<?php
/**
 * Controller class for handling sitemap-related services in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Controllers;

/**
 * Class Controller
 *
 * Handles sitemap-related services.
 */
class Controller extends Controllers\Controller {

	use Singleton;

	/**
	 * Determines if the sitemap should run.
	 *
	 * @return bool True if the sitemap should run, false otherwise.
	 */
	public function should_run() {
		return Settings::get_setting( 'sitemap' )
			&& Admin_Settings::is_tab_allowed( Settings::TAB_SITEMAP );
	}

	/**
	 * Initializes the controller.
	 *
	 * @return void
	 */
	protected function init() {
		add_action(
			'wp_ajax_wds_update_sitemap',
			array(
				$this,
				'json_update_sitemap',
			)
		);
		add_action(
			'wp_ajax_wds_update_engines',
			array(
				$this,
				'json_update_engines',
			)
		);

		add_action(
			'wp_ajax_wds-manually-update-engines',
			array(
				$this,
				'json_manually_update_engines',
			)
		);
		add_action(
			'wp_ajax_wds-manually-update-sitemap',
			array(
				$this,
				'json_manually_update_sitemap',
			)
		);
		add_action(
			'wp_ajax_wds-deactivate-sitemap-module',
			array(
				$this,
				'json_deactivate_sitemap_module',
			)
		);
		add_action(
			'wp_ajax_wds-override-native',
			array(
				$this,
				'json_override_native',
			)
		);

		add_action(
			'admin_init',
			array(
				$this,
				'prime_cache_on_sitemap_settings_page_load',
			)
		);

		add_action(
			'update_option_wds_sitemap_options',
			array(
				$this,
				'invalidate_sitemap_cache',
			)
		);

		add_action(
			'wds_plugin_update',
			array(
				$this,
				'invalidate_sitemap_cache_on_plugin_update',
			)
		);

		// Upgrade sitemap regeneration settings.
		add_action( 'wds_plugin_update', array( $this, 'upgrade_sitemap' ), 10, 2 );

		if ( Utils::auto_regeneration_enabled() ) {
			add_action( 'save_post', array( $this, 'handle_post_save' ) );
			add_action( 'delete_post', array( $this, 'handle_post_delete' ) );
			add_action(
				'wp_update_term_data',
				array(
					$this,
					'handle_term_slug_update',
				),
				10,
				3
			);
			add_action(
				'pre_delete_term',
				array(
					$this,
					'handle_term_deletion',
				),
				10,
				2
			);
		}
	}

	/**
	 * Invalidates the sitemap cache on plugin update.
	 *
	 * @return void
	 */
	public function invalidate_sitemap_cache_on_plugin_update() {
		$this->invalidate_sitemap_cache();
	}

	/**
	 * Upgrade sitemap settings.
	 *
	 * @since 3.5.0
	 *
	 * @param string $new_version New version.
	 * @param string $old_version Old version.
	 *
	 * @return void
	 */
	public function upgrade_sitemap( $new_version, $old_version ) {
		// Upgrade regenerate settings.
		if ( version_compare( $new_version, '3.5.0', '>=' ) ) {
			$available_methods = array( 'auto', 'manual', 'scheduled' );

			$method = Utils::get_sitemap_option( 'sitemap-disable-automatic-regeneration' );
			$method = ! empty( $method ) && in_array( $method, $available_methods, true ) ? $method : 'manual';
			// Set new value.
			Utils::set_sitemap_option( 'sitemap-disable-automatic-regeneration', $method );
		}

		// Clear cache if updating only from 3.9.0.
		if ( version_compare( $old_version, '3.9.0', '=' ) ) {
			$this->invalidate_sitemap_cache();
		}
	}

	/**
	 * Primes the cache on sitemap settings page load.
	 *
	 * @return void
	 */
	public function prime_cache_on_sitemap_settings_page_load() {
		global $plugin_page;

		$is_sitemap_page = isset( $plugin_page ) && Settings::TAB_SITEMAP === $plugin_page;
		if ( ! $is_sitemap_page ) {
			return;
		}

		if ( Cache::get()->is_index_cached() ) {
			return;
		}

		Utils::prime_cache( false );
	}

	/**
	 * Manually updates the search engines.
	 *
	 * @return void
	 */
	public function json_manually_update_engines() {
		Utils::notify_engines( true );
	}

	/**
	 * Manually updates the sitemap.
	 *
	 * @return void
	 */
	public function json_manually_update_sitemap() {
		$this->invalidate_sitemap_cache();
	}

	/**
	 * Deactivates the sitemap module.
	 *
	 * @return void
	 */
	public function json_deactivate_sitemap_module() {
		$data = $this->get_request_data();
		if ( empty( $data ) ) {
			wp_send_json_error();

			return;
		}

		Settings::deactivate_component( 'sitemap' );
		wp_send_json_success();
	}

	/**
	 * Overrides the native sitemap.
	 *
	 * @return void
	 */
	public function json_override_native() {
		$data     = $this->get_request_data();
		$override = \smartcrawl_get_array_value( $data, 'override' );

		if ( is_null( $override ) ) {
			wp_send_json_error();

			return;
		}

		Utils::set_sitemap_option( 'override-native', (bool) $override );
		wp_send_json_success();
	}

	/**
	 * Invalidates sitemap cache.
	 *
	 * This is so the next sitemap request re-generates the caches.
	 * Serves as performance improvement for post-based action listeners.
	 *
	 * On setups with large posts table, fully regenerating sitemap can take a
	 * while. So instead, we just invalidate the cache and potentially ping the
	 * search engines to notify them about the change.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function handle_post_save( $post_id ) {
		$post = get_post( $post_id );
		if (
			! Utils::is_post_type_included( $post->post_type )
			|| wp_is_post_autosave( $post )
			|| wp_is_post_revision( $post )
		) {
			return;
		}

		$this->invalidate_sitemap_cache();
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			// The above if condition is necessary because save_post is called twice by gutenberg: https://github.com/WordPress/gutenberg/issues/12903
			// We don't want the search engines to be notified of sitemap changes twice, so as a workaround we are going to invalidate sitemap cache both times
			// but only prime the cache for gutenberg (and other rests requests).
			Utils::prime_cache( false );
		}
	}

	/**
	 * Handles post deletion.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function handle_post_delete( $post_id ) {
		if ( ! Utils::is_post_included( get_post( $post_id ) ) ) {
			return;
		}

		$this->invalidate_sitemap_cache();
		Utils::prime_cache( false );
	}

	/**
	 * Handles term slug update.
	 *
	 * @param array  $data     The term data.
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy.
	 *
	 * @return mixed The modified term data.
	 */
	public function handle_term_slug_update( $data, $term_id, $taxonomy ) {
		$term              = get_term( $term_id, $taxonomy );
		$new_slug          = \smartcrawl_get_array_value( $data, 'slug' );
		$taxonomy_included = Utils::is_taxonomy_included( $taxonomy );

		if ( $taxonomy_included && ! empty( $term->count ) && $new_slug !== $term->slug ) {
			$this->invalidate_sitemap_cache();
			Utils::prime_cache( false );
		}

		return $data;
	}

	/**
	 * Handles term deletion.
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy.
	 *
	 * @return void
	 */
	public function handle_term_deletion( $term_id, $taxonomy ) {
		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) ) {
			return;
		}

		if ( ! Utils::is_term_included( $term ) ) {
			return;
		}

		$this->invalidate_sitemap_cache();
		Utils::prime_cache( false );
	}

	/**
	 * Updates the sitemap via AJAX.
	 *
	 * @return void
	 */
	public function json_update_sitemap() {
		$this->invalidate_sitemap_cache();
		Utils::prime_cache( true );
		die( 1 );
	}

	/**
	 * Updates the search engines via AJAX.
	 *
	 * @return void
	 */
	public function json_update_engines() {
		Utils::notify_engines( 1 );
		die( 1 );
	}

	/**
	 * Retrieves request data.
	 *
	 * @return array|mixed The request data.
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}

	/**
	 * Invalidates the sitemap cache.
	 *
	 * @return void
	 */
	public function invalidate_sitemap_cache() {
		Cache::get()->invalidate();
	}
}