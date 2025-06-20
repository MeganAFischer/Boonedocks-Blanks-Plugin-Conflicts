<?php
/**
 * Handles the plugin's upgrade page.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin\Pages;

if ( ! defined( 'WPINC' ) ) {
	die;
}

use SmartCrawl\Singleton;

/**
 * Upgrade page controller
 */
class Upgrade extends Page {

	use Singleton;

	const MENU_SLUG = 'wds_upgrade';

	/**
	 * Defines action hooks for this controller.
	 */
	protected function init() {
	}

	/**
	 * Retrieves the menu slug.
	 *
	 * @return string The menu slug.
	 */
	public function get_menu_slug() {
		return '';
	}
}