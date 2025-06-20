<?php
/**
 * Class for checking if image elements have alt attributes.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Lighthouse\Tables\Table;
use SmartCrawl\Simple_Renderer;

/**
 * Image_Alt class.
 *
 * Checks if image elements have alt attributes.
 */
class Image_Alt extends Check {
	const ID = 'image-alt';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Image elements have [alt] attributes', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Image elements do not have [alt] attributes', 'wds' ) );
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