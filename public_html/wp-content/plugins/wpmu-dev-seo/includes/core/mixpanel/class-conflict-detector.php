<?php
/**
 * Class to handle mixpanel conflict detector events functionality.
 *
 * @package SmartCrawl
 * @since   3.10.4
 */

namespace SmartCrawl\Mixpanel;

use SmartCrawl\Admin\Pages\Network_Settings;
use SmartCrawl\Admin\Pages\Upgrade;
use SmartCrawl\Modules\Advanced\Controller;
use SmartCrawl\Singleton;
use SmartCrawl\Settings;

/**
 * Class Sitemap.
 */
class Conflict_Detector extends Events {

	use Singleton;

	/**
	 * Involves all modules' titles.
	 *
	 * @var array
	 */
	private $module_titles = array();

	/**
	 * Initialize class.
	 *
	 * @since 3.7.0
	 */
	protected function init() {
		$this->module_titles = array(
			Settings::TAB_DASHBOARD     => 'Dashboard',
			Settings::TAB_HEALTH        => 'SEO Health',
			Settings::TAB_ONPAGE        => 'Title & Meta',
			Settings::TAB_SOCIAL        => 'Social',
			Settings::TAB_SCHEMA        => 'Schema',
			Settings::TAB_SITEMAP       => 'Sitemaps',
			Settings::ADVANCED_MODULE   => 'Advanced Tools',
			Upgrade::MENU_SLUG          => 'Upgrade to SmartCrawl Pro',
			Network_Settings::MENU_SLUG => 'Network Settings',
		);

		add_action( 'smartcrawl_dismissed_message', array( $this, 'intercept_dismissed_message' ), 10, 2 );
		add_action( 'wp_ajax_smartcrawl_track_confl_det', array( $this, 'track_conflict_detector' ) );
	}

	/**
	 * Handles conflict detector dismissing event.
	 *
	 * @param string $message Dismissing message.
	 * @param string $page Page where the message is dismissed.
	 *
	 * @return void
	 *
	 * @since 3.10.4
	 */
	public function intercept_dismissed_message( $message, $page ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		if ( \SmartCrawl\Admin\Conflict_Detector::ID !== $message ) {
			return;
		}

		if ( ! $page ) {
			return;
		}

		$this->tracker()->track(
			'SMA - Conflict Plugins Notice',
			array(
				'clicked'        => 'X',
				'triggered_from' => $this->module_titles[ $page ],
			)
		);
	}

	/**
	 * Handles conflict detector clicking events.
	 *
	 * @return void
	 *
	 * @since 3.10.4
	 */
	public function track_conflict_detector() {
		if ( ! $this->is_tracking_active() ) {
			wp_send_json_success();
		}

		if ( ! isset( $_POST['_wds_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-admin-nonce' ) ) {
			return;
		}

		if ( isset( $_POST['referring'] ) ) {
			if ( empty( $_POST['referring'] ) ) {
				wp_send_json_error();
			}

			$page = isset( $_POST['page'] ) ? sanitize_key( wp_unslash( $_POST['page'] ) ) : '';

			if ( ! $page ) {
				wp_send_json_error();
			}

			$this->tracker()->track(
				'SMA - Conflict Plugins Notice',
				array(
					'clicked'        => 'Settings',
					'triggered_from' => $this->module_titles[ $page ],
				)
			);

			wp_send_json_success();
		}

		if ( ! isset( $_POST['viewing'] ) ) {
			wp_send_json_error();
		}

		$this->tracker()->track(
			'SMA - Conflict Plugins List',
			array(
				'clicked' => 'Yes',
			)
		);

		wp_send_json_success();
	}
}