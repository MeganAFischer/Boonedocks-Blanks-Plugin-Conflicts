<?php
/**
 * Class for checking if the document has a meta description.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Settings;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Admin\Settings\Admin_Settings;

/**
 * Meta_Description class.
 *
 * Checks if the document has a meta description.
 */
class Meta_Description extends Check {
	const ID = 'meta-description';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Document has a meta description', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Document does not have a meta description', 'wds' ) );
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