<?php
/**
 * File containing the Renderable class for SmartCrawl plugin.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

/**
 * Class Renderable
 *
 * Provides an abstract base class for rendering views in the SmartCrawl plugin.
 *
 * @package SmartCrawl
 */
abstract class Renderable {

	/**
	 * Default view arguments.
	 *
	 * @var array|null
	 */
	private $view_defaults = null;

	/**
	 * Renders the view by calling `_load`
	 *
	 * @param string $view View file to load.
	 * @param array  $args Optional array of arguments to pass to view.
	 *
	 * @return bool
	 */
	protected function render_view( $view, $args = array() ) {
		$view = $this->load_view( $view, $args );
		if ( ! empty( $view ) ) {
			echo $view;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return ! empty( $view );
	}

	/**
	 * Loads the view file and returns the output as string
	 *
	 * @param string $view View file to load.
	 * @param array  $args Optional array of arguments to pass to view.
	 *
	 * @return mixed (string)View output on success, (bool)false on failure.
	 */
	protected function load_view( $view, $args = array() ) {
		$view = preg_replace( '/[^\-_a-z0-9\/]/i', '', $view );
		if ( empty( $view ) ) {
			return false;
		}

		$_path = wp_normalize_path( SMARTCRAWL_PLUGIN_DIR . 'core/admin/templates/' . $view . '.php' );
		if ( ! file_exists( $_path ) || ! is_readable( $_path ) ) {
			return false;
		}

		if ( empty( $args ) || ! is_array( $args ) ) {
			$args = array();
		}

		if ( is_null( $this->view_defaults ) ) {
			$this->view_defaults = $this->get_view_defaults();
		}

		$args = wp_parse_args( $args, $this->view_defaults );

		if ( ! empty( $args ) ) {
			extract( $args );  // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		ob_start();
		include $_path;

		return ob_get_clean();
	}

	/**
	 * Gets the default view arguments.
	 *
	 * @return array Default view arguments.
	 */
	abstract protected function get_view_defaults();
}