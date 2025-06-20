<?php
/**
 * Renderer class for rendering views in the SmartCrawl plugin.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse;

use SmartCrawl\Singleton;
use SmartCrawl\Renderable;
use SmartCrawl\Services\Service;

/**
 * Class Renderer
 *
 * Handles the rendering of views for the SmartCrawl plugin.
 */
class Renderer extends Renderable {

	use Singleton;

	/**
	 * Renders a view.
	 *
	 * @param string $view The view to render.
	 * @param array  $args Arguments to pass to the view.
	 *
	 * @return void
	 */
	public static function render( $view, $args = array() ) {
		$instance = self::get();
		$instance->render_view( $view, $args );
	}

	/**
	 * Loads a view.
	 *
	 * @param string $view The view to load.
	 * @param array  $args Arguments to pass to the view.
	 *
	 * @return false|mixed
	 */
	public static function load( $view, $args = array() ) {
		$instance = self::get();

		return $instance->load_view( $view, $args );
	}

	/**
	 * Retrieves the default view arguments.
	 *
	 * @return array
	 */
	public function view_defaults() {
		return $this->get_view_defaults();
	}

	/**
	 * Gets the default view arguments.
	 *
	 * @return array
	 */
	protected function get_view_defaults() {
		/**
		 * Lighthouse service.
		 *
		 * @var $lighthouse \SmartCrawl\Services\Lighthouse
		 */
		$lighthouse = Service::get( Service::SERVICE_LIGHTHOUSE );
		$device     = \smartcrawl_get_array_value( $_GET, 'device' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $device, array( 'desktop', 'mobile' ), true ) ) {
			$device = 'desktop';
		}

		return array(
			'lighthouse_start_time' => $lighthouse->get_start_time(),
			'lighthouse_report'     => $lighthouse->get_last_report( $device ),
		);
	}
}