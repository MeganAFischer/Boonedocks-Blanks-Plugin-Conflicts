<?php
/**
 * File containing the Simple_Renderer class for SmartCrawl plugin.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

/**
 * Class Simple_Renderer
 *
 * Provides simple rendering utilities for the SmartCrawl plugin.
 *
 * @package SmartCrawl
 */
class Simple_Renderer extends Renderable {

	use Singleton;

	/**
	 * Renders the view by calling `render_view`.
	 *
	 * @param string $view View file to load.
	 * @param array  $args Optional array of arguments to pass to view.
	 *
	 * @return void
	 */
	public static function render( $view, $args = array() ) {
		$instance = self::get();
		$instance->render_view( $view, $args );
	}

	/**
	 * Loads the view file and returns the output as string.
	 *
	 * @param string $view View file to load.
	 * @param array  $args Optional array of arguments to pass to view.
	 *
	 * @return mixed (string)View output on success, (bool)false on failure.
	 */
	public static function load( $view, $args = array() ) {
		$instance = self::get();

		return $instance->load_view( $view, $args );
	}

	/**
	 * Gets the default view arguments.
	 *
	 * @return array Default view arguments.
	 */
	protected function get_view_defaults() {
		return array();
	}
}