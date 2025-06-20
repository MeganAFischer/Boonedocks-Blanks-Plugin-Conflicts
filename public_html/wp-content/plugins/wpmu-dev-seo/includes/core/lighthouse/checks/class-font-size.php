<?php
/**
 * Class for checking if the document uses legible font sizes.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Lighthouse\Tables\Table;
use SmartCrawl\Simple_Renderer;

/**
 * Font_Size class.
 *
 * Checks if the document uses legible font sizes.
 */
class Font_Size extends Check {
	const ID = 'font-size';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Document uses legible font sizes', 'wds' ) );
		$this->set_failure_title( esc_html__( "Document doesn't use legible font sizes", 'wds' ) );
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