<?php
/**
 * Class to handle mixpanel events functionality.
 *
 * @since   3.7.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Mixpanel;

use SmartCrawl\Settings;
use SmartCrawl\Controllers\Controller;

/**
 * Abstract class for Mixpanel Events.
 */
abstract class Events extends Controller {

	/**
	 * Initialize class.
	 *
	 * @since 3.7.0
	 */
	protected function init() {}

	/**
	 * Get mixpanel instance.
	 *
	 * @since 3.7.0
	 *
	 * @return \Smartcrawl_Vendor\Mixpanel
	 */
	protected function tracker() {
		return Mixpanel::get()->tracker();
	}

	/**
	 * Check if usage tracking is active.
	 *
	 * @since 3.7.0
	 *
	 * @return bool
	 */
	protected function is_tracking_active() {
		$options = Settings::get_options();

		return $this->get_value( 'usage_tracking', $options );
	}

	/**
	 * Get a key value from the values provided.
	 *
	 * @param string $key     Key.
	 * @param array  $values  Array of values.
	 * @param mixed  $default_value Default value.
	 *
	 * @return false|mixed
	 *
	 * @since 3.7.0
	 */
	protected function get_value( $key, $values = array(), $default_value = false ) {
		return isset( $values[ $key ] ) ? $values[ $key ] : $default_value;
	}
}