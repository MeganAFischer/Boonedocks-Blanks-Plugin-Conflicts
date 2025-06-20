<?php
/**
 * Controls MaxMind functionality.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Integration\Maxmind;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Controllers;
use SmartCrawl\Logger;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * MaxMind main controller.
 */
class Controller extends Controllers\Controller {

	use Singleton;

	/**
	 * Should this module run?
	 *
	 * @return bool
	 */
	public function should_run() {
		return true;
	}

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'wp_ajax_smartcrawl_download_geodb', array( $this, 'download_geodb' ) );
		add_action( 'wp_ajax_smartcrawl_reset_geodb', array( $this, 'reset_geodb' ) );
	}

	/**
	 * Includes methods when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {
		parent::terminate();

		remove_action( 'wp_ajax_smartcrawl_download_geodb', array( $this, 'download_geodb' ) );
		remove_action( 'wp_ajax_smartcrawl_reset_geodb', array( $this, 'reset_geodb' ) );
	}

	/**
	 * Ajax handler to download GEODB from Maxmind.
	 */
	public function download_geodb() {
		$data = $this->get_request_data();

		if ( empty( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid POST request.', 'wds' ) ) );
		}

		$license_key = \smartcrawl_get_array_value( $data, 'license_key' );

		if ( ! $license_key ) {
			wp_send_json_error( array( 'message' => __( 'License key is required.', 'wds' ) ) );
		}

		$symb_key = GeoDB::get()->activate_license( $license_key );

		if ( is_wp_error( $symb_key ) ) {
			Logger::error( 'Error from MaxMind: ' . $symb_key->get_error_message() );

			wp_send_json_error(
				array(
					'message' => $symb_key->get_error_message(),
				)
			);
		}

		wp_send_json_success( array( 'key' => $symb_key ) );
	}

	/**
	 * Ajax handler to reset GEODB and license key settings for Maxmind.
	 */
	public function reset_geodb() {
		$data = $this->get_request_data();

		if ( empty( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid POST request.', 'wds' ) ) );
		}

		GeoDB::get()->delete_license();
		GeoDB::get()->delete_db();

		wp_send_json_success();
	}

	/**
	 * Retrieves HTTP Request data.
	 *
	 * @return array|mixed
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-admin-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}
}