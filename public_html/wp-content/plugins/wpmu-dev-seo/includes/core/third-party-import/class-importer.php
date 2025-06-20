<?php
/**
 * This file contains the Importer class for handling the import of third-party SEO settings.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Third_Party_Import;

use SmartCrawl\Settings;
use SmartCrawl\Multisite\Subsite_Process_Runner;

/**
 * Class Importer
 *
 * Abstract class for handling the import of third-party SEO settings.
 */
abstract class Importer {

	/**
	 * Status of the import process.
	 *
	 * @var mixed
	 */
	private $status = null;

	/**
	 * Check if data exists for import.
	 *
	 * @return bool True if data exists, false otherwise.
	 */
	abstract public function data_exists();

	/**
	 * Get source plugins.
	 *
	 * @return array The source plugins.
	 */
	abstract protected function get_source_plugins();

	/**
	 * Get the active source plugin.
	 *
	 * @return bool|string The active source plugin or false if none.
	 */
	public function get_active_source_plugin() {
		$source_plugin = $this->get_source_plugins();
		foreach ( $source_plugin as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				return $plugin;
			}
		}

		return false;
	}

	/**
	 * Get the deactivation link for the plugin.
	 *
	 * @return string The deactivation link.
	 */
	public function get_deactivation_link() {
		$active_plugin = $this->get_active_source_plugin();
		if ( ! $active_plugin ) {
			return false;
		}

		return wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $active_plugin . '&amp;plugin_status=all', 'deactivate-plugin_' . $active_plugin );
	}

	/**
	 * Import settings for all sites in a network.
	 *
	 * @param array $options The options to use for the import.
	 *
	 * @return void
	 */
	public function import_for_all_sites( $options = array() ) {
		$runner          = new Subsite_Process_Runner(
			$this->get_next_network_site_option_id(),
			array( $this, 'import' )
		);
		$total_sites     = $runner->get_total_site_count();
		$processed_sites = $runner->run( $options );
		$this->update_site_status( $total_sites, $processed_sites );
		$this->enable_settings_page_on_subsites();
	}

	/**
	 * Get the status of the import process.
	 *
	 * @return mixed The status of the import process.
	 */
	public function get_status() {
		return empty( $this->status ) ? array() : $this->status;
	}

	/**
	 * Set the status of the import process.
	 *
	 * @param mixed $status The status to set.
	 *
	 * @return void
	 */
	protected function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * Update the status of the import process.
	 *
	 * @param mixed $status The status to update.
	 *
	 * @return void
	 */
	protected function update_status( $status ) {
		$this->status = wp_parse_args( $status, $this->get_status() );
	}

	/**
	 * Get the next network site option ID.
	 *
	 * @return string The next network site option ID.
	 */
	abstract protected function get_next_network_site_option_id();

	/**
	 * Import settings.
	 *
	 * @param array $options The options to use for the import.
	 *
	 * @return bool
	 */
	public function import( $options = array() ) {
		$options          = wp_parse_args(
			$options,
			array(
				'import-options'          => true,
				'import-term-meta'        => true,
				'import-post-meta'        => true,
				'force-restart'           => false,
				'keep-existing-post-meta' => false,
			)
		);
		$import_options   = (bool) \smartcrawl_get_array_value( $options, 'import-options' );
		$import_term_meta = (bool) \smartcrawl_get_array_value( $options, 'import-term-meta' );
		$import_post_meta = (bool) \smartcrawl_get_array_value( $options, 'import-post-meta' );
		$force_restart    = (bool) \smartcrawl_get_array_value( $options, 'force-restart' );
		$keep_post_meta   = (bool) \smartcrawl_get_array_value( $options, 'keep-existing-post-meta' );

		if ( ! $this->is_import_in_progress() || $force_restart ) {
			$this->set_import_flag();
			if ( $import_options ) {
				$this->remove_existing_wds_options();
				$this->import_options();
			}
			if ( $import_term_meta ) {
				$this->remove_existing_wds_taxonomy_meta();
				$this->import_taxonomy_meta();
			}
			if ( $import_post_meta && ! $keep_post_meta ) {
				$this->remove_existing_wds_post_meta();
			}
		}

		// If post meta doesn't need to be imported then we're done.
		$complete = $import_post_meta ? $this->import_post_meta() : true;
		if ( $complete ) {
			$this->reset_import_flag();
		}

		return $complete;
	}

	/**
	 * Check if an import is in progress.
	 *
	 * @return bool True if an import is in progress, false otherwise.
	 */
	public function is_import_in_progress() {
		return (bool) get_option( $this->get_import_in_progress_option_id() );
	}

	/**
	 * Get the import in progress option ID.
	 *
	 * @return string The import in progress option ID.
	 */
	abstract protected function get_import_in_progress_option_id();

	/**
	 * Set the import flag.
	 *
	 * @return void
	 */
	private function set_import_flag() {
		update_option( $this->get_import_in_progress_option_id(), true );
	}

	/**
	 * Remove existing WDS options.
	 *
	 * @return void
	 */
	private function remove_existing_wds_options() {
		Settings::reset_options();

		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%wds-sitemap%'" );
	}

	/**
	 * Remove existing WDS taxonomy meta.
	 *
	 * @return void
	 */
	private function remove_existing_wds_taxonomy_meta() {
		delete_option( 'wds_taxonomy_meta' );
	}

	/**
	 * Import options.
	 *
	 * @return void
	 */
	abstract public function import_options();

	/**
	 * Import taxonomy meta.
	 *
	 * @return void
	 */
	abstract public function import_taxonomy_meta();

	/**
	 * Remove existing WDS post meta.
	 *
	 * @return void
	 */
	private function remove_existing_wds_post_meta() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wds_%'" );
	}

	/**
	 * Import post meta.
	 *
	 * @return void
	 */
	abstract public function import_post_meta();

	/**
	 * Reset the import flag.
	 *
	 * @return void
	 */
	private function reset_import_flag() {
		delete_option( $this->get_import_in_progress_option_id() );
	}

	/**
	 * Enable settings page on subsites.
	 *
	 * @return void
	 */
	private function enable_settings_page_on_subsites() {
		$blog_tabs                 = get_site_option( 'wds_blog_tabs', array() );
		$blog_tabs['wds_settings'] = true;
		update_site_option( 'wds_blog_tabs', $blog_tabs );
	}

	/**
	 * Check if a network import is in progress.
	 *
	 * @return bool True if a network import is in progress, false otherwise.
	 */
	public function is_network_import_in_progress() {
		return get_site_option( $this->get_next_network_site_option_id(), false ) !== false;
	}

	/**
	 * Get posts with source metas.
	 *
	 * @param string $prefix The meta prefix.
	 *
	 * @return int[] The posts with source metas.
	 */
	protected function get_posts_with_source_metas( $prefix ) {
		global $wpdb;
		$posts_with_target_meta = implode( ',', $this->get_posts_with_target_metas() );
		$not_in                 = $posts_with_target_meta ? $posts_with_target_meta : '-1';
		$meta_query             = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE '{$prefix}%' AND post_id NOT IN ({$not_in}) GROUP BY post_id";

		return $wpdb->get_col( $meta_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get posts with target metas.
	 *
	 * @return int[] The posts with target metas.
	 */
	protected function get_posts_with_target_metas() {
		global $wpdb;

		return $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wds_%' AND meta_key NOT IN ('_wds_analysis','_wds_readability') GROUP BY post_id" );
	}

	/**
	 * Load a mapping file.
	 *
	 * @param string $file The file to load.
	 *
	 * @return array The loaded mappings.
	 */
	protected function load_mapping_file( $file ) {
		return include \SMARTCRAWL_PLUGIN_DIR . 'core/resources/' . $file;
	}

	/**
	 * Save options.
	 *
	 * @param array $options The options to save.
	 *
	 * @return void
	 */
	protected function save_options( $options ) {
		foreach ( $options as $option_key => $values ) {
			remove_all_filters( 'sanitize_option_' . $option_key );
			update_option( $option_key, $values );
		}
	}

	/**
	 * Try custom handlers for specific options.
	 *
	 * @param string $source_key The key of the option.
	 * @param mixed  $source_value The value of the option.
	 * @param array  $target_options The target options.
	 *
	 * @return array True if a custom handler was used, false otherwise.
	 */
	protected function try_custom_handlers( $source_key, $source_value, $target_options ) {
		$custom_handler = null;
		foreach ( $this->expand_mappings( $this->get_custom_handlers() ) as $pattern => $callback ) {
			if ( preg_match( '#' . $pattern . '#', $source_key ) ) {
				$custom_handler = $callback;
			}
		}

		if ( ! $custom_handler ) {
			return $target_options;
		}

		$target_options = call_user_func_array(
			array( $this, $custom_handler ),
			array( $source_key, $source_value, $target_options )
		);

		return $target_options;
	}

	/**
	 * Expand mappings.
	 *
	 * @param array $mappings The mappings to expand.
	 *
	 * @return array The expanded mappings.
	 */
	protected function expand_mappings( $mappings ) {
		$post_types = $this->get_post_types();
		$taxonomies = $this->get_taxonomies();

		foreach ( $mappings as $source_key => $target_key ) {
			if ( ! $this->is_custom_type_option( $source_key ) ) {
				continue;
			}

			unset( $mappings[ $source_key ] );

			if ( $this->is_post_type_option( $source_key ) ) {
				foreach ( $post_types as $post_type ) {
					$new_source_key              = str_replace( 'POSTTYPE', $post_type, $source_key );
					$new_target_key              = false === $target_key ? false : str_replace( 'POSTTYPE', $post_type, $target_key );
					$mappings[ $new_source_key ] = $new_target_key;
				}
			} elseif ( $this->is_taxonomy_option( $source_key ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					$new_source_key              = str_replace( 'TAXONOMY', $taxonomy, $source_key );
					$new_target_key              = false === $target_key ? false : str_replace( 'TAXONOMY', $taxonomy, $target_key );
					$mappings[ $new_source_key ] = $new_target_key;
				}
			}
		}

		return $mappings;
	}

	/**
	 * Get post types.
	 *
	 * @return array The post types.
	 */
	protected function get_post_types() {
		return get_post_types( array( 'public' => true ) );
	}

	/**
	 * Get taxonomies.
	 *
	 * @return array
	 */
	protected function get_taxonomies() {
		return array_merge(
			array( 'post_tag', 'category' ),
			get_taxonomies(
				array(
					'_builtin' => false,
					'public'   => true,
				)
			)
		);
	}

	/**
	 * Check if an option is a custom type option.
	 *
	 * @param string $key The option to check.
	 *
	 * @return bool True if the option is a custom type option, false otherwise.
	 */
	private function is_custom_type_option( $key ) {
		return $this->is_post_type_option( $key ) || $this->is_taxonomy_option( $key );
	}

	/**
	 * Check if an option is a post type option.
	 *
	 * @param string $key The option to check.
	 *
	 * @return bool True if the option is a post type option, false otherwise.
	 */
	private function is_post_type_option( $key ) {
		return $key && strpos( $key, 'POSTTYPE' ) !== false;
	}

	/**
	 * Check if an option is a taxonomy option.
	 *
	 * @param string $key The option to check.
	 *
	 * @return bool True if the option is a taxonomy option, false otherwise.
	 */
	private function is_taxonomy_option( $key ) {
		return $key && strpos( $key, 'TAXONOMY' ) !== false;
	}

	/**
	 * Get custom handlers.
	 *
	 * @return array The custom handlers.
	 */
	protected function get_custom_handlers() {
		return array();
	}

	/**
	 * Pre-process a value.
	 *
	 * @param string $target_key The key of the value.
	 * @param mixed  $source_value The value to pre-process.
	 *
	 * @return mixed The pre-processed value.
	 */
	protected function pre_process_value( $target_key, $source_value ) {
		if ( $this->requires_array_wrapping( $target_key ) ) {
			return array( $source_value );
		}

		if ( $this->requires_boolean_casting( $target_key ) ) {
			return $this->is_value_truthy( $source_value );
		}

		if ( $this->requires_boolean_inversion( $target_key ) ) {
			return ! $this->is_value_truthy( $source_value );
		}

		$all_arguments = func_get_args();

		return $this->try_custom_pre_processor( $target_key, $source_value, $all_arguments );
	}

	/**
	 * Check if a value requires array wrapping.
	 *
	 * @param string $key The key of the value.
	 *
	 * @return bool True if the value requires array wrapping, false otherwise.
	 */
	private function requires_array_wrapping( $key ) {
		return $key && strpos( $key, '[]' ) !== false;
	}

	/**
	 * Check if a value requires boolean casting.
	 *
	 * @param string $key The key of the value.
	 *
	 * @return bool True if the value requires boolean casting, false otherwise.
	 */
	private function requires_boolean_casting( $key ) {
		return $key && strpos( $key, '!!' ) !== false;
	}

	/**
	 * Check if a value is truthy.
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return bool True if the value is truthy, false otherwise.
	 */
	private function is_value_truthy( $value ) {
		return 'on' === $value || '1' === $value || true === $value || ( is_int( $value ) && $value > 0 );
	}

	/**
	 * Check if a value requires boolean inversion.
	 *
	 * @param string $key The key of the value.
	 *
	 * @return bool True if the value requires boolean inversion, false otherwise.
	 */
	private function requires_boolean_inversion( $key ) {
		return $key && strpos( $key, '!' ) !== false;
	}

	/**
	 * Try custom pre-processor for a value.
	 *
	 * @param string $target_key The key of the value.
	 * @param mixed  $source_value The value to pre-process.
	 * @param array  $all_arguments All arguments passed to the pre-processor.
	 *
	 * @return bool True if a custom pre-processor was used, false otherwise.
	 */
	private function try_custom_pre_processor( $target_key, $source_value, $all_arguments ) {
		$pre_processor = null;
		foreach ( $this->get_pre_processors() as $pattern => $callback ) {
			if ( ! is_null( $target_key ) && preg_match( '#' . $pattern . '#', $target_key ) ) {
				$pre_processor = $callback;
			}
		}

		if ( ! $pre_processor ) {
			return $source_value;
		}

		return call_user_func_array(
			array( $this, $pre_processor ),
			$all_arguments
		);
	}

	/**
	 * Get pre-processors.
	 *
	 * @return array The pre-processors.
	 */
	protected function get_pre_processors() {
		return array();
	}

	/**
	 * Pre-process a key.
	 *
	 * @param string $key The key to pre-process.
	 *
	 * @return string The pre-processed key.
	 */
	protected function pre_process_key( $key ) {
		if ( $this->requires_array_wrapping( $key ) ) {
			$key = $this->remove_array_wrapping_indicators( $key );
		}

		if ( $this->requires_boolean_casting( $key ) ) {
			$key = $this->remove_boolean_casting_indicators( $key );
		}

		if ( $this->requires_boolean_inversion( $key ) ) {
			$key = $this->remove_boolean_inversion_indicators( $key );
		}

		if ( $this->is_multipart_key( $key ) ) {
			$key = $this->get_key_parts( $key );
		}

		return $key;
	}

	/**
	 * Remove array wrapping indicators from a key.
	 *
	 * @param string $key The key to process.
	 *
	 * @return string The processed key.
	 */
	private function remove_array_wrapping_indicators( $key ) {
		$parts = explode( '[]', $key );

		return empty( $parts[0] ) ? '' : $parts[0];
	}

	/**
	 * Remove boolean casting indicators from a key.
	 *
	 * @param string $key The key to process.
	 *
	 * @return string The processed key.
	 */
	private function remove_boolean_casting_indicators( $key ) {
		$parts = explode( '!!', $key );

		return empty( $parts[1] ) ? '' : $parts[1];
	}

	/**
	 * Remove boolean inversion indicators from a key.
	 *
	 * @param string $key The key to process.
	 *
	 * @return string The processed key.
	 */
	private function remove_boolean_inversion_indicators( $key ) {
		$parts = explode( '!', $key );

		return empty( $parts[1] ) ? '' : $parts[1];
	}

	/**
	 * Check if a key is multipart.
	 *
	 * @param string $key The key to check.
	 *
	 * @return bool True if the key is multipart, false otherwise.
	 */
	private function is_multipart_key( $key ) {
		return $key && strpos( $key, '/' ) !== false;
	}

	/**
	 * Get parts of a multipart key.
	 *
	 * @param string $key The key to process.
	 *
	 * @return array The parts of the key.
	 */
	private function get_key_parts( $key ) {
		return explode( '/', $key );
	}

	/**
	 * Get the target key for a source key.
	 *
	 * @param int $total_sites     Total sites.
	 * @param int $completed_sites Completed sites.
	 *
	 * @return void
	 */
	private function update_site_status( $total_sites, $completed_sites ) {
		$this->update_status(
			array(
				'total_sites'     => $total_sites,
				'completed_sites' => $completed_sites,
			)
		);
	}
}