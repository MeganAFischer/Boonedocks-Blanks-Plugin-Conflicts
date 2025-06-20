<?php
/**
 * Controls new feature status.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * New Feature controller.
 */
class New_Feature extends Controller {

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
	 * Binds processing actions.
	 */
	protected function init() {
		add_filter( 'smartcrawl_admin_settings_menu_title', array( $this, 'admin_settings_menu_title' ) );
		add_filter( 'smartcrawl_admin_settings_submenu_title', array( $this, 'admin_settings_submenu_title' ), 10, 2 );

		add_action( 'wp_ajax_smartcrawl_new_feature_status', array( $this, 'update_new_feature_status' ) );
		add_action( 'wp_ajax_smartcrawl_update_new_feature', array( $this, 'update_new_feature_badge' ) );
	}

	/**
	 * Retrieves menu name of admin settings with new features.
	 *
	 * @param string $menu_name Navigation item name.
	 *
	 * @return string
	 */
	public function admin_settings_menu_title( $menu_name ) {
		$viewed = (int) Settings::get_specific_options( 'wds-features-viewed', -1 );

		if ( -1 === $viewed ) {
			$menu_name .= '<span class="wds-new-feature-status"></span>';
		}

		return $menu_name;
	}

	/**
	 * Retrieves menu item name of admin settings with new features.
	 *
	 * @param string $menu_name Menu item name.
	 * @param string $slug Menu item slug.
	 *
	 * @return string
	 */
	public function admin_settings_submenu_title( $menu_name, $slug ) {
		$viewed = (int) Settings::get_specific_options( 'wds-features-viewed', 0 );

		if ( Settings::ADVANCED_MODULE === $slug && $viewed < 1 ) {
			$menu_name .= '<span class="wds-new-feature-status"></span>';
		}

		return $menu_name;
	}

	/**
	 * Ajax handler to update new feature status.
	 *
	 * @return void
	 */
	public function update_new_feature_status() {
		if (
			is_multisite()
			&& \smartcrawl_subsite_manager_role() === 'superadmin'
			&& ! current_user_can( 'manage_network_options' )
		) {
			wp_send_json_error( array( 'message' => __( 'No Permission to set.', 'wds' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No Permission to set.', 'wds' ) ) );
		}

		$data = $this->get_request_data();

		if ( ! isset( $data['step'] ) || '-1' === $data['step'] ) {
			wp_send_json_error( array( 'message' => __( 'Feature is not found.', 'wds' ) ) );
		}

		$curr_step = (int) Settings::get_specific_options( 'wds-features-viewed', 0 );

		if ( $curr_step < (int) $data['step'] ) {
			Settings::update_specific_options( 'wds-features-viewed', $data['step'] );
		}

		wp_send_json_success( array( 'message' => __( 'New feature status was successfully updated.', 'wds' ) ) );
	}

	/**
	 * Ajax handler to update new feature badge.
	 *
	 * @return void
	 */
	public function update_new_feature_badge() {
		if ( ! isset( $_POST['_wds_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-admin-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'wds' ) ) );
		}

		if (
			is_multisite()
			&& \smartcrawl_subsite_manager_role() === 'superadmin'
			&& ! current_user_can( 'manage_network_options' )
		) {
			wp_send_json_error( array( 'message' => __( 'No Permission to set.', 'wds' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No Permission to set.', 'wds' ) ) );
		}

		Settings::update_specific_options( 'wds-badge-viewed', 1 );

		wp_send_json_success( array( 'message' => __( 'New feature badge was successfully updated.', 'wds' ) ) );
	}

	/**
	 * Retrieves the request data.
	 *
	 * @return array
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-admin-nonce' )
			? $_POST :
			array();
	}
}