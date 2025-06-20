<?php
/**
 * Link_Text class for checking if links have descriptive text.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Lighthouse\Tables\Table;
use SmartCrawl\Simple_Renderer;

/**
 * Link_Text class.
 *
 * Checks if links have descriptive text.
 */
class Link_Text extends Check {
	const ID = 'link-text';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Links have descriptive text', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Links do not have descriptive text', 'wds' ) );
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