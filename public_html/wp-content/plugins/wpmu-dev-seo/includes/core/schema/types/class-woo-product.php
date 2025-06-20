<?php
/**
 * Woo_Product class for handling WooCommerce product schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Types;

/**
 * Class Woo_Product
 *
 * Handles WooCommerce product schema fragments.
 */
class Woo_Product extends Type {
	const TYPE          = 'WooProduct';
	const TYPE_SIMPLE   = 'WooSimpleProduct';
	const TYPE_VARIABLE = 'WooVariableProduct';

	/**
	 * Retrieves the type of the product.
	 *
	 * @return string The type of the product.
	 */
	public function get_type() {
		return 'Product';
	}

	/**
	 * Checks if the product schema is active.
	 *
	 * @return bool True if the product schema is active, false otherwise.
	 */
	public function is_active() {
		return parent::is_active() && \smartcrawl_woocommerce_active();
	}
}