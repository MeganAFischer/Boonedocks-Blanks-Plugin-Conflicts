<?php
/**
 * Product Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

use SmartCrawl\Integration\Woocommerce\Api;
use SmartCrawl\Integration\Woocommerce\Data;

/**
 * Product Entity class.
 */
class Product extends Entity {
	/**
	 * WP Post object.
	 *
	 * @var Post
	 */
	private $post;
	/**
	 * WooCommerce Product object.
	 *
	 * @var \WC_Product|false
	 */
	private $woo_product;
	/**
	 * Product Brand.
	 *
	 * @var \WP_Term|false
	 */
	private $brand;
	/**
	 * Product data.
	 *
	 * @var \SmartCrawl\Integration\Woocommerce\Data
	 */
	private $data;
	/**
	 * WooCommerce API provider.
	 *
	 * @var \SmartCrawl\Integration\Woocommerce\Api
	 */
	private $woo_api;

	/**
	 * Class constructor.
	 *
	 * @param \WP_Post|int $wp_post The WordPress post object.
	 * @param int          $page_number The page number.
	 * @param int          $comments_page The comments page number.
	 */
	public function __construct( $wp_post, $page_number = 0, $comments_page = 0 ) {
		$this->post    = new Post( $wp_post, $page_number, $comments_page );
		$this->data    = new Data();
		$this->woo_api = new Api();
	}

	/**
	 * Retrieves WooCommerce product.
	 *
	 * @return \WC_Product|false The WooCommerce product object or false if not found.
	 */
	private function get_woo_product() {
		$wp_post = $this->post->get_wp_post();

		if (
			! $wp_post
			|| ! \smartcrawl_woocommerce_active()
			|| ! $this->is_woo_module_enabled()
		) {
			return false;
		}

		if ( is_null( $this->woo_product ) ) {
			$this->woo_product = $this->woo_api->wc_get_product( $wp_post );
		}

		return $this->woo_product ? $this->woo_product : false;
	}

	/**
	 * Loads the meta title for the post.
	 *
	 * @return string The meta title for the post.
	 */
	protected function load_meta_title() {
		return $this->post->get_meta_title();
	}

	/**
	 * Loads the meta description from the post object.
	 *
	 * @return string The meta description.
	 */
	protected function load_meta_description() {
		return $this->post->get_meta_description();
	}

	/**
	 * Loads robots meta tag.
	 *
	 * @return string The robots meta tag value.
	 */
	protected function load_robots() {
		$woo_product         = $this->get_woo_product();
		$noindex_hidden_prod = \smartcrawl_get_array_value( $this->get_options(), 'noindex_hidden_prod' );

		if (
			$woo_product
			&& $woo_product->get_catalog_visibility() === 'hidden'
			&& $noindex_hidden_prod
		) {
			return 'noindex,nofollow';
		}

		return $this->post->get_robots();
	}

	/**
	 * Loads the canonical URL from the post object.
	 *
	 * @return string The canonical URL.
	 */
	protected function load_canonical_url() {
		return $this->post->get_canonical_url();
	}

	/**
	 * Loads the schema for the post object.
	 *
	 * @return array The schema data.
	 */
	protected function load_schema() {
		// Notice that we are not checking Woo module status here because schema is not dependent on that.
		$wp_post = $this->post->get_wp_post();
		if ( ! $wp_post ) {
			return array();
		}

		$fragment = new \SmartCrawl\Schema\Fragments\Singular( $this->post, false );

		return $fragment->get_schema();
	}

	/**
	 * Retrieves the OpenGraph enabled status of the post object.
	 *
	 * @return bool The OpenGraph enabled status of the post object.
	 */
	protected function load_opengraph_enabled() {
		return $this->post->is_opengraph_enabled();
	}

	/**
	 * Loads the OpenGraph title from the post object.
	 *
	 * @return string The OpenGraph title.
	 */
	protected function load_opengraph_title() {
		return $this->post->get_opengraph_title();
	}

	/**
	 * Load the OpenGraph description from the post object.
	 *
	 * @return string The OpenGraph description of the post.
	 */
	protected function load_opengraph_description() {
		return $this->post->get_opengraph_description();
	}

	/**
	 * Loads OpenGraph images from the post object.
	 *
	 * @return array The array of OpenGraph images.
	 */
	protected function load_opengraph_images() {
		return $this->post->get_opengraph_images();
	}

	/**
	 * Loads the Twitter enabled status from the post object.
	 *
	 * @return bool The Twitter enabled status.
	 */
	protected function load_twitter_enabled() {
		return $this->post->is_twitter_enabled();
	}

	/**
	 * Loads the Twitter title for the post object.
	 *
	 * @return string The Twitter title for the post object.
	 */
	protected function load_twitter_title() {
		return $this->post->get_twitter_title();
	}

	/**
	 * Loads the Twitter description from the post object.
	 *
	 * @return string The Twitter description for the post object.
	 */
	protected function load_twitter_description() {
		return $this->post->get_twitter_description();
	}

