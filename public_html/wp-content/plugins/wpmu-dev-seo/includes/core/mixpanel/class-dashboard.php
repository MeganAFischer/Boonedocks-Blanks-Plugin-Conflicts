<?php
/**
 * Class to handle mixpanel dashboard events functionality.
 *
 * @since   3.7.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Mixpanel;

use SmartCrawl\Singleton;

/**
 * Mixpanel Tools Event class
 */
class Dashboard extends Events {

	use Singleton;

	/**
	 * Initialize class.
	 *
	 * @since 3.7.0
	 */
	protected function init() {
		add_action( 'update_option_wds_settings_options', array( $this, 'intercept_activation_update' ) );
	}

	/**
	 * Handle Breadcrumbs settings update.
	 *
	 * @return void
	 *
	 * @since 3.7.0
	 */
	public function intercept_activation_update() {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		if ( ! isset( $_POST['_wds_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-nonce' ) ) {
			return;
		}

		$option_key = \smartcrawl_get_array_value( $_POST, 'flag' );

		switch ( $option_key ) {
			case 'autolinks':
				$option_name = 'Automatic Links';
				break;
			case 'robots-txt':
				$option_name = 'Robots.txt Editor';
				break;
			case 'breadcrumb':
				$option_name = 'Breadcrumbs';
				break;
			default:
				return;
		}

		$this->tracker()->track(
			'SMA - Advanced Tool Activated',
			array(
				'advanced_tool'  => $option_name,
				'triggered_from' => 'Dashboard',
			)
		);
	}
}