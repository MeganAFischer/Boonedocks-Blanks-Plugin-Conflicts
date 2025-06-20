<?php
/**
 * Class for checking if the document has a valid hreflang.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Lighthouse\Tables\Table;
use SmartCrawl\Simple_Renderer;

/**
 * Hreflang class.
 *
 * Checks if the document has a valid hreflang.
 */
class Hreflang extends Check {
	const ID = 'hreflang';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Document has a valid hreflang', 'wds' ) );
		$this->set_failure_title( esc_html__( "Document doesn't have a valid hreflang", 'wds' ) );
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