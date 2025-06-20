<?php
/**
 * Structured_Data class for checking the validity of structured data.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Settings;
use SmartCrawl\Simple_Renderer;

/**
 * Structured_Data class.
 *
 * Checks if the structured data is valid.
 */
class Structured_Data extends Check {
	const ID = 'structured-data';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Structured data is valid', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Structured data is invalid', 'wds' ) );
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