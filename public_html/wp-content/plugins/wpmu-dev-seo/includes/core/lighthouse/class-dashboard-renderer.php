<?php
/**
 * Dashboard Renderer class for SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse;

use SmartCrawl\Singleton;
use SmartCrawl\Renderable;
use SmartCrawl\Services\Service;

/**
 * Dashboard_Renderer class.
 *
 * Renders the dashboard views for SmartCrawl.
 */
class Dashboard_Renderer extends Renderable {

	use Singleton;

	/**
	 * Renders the specified view with the given arguments.
	 *
	 * @param string $view The view to render.
	 * @param array  $args The arguments to pass to the view.
	 *
	 * @return void
	 */
	public static function render( $view, $args = array() ) {
		$instance = self::get();
		$instance->render_view( $view, $args );
	}

	/**
	 * Loads the specified view with the given arguments.
	 *
	 * @param string $view The view to load.
	 * @param array  $args The arguments to pass to the view.
	 *
	 * @return false|mixed
	 */
	public static function load( $view, $args = array() ) {
		$instance = self::get();

		return $instance->load_view( $view, $args );
	}

	/**
	 * Gets the default values for the view.
	 *
	 * @return array The default values for the view.
	 */
	protected function get_view_defaults() {
		/**
		 * Gets the Lighthouse service instance.
		 *
		 * @var \SmartCrawl\Services\Lighthouse $lighthouse Service
		 */
		$lighthouse = Service::get( Service::SERVICE_LIGHTHOUSE );
		$device     = Options::dashboard_widget_device();

		if ( ! in_array( $device, array( 'desktop', 'mobile' ), true ) ) {
			$device = 'desktop';
		}

		return array(
			'lighthouse_start_time' => $lighthouse->get_start_time(),
			'lighthouse_report'     => $lighthouse->get_last_report( $device ),
		);
	}
}