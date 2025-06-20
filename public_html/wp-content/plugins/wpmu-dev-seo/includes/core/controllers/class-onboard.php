<?php
/**
 * Dispatches action listeners for Onboarding.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Settings;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Singleton;

/**
 * Onboard Controller
 */
class Onboard extends Controller {

	use Singleton;

	const ONBOARDING_DONE_OPTION = 'wds-onboarding-done';

	/**
	 * Dispatches action listeners for onboard.
	 *
	 * @return void
	 */
	public function dispatch_actions() {
		add_action( 'wds-dshboard-after_settings', array( $this, 'add_onboarding' ) );

		add_action( 'wp_ajax_smartcrawl_onboard_toggle', array( $this, 'ajax_action' ) );
		add_action( 'wp_ajax_smartcrawl_onboard_skip', array( $this, 'ajax_skip' ) );
		add_action( 'wp_ajax_smartcrawl_onboard_done', array( $this, 'ajax_done' ) );
	}

	/**
	 * Ajax handler for skip on onboard.
	 *
	 * @return void
	 */
	public function ajax_skip() {
		$this->set_done();

		/**
		 * Action hook to trigger after onboarding is skipped.
		 *
		 * @since 3.7.0
		 */
		do_action( 'smartcrawl_after_onboarding_skip' );

		wp_send_json_success();
	}

	/**
	 * Ajax handler to processe onboarding completion.
	 *
	 * @return void
	 */
	public function ajax_done() {
		$this->set_done();

		/**
		 * Action hook to trigger after onboarding is done.
		 *
		 * @since 3.7.0
		 */
		do_action( 'smartcrawl_after_onboarding_done' );

		wp_send_json_success();
	}

	/**
	 * Ajax handler to process onboarding actions.
	 *
	 * @return void
	 */
	public function ajax_action() {
		$data   = $this->get_request_data();
		$target = ! empty( $data['target'] ) ? sanitize_key( $data['target'] ) : false;
		$enable = empty( $data['enable'] ) ? false : true;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( 'analysis-enable' === $target ) {
			$opts                         = Settings::get_specific_options( 'wds_settings_options' );
			$opts['analysis-seo']         = $enable;
			$opts['analysis-readability'] = $enable;
			Settings::update_specific_options( 'wds_settings_options', $opts );
			wp_send_json_success();
		} elseif ( 'opengraph-twitter-enable' === $target ) {
			$opts                        = Settings::get_component_options( Settings::COMP_SOCIAL );
			$opts['og-enable']           = $enable;
			$opts['twitter-card-enable'] = $enable;
			Settings::update_component_options( Settings::COMP_SOCIAL, $opts );
			wp_send_json_success();
		} elseif ( 'sitemaps-enable' === $target ) {
			$opts            = Settings::get_specific_options( 'wds_settings_options' );
			$opts['sitemap'] = $enable;
			Settings::update_specific_options( 'wds_settings_options', $opts );
			wp_send_json_success();
		} elseif ( 'robots-txt-enable' === $target ) {
			$controller               = \SmartCrawl\Modules\Advanced\Controller::get();
			$opts                     = $controller->get_options();
			$opts['robots']['active'] = $enable;
			$controller->update_option( 'robots', $opts['robots'] );
			wp_send_json_success();
		} elseif ( 'usage-tracking-enable' === $target ) {
			$opts                   = Settings::get_specific_options( 'wds_settings_options' );
			$opts['usage_tracking'] = $enable;
			Settings::update_specific_options( 'wds_settings_options', $opts );
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Ajax handler to add onboarding content if it hasn't been completed yet.
	 *
	 * @return void
	 */
	public function add_onboarding() {
		if ( $this->is_done() ) {
			return;
		}

		Simple_Renderer::render( 'dashboard/onboarding' );
	}

	/**
	 * Bind listening actions
	 *
	 * @return bool
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'dispatch_actions' ) );

		return true;
	}

	/**
	 * Unbinds listening actions
	 *
	 * @return bool
	 */
	protected function terminate() {
		remove_action( 'admin_init', array( $this, 'dispatch_actions' ) );

		return true;
	}

	/**
	 * Retrieves the request data.
	 *
	 * @return array The request data.
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-onboard-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}

	/**
	 * Checks if onboarding is done
	 *
	 * @return bool
	 */
	public function is_done() {
		return ! empty( Settings::get_specific_options( self::ONBOARDING_DONE_OPTION ) );
	}

	/**
	 * Sets onboarding as done in the settings.
	 */
	public function set_done() {
		Settings::update_specific_options( self::ONBOARDING_DONE_OPTION, SMARTCRAWL_VERSION );
	}
}