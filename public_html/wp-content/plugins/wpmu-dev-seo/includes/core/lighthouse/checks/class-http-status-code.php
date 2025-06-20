<?php
/**
 * Class for checking the HTTP status code of a page.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Simple_Renderer;

/**
 * Http_Status_Code class.
 *
 * Checks if the page has a successful HTTP status code.
 */
class Http_Status_Code extends Check {
	const ID = 'http-status-code';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Page has successful HTTP status code', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Page has unsuccessful HTTP status code', 'wds' ) );
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