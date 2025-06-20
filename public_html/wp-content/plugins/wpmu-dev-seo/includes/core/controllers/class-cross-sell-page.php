<?php
/**
 * Handles Cross-Sell page for free version.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Services\Service;
use SmartCrawl\Singleton;

/**
 * Cross_Sell_page Controller.
 */
class Cross_Sell_page extends Controller {

	use Singleton;

	/**
	 * Initializes the application.
	 *
	 * @return void
	 */
	protected function init() {
		if ( Service::get( Service::SERVICE_SITE )->is_member() ) {
			return;
		}

		$cross_sell_path = SMARTCRAWL_PLUGIN_DIR . 'external/plugins-cross-sell-page/plugin-cross-sell.php';
		if ( ! file_exists( $cross_sell_path ) ) {
			return;
		}
		static $cross_sell = null;
		if ( is_null( $cross_sell ) ) {
			if ( ! class_exists( '\WPMUDEV\Modules\Plugin_Cross_Sell' ) ) {
				require_once $cross_sell_path;
			}

			$submenu_params = array(
				'slug'               => 'smartcrawl-seo', // Required.
				'parent_slug'        => 'wds_wizard', // Required.
				'capability'         => 'manage_options', // Optional.
				'menu_slug'          => 'smartcrawl_cross_sell', // Optional - Strongly recommended to set in order to avoid admin page conflicts with other WPMU DEV plugins.
				'position'           => 17, // Optional – Usually a specific position will be required.
				'translation_dir'    => dirname( \SMARTCRAWL_PLUGIN_BASENAME ) . '/languages', // Optional – The directory where the translation files are located.
				'menu_hook_priority' => 99, // Optional – The priority of the menu hook.
			);

			$cross_sell = new \WPMUDEV\Modules\Plugin_Cross_Sell( $submenu_params );
		}
	}
}