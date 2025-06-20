<?php
/**
 * Woocommerce API provider.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Integration\Woocommerce;

/**
 * Woocommerce API provider.
 *
 * @method \WC_Product|null|false wc_get_product( $the_product = false, $deprecated = array() )
 * @method string wc_format_decimal( $number, $dp = false, $trim_zeros = false )
 * @method int wc_get_price_decimals()
 * @method string get_woocommerce_currency( $currency = '' )
 * @method int wc_get_page_id( $page )
 * @method string wc_get_page_permalink( string $page, string|bool $fallback = null )
 * @method boolean is_shop()
 */
class Api {

	/**
	 * Invoked automatically when a non-existing method or inaccessible method is called.
	 *
	 * @param string $name Name of the method that is being called by the object.
	 * @param array  $arguments Array of arguments passed to the method call.
	 *
	 * @return mixed|null
	 */
	public function __call( $name, $arguments ) {
		if ( function_exists( $name ) ) {
			return call_user_func_array( $name, $arguments );
		}

		return null;
	}
}