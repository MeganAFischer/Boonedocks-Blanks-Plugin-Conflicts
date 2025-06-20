<?php
/**
 * Controller for Advanced module.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Services\Service;
use SmartCrawl\Settings;

/**
 * Redirects Controller.
 */
abstract class Submodule_Controller extends Controller {

	/**
	 * Parent module.
	 *
	 * @var Module_Controller
	 */
	public $parent;

	/**
	 * Submodule ID.
	 *
	 * @var string
	 */
	public $module_id;

	/**
	 * Submodule name.
	 *
	 * @var string
	 */
	public $module_name;

	/**
	 * Submodule title.
	 *
	 * @var string
	 */
	public $module_title = '';

	/**
	 * Mixpanel event name.
	 *
	 * @var string
	 */
	public $event_name;

	/**
	 * Indicates if this submodule is premium feature.
	 *
	 * @var bool
	 */
	public $premium = false;

	/**
	 * Includes methods that runs always.
	 *
	 * @return void
	 */
	protected function always() {
		$this->options = wp_parse_args(
			$this->options,
			is_callable( array( $this, 'defaults' ) ) ?
				call_user_func( array( $this, 'defaults' ) ) :
				array()
		);

		add_action( "smartcrawl_{$this->parent->module_id}_after_output_page", array( $this, 'localize_script' ) );

		add_action( "wp_ajax_smartcrawl_update_options_{$this->module_id}", array( $this, 'update_submodule' ) );

		if ( ! \smartcrawl_get_array_value( $this->parent->settings_opts, 'hide_disables', true ) && is_callable( array( $this, 'render_dashboard_content' ) ) ) {
			add_action( "smartcrawl_widget_{$this->parent->module_id}_submodules", array( $this, 'render_dashboard_content' ) );
		}
	}

	/**
	 * Should this module run?.
	 *
	 * @return bool
	 */
	public function should_run() {
		return $this->parent->should_run() && is_array( $this->options ) && ! empty( $this->options['active'] );
	}

	/**
	 * Initiailization method.
	 *
	 * @return void
	 */
	protected function init() {
		if ( \smartcrawl_get_array_value( $this->parent->settings_opts, 'hide_disables', true ) && is_callable( array( $this, 'render_dashboard_content' ) ) ) {
				add_action( "smartcrawl_widget_{$this->parent->module_id}_submodules", array( $this, 'render_dashboard_content' ) );
		}
	}

	/**
	 * Includes methods when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {
		$this->options['active'] = false;
	}

	/**
	 * Ajax handler to update submodule options.
	 *
	 * @return void
	 */
	public function update_submodule() {
		if ( ! isset( $_POST['_wds_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-admin-nonce' ) ) {
			wp_send_json_error();
		}

		if ( isset( $_POST['active'] ) ) {
			$input = array( 'active' => 'true' === sanitize_text_field( wp_unslash( $_POST['active'] ) ) );
		} elseif ( isset( $_POST['options'] ) ) {
			$input = stripslashes_deep( $_POST['options'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		if ( empty( $input ) ) {
			wp_send_json_error();
		}

		$old_options = $this->options;

		if ( is_callable( array( $this, 'sanitize_options' ) ) && $this->sanitize_options( $input ) ) {
			$this->options = wp_parse_args( $this->options, $old_options );

			$this->parent->update_option( $this->module_name, $this->options );

			do_action( "smartcrawl_after_sanitize_$this->module_id", $old_options, $this->options );

			if ( is_callable( array( $this, 'localize_script_args' ) ) ) {
				wp_send_json_success(
					apply_filters(
						'smartcrawl_update_submodule_' . $this->module_id,
						array(
							array(
								'name'  => $this->module_name,
								'value' => $this->localize_script_args(),
							),
						)
					)
				);
			}

			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Localizes script for this submodule.
	 *
	 * @return void
	 */
	public function localize_script() {
		if ( is_callable( array( $this, 'localize_script_args' ) ) ) {
			wp_localize_script( $this->parent->module_name, '_wds_' . $this->module_name, $this->localize_script_args() );
		}
	}
}