<?php
/**
 * Class Controller
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Social;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Controllers;

/**
 * Class Controller
 */
class Controller extends Controllers\Controller {

	use Singleton;

	/**
	 * Should this module run?.
	 *
	 * @return bool
	 */
	public function should_run() {
		return (
			Settings::get_setting( 'social' ) &&
			Admin_Settings::is_tab_allowed( Settings::TAB_SOCIAL )
		);
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	protected function init() {
		OpenGraph_Printer::run();
		Twitter_Printer::run();
		Pinterest_Printer::run();
	}
}