	/**
	 * Loads Twitter images from the post object.
	 *
	 * @return array The Twitter images from the post object.
	 */
	protected function load_twitter_images() {
		return $this->post->get_twitter_images();
	}

	/**
	 * Retrieves the options from the data object.
	 *
	 * @return array The options.
	 */
	private function get_options() {
		return $this->data->get_options();
	}

	/**
	 * Retrieves the brand for the product.
	 *
	 * If the brand is null, it is loaded by calling the load_brand method and stored in the brand property.
	 *
	 * @return \WP_Term|false The brand for the product.
	 */
	public function get_brand() {
		if ( ! $this->brand ) {
			$this->brand = $this->load_brand();
		}

		return $this->brand;
	}

	/**
	 * Loads the brand for the product.
	 *
	 * @return \WP_Term|false Returns the brand term object if found, otherwise false.
	 */
	private function load_brand() {
		$woo_product = $this->get_woo_product();

		if ( ! $woo_product ) {
			return false;
		}

		$brand = \smartcrawl_get_array_value( $this->get_options(), 'brand' );

		if ( empty( $brand ) ) {
			return false;
		}

		$brands = get_the_terms( $woo_product->get_id(), $brand );

		return is_wp_error( $brands ) || empty( $brands[0] ) ? false : $brands[0];
	}

	/**
	 * Loads OpenGraph tags.
	 *
	 * @return array The array of OpenGraph tags.
	 */
	public function load_opengraph_tags() {
		$tags = array();

		$woo_product    = $this->get_woo_product();
		$woo_og_enabled = (bool) \smartcrawl_get_array_value( $this->get_options(), 'enable_og' );

		if ( $woo_product && $woo_og_enabled ) {
			$tags            = parent::load_opengraph_tags();
			$tags['og:type'] = 'og:product';

			$price = $this->get_opengraph_product_price();
			if ( $price ) {
				$tags['product:price:amount']   = $price;
				$tags['product:price:currency'] = $this->woo_api->get_woocommerce_currency();
			}

			$tags = $this->add_opengraph_availability( $tags );

			$brand = $this->get_brand();

			if ( $brand ) {
				$tags['product:brand'] = $brand->name;
			}
		}

		return $tags;
	}

	/**
	 * Retrieves the OpenGraph product price.
	 *
	 * @return string The formatted product price.
	 */
	private function get_opengraph_product_price() {
		$woo_product = $this->get_woo_product();
		if ( ! $woo_product ) {
			return '';
		}

		$price = $woo_product->get_price();

		if ( '' === $price ) {
			return '';
		}

		if ( $woo_product->is_type( 'variable' ) ) {
			$lowest  = $woo_product->get_variation_price( 'min', false );
			$highest = $woo_product->get_variation_price( 'max', false );

			return $lowest === $highest
				? $this->woo_api->wc_format_decimal( $lowest, $this->woo_api->wc_get_price_decimals() )
				: '';
		} else {
			return $this->woo_api->wc_format_decimal( $price, $this->woo_api->wc_get_price_decimals() );
		}
	}

	/**
	 * Adds OpenGraph availability tags to existing tags.
	 *
	 * @param array $tags The existing OpenGraph tags.
	 *
	 * @return array The modified OpenGraph tags.
	 */
	private function add_opengraph_availability( $tags ) {
		$woo_product = $this->get_woo_product();

		if ( ! $woo_product ) {
			return $tags;
		}

		$og_availability      = false;
		$product_availability = false;

		$stock_status = $woo_product->get_stock_status();

		if ( 'onbackorder' === $stock_status ) {
			$product_availability = 'available for order';
			$og_availability      = 'backorder';
		} elseif ( 'instock' === $stock_status ) {
			$og_availability      = 'instock';
			$product_availability = 'instock';
		} elseif ( 'outofstock' === $stock_status ) {
			$og_availability      = 'out of stock';
			$product_availability = 'out of stock';
		}

		if ( $og_availability ) {
			$tags['og:availability'] = $og_availability;
		}
		if ( $product_availability ) {
			$tags['product:availability'] = $product_availability;
		}

		return $tags;
	}

	/**
	 * Retrieves the macros for a given subject.
	 *
	 * @param string $subject The subject to search for macros.
	 *
	 * @return array The macros for the given subject.
	 */
	public function get_macros( $subject = '' ) {
		return $this->post->get_macros( $subject );
	}

	/**
	 * Checks if the WooCommerce is enabled.
	 *
	 * @return bool Returns true if the Woo module is enabled, false otherwise.
	 */
	private function is_woo_module_enabled() {
		return (bool) \smartcrawl_get_array_value( $this->get_options(), 'active' );
	}

	/**
	 * Retrieves the WooCommerce API.
	 *
	 * @return \SmartCrawl\Integration\Woocommerce\Api
	 */
	public function get_woo_api() {
		return $this->woo_api;
	}

	/**
	 * Sets the WooCommerce API.
	 *
	 * @param object $woo_api The WooCommerce API object.
	 */
	public function set_woo_api( $woo_api ) {
		$this->woo_api = $woo_api;
	}
}