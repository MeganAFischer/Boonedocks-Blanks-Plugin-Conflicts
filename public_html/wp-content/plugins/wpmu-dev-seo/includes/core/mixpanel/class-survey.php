<?php
/**
 * Class to handle mixpanel events for survey.
 *
 * @since   3.10.4
 * @package SmartCrawl
 */

namespace SmartCrawl\Mixpanel;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use Smartcrawl_Vendor\Mixpanel as Mixpanel_Lib;

/**
 * Mixpanel Survey Event class
 */
class Survey extends Events {

	use Singleton;

	/**
	 * Initialize class.
	 *
	 * @since 3.10.0
	 */
	protected function init() {
		add_action( 'wp_ajax_smartcrawl_track_deactivate', array( $this, 'track_deactivate' ) );
	}

	/**
	 * Handles to track Deactivations Survey event.
	 *
	 * @return void
	 *
	 * @since 3.10.4
	 */
	public function track_deactivate() {
		if ( ! isset( $_POST['_wds_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-survey-nonce' ) ) {
			wp_send_json_error();
		}

		if ( ! isset( $_POST['skipped'] ) ) {
			wp_send_json_error();
		}

		$props = array();

		if ( 'true' === $_POST['skipped'] ) {
			if ( ! $this->is_tracking_active() ) {
				wp_send_json_success();
			}

			$props['skipped'] = 'Yes';
		} else {
			if ( ! isset( $_POST['from'] ) || empty( $_POST['selected'] ) ) {
				wp_send_json_error();
			}

			if ( $this->is_tracking_active() ) {
				$props['activate_specific_modules'] = Settings::get_specific_options( 'wds-from-survey', 0 );
			}

			$selected = sanitize_text_field( stripslashes_deep( $_POST['selected'] ) );
			$message  = isset( $_POST['message'] ) ? wp_kses_post( stripslashes_deep( $_POST['message'] ) ) : array();

			$options = array(
				'not-needed' => __( 'I no longer need the plugin', 'wds' ),
				'switching'  => __( "I'm switching to a different plugin", 'wds' ),
				'not-work'   => __( "I couldn't get the plugin to work", 'wds' ),
				'temporary'  => __( "It's a temporary deactivation", 'wds' ),
				'other'      => __( 'Other', 'wds' ),
			);

			$props = array_merge(
				$props,
				$this->is_tracking_active() ?
				array(
					'deactivated_from' => 'dashboard' === $_POST['from'] ? 'WPMU DEV Dashboard' : 'Plugins',
					'reason'           => array(),
					'skipped'          => 'No',
				) : array(
					'reason' => array(),
				)
			);

			$props['reason'] = $options[ $selected ];

			if ( 'switching' === $selected && ! empty( $message ) ) {
				$props['switch_message'] = $message;
			}

			if ( 'other' === $selected && ! empty( $message ) ) {
				$props['other_message'] = $message;
			}
			$props['usage_tracking'] = Settings::get_value( 'usage_tracking', Settings::get_options() );

			$install_option_name = \smartcrawl_is_build_type_full() ? 'wds-pro-install-date' : 'wds-free-install-date';
			if ( get_site_option( $install_option_name ) ) {
				$props['activation_date'] = date_i18n( 'Y-m-d H:i:s', get_site_option( $install_option_name ) );
			}
		}

		if ( $this->is_tracking_active() ) {
			$this->tracker()->track( 'SMA - Deactivation Survey', $props );
		} else {
			$this->tracker()->unregisterAll(
				array(
					'active_theme',
					'mysql_version',
					'php_version',
					'server_type',
					'wp_type',
					'device',
					'user_agent',
					'memory_limit',
					'max_execution_time',
				)
			);

			$this->tracker()->track( 'SMA - Deactivation Survey', $props );
		}

		wp_send_json_success();
	}
}