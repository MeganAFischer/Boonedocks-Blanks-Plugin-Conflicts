<?php
/**
 * Class for checking if the document avoids browser plugins.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Lighthouse\Tables\Table;
use SmartCrawl\Simple_Renderer;

/**
 * Plugins class.
 *
 * Checks if the document avoids browser plugins.
 */
class Plugins extends Check {
	const ID = 'plugins';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Document avoids browser plugins', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Document uses browser plugins', 'wds' ) );
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