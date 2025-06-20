<?php
/**
 * Module settings
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Singleton;

/**
 * Module Settings
 */
class Module_Settings extends Admin_Settings {

	use Singleton;

	/**
	 * Renders the whole page view by calling `_render`
	 *
	 * @param string $view View file to load.
	 * @param array  $args Optional array of arguments to pass to view.
	 */
	public function output_view( $view, $args = array() ) {
		$this->render_view( $view, $args );
	}

	/**
	 * Sanitizes the given input of module settings value.
	 *
	 * @param array $input The input to validate.
	 *
	 * @return array
	 */
	public function validate( $input ) {
		return array();
	}

	/**
	 * Retrieves the title of module settings.
	 *
	 * @return void
	 */
	public function get_title() {}
}