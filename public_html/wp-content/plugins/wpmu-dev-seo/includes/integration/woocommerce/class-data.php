<?php
/**
 * Woocommerce Data handler.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Integration\Woocommerce;

use SmartCrawl\Settings;

/**
 * Woocommerce Data class.
 *
 * @package SmartCrawl
 */
class Data {
	/**
	 * Retrieves db options.
	 *
	 * @return array
	 */
	public function get_options() {
		$options = \smartcrawl_get_array_value( get_option( Settings::ADVANCED_MODULE ), 'woocommerce' );

		return empty( $options ) || ! is_array( $options )
			? array()
			: $options;
	}
}