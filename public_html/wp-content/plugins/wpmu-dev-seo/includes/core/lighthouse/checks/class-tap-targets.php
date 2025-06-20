<?php
/**
 * Class for checking if tap targets are sized appropriately.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Lighthouse\Tables;
use SmartCrawl\Simple_Renderer;

/**
 * Tap_Targets class.
 *
 * Checks if tap targets are sized appropriately.
 */
class Tap_Targets extends Check {
	const ID = 'tap-targets';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Tap targets are sized appropriately', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Tap targets are not sized appropriately', 'wds' ) );
	}

	/**
	 * Gets the ID of the check.
	 *
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}
}