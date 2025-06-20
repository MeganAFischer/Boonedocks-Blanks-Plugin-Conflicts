<?php
/**
 * Robots_Txt class for checking the validity of the robots.txt file.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Lighthouse\Tables\Table;
use SmartCrawl\Settings;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Admin\Settings\Admin_Settings;

/**
 * Robots_Txt class.
 *
 * Checks if the robots.txt file is valid.
 */
class Robots_Txt extends Check {
	const ID = 'robots-txt';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'robots.txt is valid', 'wds' ) );
		$this->set_failure_title( esc_html__( 'robots.txt is not valid', 'wds' ) );
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