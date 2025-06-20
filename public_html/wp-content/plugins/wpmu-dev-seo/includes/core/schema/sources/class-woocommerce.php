<?php
/**
 * Woocommerce class for handling WooCommerce schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

use SmartCrawl\Integration\Woocommerce\Data;

/**
 * Class Woocommerce
 *
 * Handles WooCommerce schema fragments.
 */
class Woocommerce extends Property {
	const ID = 'woocommerce';

	const STOCK_STATUS           = 'stock_status';
	const CURRENCY               = 'currency';
	const PRICE                  = 'price';
	const SALE_START_DATE        = 'date_on_sale_from';
	const SALE_END_DATE          = 'date_on_sale_to';
	const MIN_PRICE              = 'min_price';
	const MAX_PRICE              = 'max_price';
	const PRODUCT_CHILDREN_COUNT = 'product_children_count';
	const PRODUCT_ID             = 'product_id';
	const SKU                    = 'sku';
	const GLOBAL_ID              = 'global_id';
	const REVIEW_COUNT           = 'review_count';
	const AVERAGE_RATING         = 'average_rating';
	const PRODUCT_CATEGORY       = 'product_category';
	const PRODUCT_CATEGORY_URL   = 'product_category_url';
	const PRODUCT_TAG            = 'product_tag';
	const PRODUCT_TAG_URL        = 'product_tag_url';

	/**
	 * The WooCommerce product.
	 *
	 * @var \WC_Product|false
	 */
	private $product = false;

	/**
	 * The field to retrieve.
	 *
	 * @var string
	 */
	private $field;

	/**
	 * WooCommerce data handler.
	 *
	 * @var Data
	 */
	private $woo_data;

	/**
	 * Woocommerce constructor.
	 *
	 * @param \WP_Post $post The post object.
	 * @param string   $field The field to retrieve.
	 */
	public function __construct( $post, $field ) {
		parent::__construct();

		if ( ! $this->woocommerce_active() ) {
			return;
		}

		$this->product  = wc_get_product( $post );
		$this->field    = $field;
		$this->woo_data = new Data();
	}

	/**
	 * Retrieves the value of the specified field.
	 *
	 * @return mixed The value of the field.
	 */
	public function get_value() {
		if ( ! $this->woocommerce_active() || ! $this->product ) {
			return '';
		}

		$price               = $this->product->get_price();
		$is_variable_product = is_a( $this->product, '\WC_Product_Variable' );

		switch ( $this->field ) {
			case self::PRODUCT_ID:
				return $this->product->get_id();

			case self::SKU:
				return $this->product->get_sku();

			case self::GLOBAL_ID:
				return $this->get_global_id();

			case self::STOCK_STATUS:
				return $this->product->is_in_stock() ? 'InStock' : 'OutOfStock';

			case self::CURRENCY:
				return get_woocommerce_currency();

			case self::PRICE:
				return $price;

			case self::SALE_START_DATE:
				return $this->format_date( $this->product->get_date_on_sale_from() );

			case self::SALE_END_DATE:
				if ( $this->product->is_on_sale() && $this->product->get_date_on_sale_to() ) {
					return $this->format_date( $this->product->get_date_on_sale_to() );
				} else {
					return gmdate( 'Y-12-31', time() + YEAR_IN_SECONDS );
				}

			case self::MIN_PRICE:
				return $is_variable_product
					? $this->format_price( $this->product->get_variation_price( 'min', false ) )
					: $price;

			case self::MAX_PRICE:
				return $is_variable_product
					? $this->format_price( $this->product->get_variation_price( 'max', false ) )
					: $price;

			case self::PRODUCT_CHILDREN_COUNT:
				return count( $this->product->get_children() );

			case self::REVIEW_COUNT:
				return $this->product->get_review_count();

			case self::AVERAGE_RATING:
				return $this->product->get_average_rating();

			case self::PRODUCT_CATEGORY:
				$product_category = $this->get_object_term( 'product_cat' );
				return $product_category
					? $product_category->name
					: '';

			case self::PRODUCT_CATEGORY_URL:
				$product_category = $this->get_object_term( 'product_cat' );
				return $product_category
					? get_term_link( $product_category->term_id )
					: '';

			case self::PRODUCT_TAG:
				$product_tag = $this->get_object_term( 'product_tag' );
				return $product_tag
					? $product_tag->name
					: '';

			case self::PRODUCT_TAG_URL:
				$product_tag = $this->get_object_term( 'product_tag' );
				return $product_tag
					? get_term_link( $product_tag->term_id )
					: '';

			default:
				return '';
		}
	}

	/**
	 * Formats the price.
	 *
	 * @param float $price The price to format.
	 * @return string The formatted price.
	 */
	private function format_price( $price ) {
		return wc_format_decimal( $price, wc_get_price_decimals() );
	}

	/**
	 * Formats the date.
	 *
	 * @param object $date The date to format.
	 *
	 * @return string The formatted date.
	 */
	private function format_date( $date ) {
		return $date ? gmdate( 'Y-m-d', $date->getTimestamp() ) : '';
	}

	/**
	 * Checks if WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	private function woocommerce_active() {
		return \smartcrawl_woocommerce_active();
	}

	/**
	 * Retrieves the object term for the specified taxonomy.
	 *
	 * @param string $taxonomy The taxonomy to retrieve.
	 * @return array The object term.
	 */
	private function get_object_term( $taxonomy ) {
		$terms = wp_get_object_terms( $this->product->get_id(), $taxonomy );
		if ( is_wp_error( $terms ) ) {
			return null;
		}
		return \smartcrawl_get_array_value( $terms, 0 );
	}

	/**
	 * Retrieves the global ID.
	 *
	 * @return string The global ID.
	 */
	private function get_global_id() {
		$options        = $this->woo_data->get_options();
		$module_enabled = (bool) \smartcrawl_get_array_value( $options, 'active' );
		if ( ! $module_enabled ) {
			return '';
		}

		$global_id_key = (string) \smartcrawl_get_array_value( $options, 'global_id' );
		if ( ! $global_id_key ) {
			return '';
		}

		return $this->product->get_meta( \SmartCrawl\Modules\Advanced\Woocommerce\Global_Id::GLOBAL_ID_META_KEY );
	}
}