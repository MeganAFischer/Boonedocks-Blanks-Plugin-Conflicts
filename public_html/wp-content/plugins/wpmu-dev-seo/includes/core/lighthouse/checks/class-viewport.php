<?php
/**
 * Class for checking if the document has a valid viewport meta tag.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Simple_Renderer;

/**
 * Viewport class.
 *
 * Checks if the document has a valid viewport meta tag.
 */
class Viewport extends Check {
	const ID = 'viewport';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Has a <meta name="viewport"> tag with width or initial-scale', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Does not have a <meta name="viewport"> tag with width or initial-scale', 'wds' ) );
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