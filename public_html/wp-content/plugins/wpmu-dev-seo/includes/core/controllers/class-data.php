<?php
/**
 * Controls Data & Settings.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Modules\Advanced\Redirects\Database_Table;
use SmartCrawl\Multisite\Subsite_Process_Runner;
use SmartCrawl\Services\Lighthouse;
use SmartCrawl\Services\Service;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * Data Controller
 */
class Data extends Controller {

	use Singleton;

	/**
	 * Service instance.
	 *
	 * @var Service
	 */
	private $site_service;

	const PROGRESS_OPTION_ID = 'wds-multisite-data-reset-progress';

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->site_service = Service::get( Service::SERVICE_SITE );
	}

	/**
	 * Checks if the current user has permission to manage plugin settings.
	 *
	 * @return bool True if the user has permission, false otherwise.
	 */
	public function user_has_permission() {
		if ( is_multisite() ) {
			return current_user_can( 'manage_network_options' );
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Initializes action hooks.
	 */
	protected function init() {
		add_action( 'wp_ajax_wds_data_reset', array( $this, 'ajax_reset' ) );
		add_action( 'wp_ajax_wds_multisite_data_reset', array( $this, 'ajax_reset_multisite' ) );
	}

	/**
	 * Resets all multisite settings and data.
	 *
	 * @return array An array containing total sites, completed sites, and progress message.
	 */
	public function reset_multisite() {
		$runner          = new Subsite_Process_Runner(
			self::PROGRESS_OPTION_ID,
			array( $this, 'reset' )
		);
		$total_sites     = $runner->get_total_site_count();
		$next_site_id    = $runner->get_next_site_to_process();
		$processed_sites = $runner->run();
		$finished        = $total_sites === $processed_sites;

		return array(
			'total_sites'      => $total_sites,
			'completed_sites'  => $processed_sites,
			'progress_message' => $this->get_progress_message( $next_site_id, $finished ),
		);
	}

	/**
	 * Retrieves the progress message for the next site in the reset process.
	 *
	 * @param int  $next_site_id The ID of the next site to reset.
	 * @param bool $finished Whether the reset process has finished.
	 *
	 * @return string The progress message.
	 */
	private function get_progress_message( $next_site_id, $finished ) {
		if ( $finished ) {
			// Finished processing, we don't have a next site.
			return esc_html__( 'Finishing up', 'wds' );
		}

		if ( empty( $next_site_id ) ) {
			return '';
		}

		$next_site = get_site( $next_site_id );

		return empty( $next_site->blogname )
			? ''
			: sprintf(
			/* translators: 1: Open strong tag, 2: Site name, 3: Close strong tag. */
				esc_html__( 'Resetting %1$s%2$s%3$s', 'wds' ),
				'<strong>',
				$next_site->blogname,
				'</strong>'
			);
	}

	/**
	 * Ajax handler to reset multisite data.
	 *
	 * This function is responsible for resetting the multisite data
	 * only if the user has the required permission.
	 *
	 * @return void
	 */
	public function ajax_reset_multisite() {
		if ( ! $this->user_has_permission() ) {
			wp_send_json_error();
		}

		check_admin_referer( 'wds-multisite-data-reset-nonce', '_wds_nonce' );

		wp_send_json_success( $this->reset_multisite() );
	}

	/**
	 * Ajax handler to reset data.
	 *
	 * Resets the data if the user has permission.
	 *
	 * @return void
	 */
	public function ajax_reset() {
		if ( ! $this->user_has_permission() ) {
			wp_send_json_error();
		}

		check_admin_referer( 'wds-data-reset-nonce', '_wds_nonce' );

		$this->reset();

		wp_send_json_success();
	}

	/**
	 * Resets data and settings based on user's data retention options
	 */
	public function uninstall() {
		$options       = Settings::get_options();
		$keep_settings = (bool) \smartcrawl_get_array_value( $options, 'keep_settings_on_uninstall' );
		$keep_data     = (bool) \smartcrawl_get_array_value( $options, 'keep_data_on_uninstall' );

		if ( ! $keep_settings ) {
			$this->reset_settings();
		}

		if ( ! $keep_data ) {
			$this->reset_data();
		}

		wp_cache_flush();

		/**
		 * Action hook to run after plugin reset.
		 *
		 * @since 3.7.0
		 *
		 * @param array $options Old options.
		 * @param bool $keep_settings Determine whether to save current settings for next time, or reset them.
		 */
		do_action( 'smartcrawl_after_uninstall', $options, $keep_settings );
	}

	/**
	 * Resets all settings and data.
	 */
	public function reset() {
		$old_options = Settings::get_options();

		$this->reset_settings();
		$this->reset_data();

		wp_cache_flush();

		\smartcrawl_activate_all_blog_tabs();

		/**
		 * Action hook to run after plugin reset.
		 *
		 * @since 3.7.0
		 *
		 * @param array $old_options Old options.
		 */
		do_action( 'smartcrawl_after_reset', $old_options );

		return true;
	}

	/**
	 * Settings include options, post meta and taxonomy meta.
	 */
	public function reset_settings() {
		$old_options = Settings::get_options();

		$this->remove_options();

		if ( is_multisite() && is_main_site() ) {
			$this->remove_site_options();
		}

		$this->remove_post_meta();
		$this->remove_user_meta();

		/**
		 * Action hook to run after plugin settings reset.
		 *
		 * @since 3.7.0
		 *
		 * @param array $old_options Old options.
		 */
		do_action( 'smartcrawl_after_reset_settings', $old_options );
	}

	/**
	 * Resets data including audit/crawl results, redirects and all files stored by the plugin.
	 */
	public function reset_data() {
		$this->remove_service_results();

		if ( is_multisite() && is_main_site() ) {
			$this->remove_site_service_results();
		}

		$this->remove_files();
		Database_Table::get()->drop_table();

		// Clears Lighthouse report.
		Service::get( Service::SERVICE_LIGHTHOUSE )->clear_last_report();

		/**
		 * Action hook to run after plugin data reset.
		 *
		 * @since 3.7.0
		 */
		do_action( 'smartcrawl_after_reset_data' );
	}

	/**
	 * Removes site options from the database in multisite.
	 *
	 * @return int|false Number of rows affected or false on query failure.
	 */
	private function remove_site_options() {
		global $wpdb;

		$service_model_key = $this->get_service_model_key();

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_key NOT LIKE %s AND meta_key != %s",
				'wds%',
				$service_model_key,
				Lighthouse::OPTION_ID_LAST_REPORT
			)
		);
	}

	/**
	 * Removes options from the database.
	 *
	 * @return int|false The number of rows affected or false on failure.
	 */
	private function remove_options() {
		global $wpdb;
		$service_model_key = $this->get_service_model_key();

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s AND option_name != %s",
				'wds%',
				$service_model_key,
				Lighthouse::OPTION_ID_LAST_REPORT
			)
		);
	}

	/**
	 * Removes post meta data.
	 *
	 * Removes post meta data with meta keys that start with '_wds'.
	 *
	 * @return int|false The number of rows affected or false on failure.
	 */
	private function remove_post_meta() {
		global $wpdb;

		return $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wds%'" );
	}

	/**
	 * Removes user meta data from the database.
	 *
	 * This function will delete user meta data that has a meta key starting with 'wds_' from the database.
	 *
	 * @return int|false On success, the number of rows affected. False on failure.
	 */
	private function remove_user_meta() {
		global $wpdb;

		return $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wds_%'" );
	}

	/**
	 * Retrieves the service model key.
	 *
	 * @return string The service model key.
	 */
	private function get_service_model_key() {
		return $this->site_service->get_filter( '%' );
	}

	/**
	 * Removes the site service results.
	 *
	 * @return void
	 */
	private function remove_site_service_results() {
		global $wpdb;

		$key = $this->get_service_model_key();

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", "$key" ) );
	}

	/**
	 * Removes service results from the options table.
	 *
	 * @return void
	 */
	private function remove_service_results() {
		global $wpdb;

		$key = $this->get_service_model_key();

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", "$key" ) );
	}

	/**
	 * Removes SmartCrawl's upload directory recursively.
	 */
	private function remove_files() {
		$file_system = $this->fs_direct();
		$file_system->rmdir( \smartcrawl_uploads_dir(), true );
	}

	/**
	 * Retrieves the WordPress filesystem object.
	 *
	 * @return \WP_Filesystem_Direct The WordPress filesystem object.
	 */
	private function fs_direct() {
		if ( ! class_exists( '\WP_Filesystem_Direct', false ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}

		return new \WP_Filesystem_Direct( null );
	}
}