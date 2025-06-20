<?php
/**
 * Class to handle mixpanel schema events functionality.
 *
 * @since   3.7.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Mixpanel;

use SmartCrawl\Singleton;
use SmartCrawl\Settings;

/**
 * Schema class.
 *
 * Handles mixpanel schema events functionality.
 */
class Schema extends Events {

	use Singleton;

	/**
	 * Flag to check if general settings are tracked.
	 *
	 * @var bool
	 */
	public static $track_general = false;

	/**
	 * Initialize class.
	 *
	 * @since 3.7.0
	 */
	protected function init() {
		add_action( 'smartcrawl_after_delete_schema_types', array( $this, 'intercept_schema_types_delete' ), 10, 2 );
		add_action( 'smartcrawl_after_add_schema_types', array( $this, 'intercept_schema_types_add' ), 10, 4 );
		add_action( 'update_option_wds_schema_options', array( $this, 'intercept_general_settings_update' ), 10, 2 );
		add_action( 'shutdown', array( $this, 'track_general_settings_update' ) );
	}

	/**
	 * Handle event for deleting schema types.
	 *
	 * @since 3.7.0
	 *
	 * @param array $deleted_types  Delete schema types.
	 * @param array $previous_types Old schema types.
	 *
	 * @return void
	 */
	public function intercept_schema_types_delete( $deleted_types, $previous_types ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		$deleted_labels = array();

		// Get deleted schema labels.
		foreach ( $deleted_types as $type ) {
			if ( isset( $previous_types[ $type ]['type'] ) ) {
				$deleted_labels[] = $previous_types[ $type ]['type'];
			}
		}

		$this->tracker()->track(
			'SMA - Delete Schema',
			array(
				'schema_type' => $deleted_labels,
				'action'      => 'Delete',
			)
		);
	}

	/**
	 * Handle event for adding new schema types.
	 *
	 * @since 3.7.0
	 *
	 * @param array $new_types      New schema types.
	 * @param array $previous_types Old schema types.
	 * @param array $current_types  Current schema types.
	 * @param array $post_data      Post data.
	 *
	 * @return void
	 */
	public function intercept_schema_types_add( $new_types, $previous_types, $current_types, $post_data ) {
		if ( ! $this->is_tracking_active() || empty( $post_data['option_page'] ) || 'wds_schema_options' !== $post_data['option_page']
		) {
			return;
		}

		$types = array();

		// Get added schema labels.
		foreach ( $new_types as $type ) {
			if ( isset( $current_types[ $type ]['type'] ) ) {
				$types[] = $current_types[ $type ]['type'];
			}
		}

		$types = array_unique( $types );

		if ( ! empty( $types ) ) {
			$this->tracker()->track(
				'SMA - Record Schema Type Builder',
				array( 'schema_type' => wp_json_encode( $types ) )
			);
		}
	}

	/**
	 * Handle schema general settings update.
	 *
	 * @since 3.7.0
	 *
	 * @param array $old_value Old options value.
	 * @param array $new_value New options value.
	 *
	 * @return void
	 */
	public function intercept_general_settings_update( $old_value, $new_value ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		// Check output page value.
		$old_value = $this->get_value( 'schema_output_page', $old_value );
		$new_value = $this->get_value( 'schema_output_page', $new_value );

		// Continue only if value changed.
		if ( $old_value !== $new_value ) {
			self::$track_general = true;
		}
	}

	/**
	 * Track schema general and social settings update.
	 *
	 * Social and general settings are stored in different options.
	 * So make sure to send event only once.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function track_general_settings_update() {
		if ( ! self::$track_general ) {
			return;
		}

		$social_options = Settings::get_specific_options( 'wds_social_options' );
		$schema_options = Settings::get_component_options( Settings::COMP_SCHEMA );

		$properties = array(
			'output_page'         => 'Homepage',
			'site_representation' => $this->get_value( 'schema_type', $social_options, '' ),
		);

		$social_accounts = array();

		$socials = array(
			'twitter_username' => 'Twitter',
			'fb-app-id'        => 'Facebook App ID',
			'facebook_url'     => 'Facebook',
			'instagram_url'    => 'Instagram',
			'linkedin_url'     => 'Linkedin',
			'pinterest_url'    => 'Pinterest',
			'youtube_url'      => 'YouTube',
		);

		// Get social values.
		foreach ( $socials as $social => $label ) {
			if ( ! empty( $social_options[ $social ] ) ) {
				$social_accounts[] = $label;
			}
		}

		if ( ! empty( $social_accounts ) ) {
			$properties['social_account'] = wp_json_encode( $social_accounts );
		}

		// Get output page.
		$output_page = $this->get_value( 'schema_output_page', $schema_options );
		if ( ! empty( $output_page ) ) {
			$output_page = get_post( $output_page );

			if ( isset( $output_page->post_title ) ) {
				$properties['output_page'] = $output_page->post_title;
			}
		}

		$this->tracker()->track( 'SMA - Schema General Settings', $properties );
	}
}