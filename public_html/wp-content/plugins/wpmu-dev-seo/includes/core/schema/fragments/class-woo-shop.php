<?php
/**
 * Woo\_Shop class for handling WooCommerce shop schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Integration\Woocommerce\Data;
use SmartCrawl\Schema\Utils;

/**
 * Class Woo\_Shop
 *
 * Handles WooCommerce shop schema fragments.
 */
class Woo_Shop extends Fragment {

	/**
	 * The URL of the shop.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The posts related to the shop.
	 *
	 * @var array
	 */
	private $posts;

	/**
	 * The title of the shop.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the shop.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * WooCommerce data handler.
	 *
	 * @var Data
	 */
	private $data;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Woo\_Shop constructor.
	 *
	 * @param string $url The URL of the shop.
	 * @param array  $posts The posts related to the shop.
	 * @param string $title The title of the shop.
	 * @param string $description The description of the shop.
	 */
	public function __construct( $url, $posts, $title, $description ) {
		$this->url         = $url;
		$this->posts       = $posts;
		$this->title       = $title;
		$this->description = $description;
		$this->data        = new Data();
		$this->utils       = Utils::get();
	}

	/**
	 * Retrieves the WooCommerce options.
	 *
	 * @return array The WooCommerce options.
	 */
	private function get_options() {
		return $this->data->get_options();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array|mixed|Archive The raw schema data.
	 */
	protected function get_raw() {
		$woo_enabled = (bool) \smartcrawl_get_array_value( $this->get_options(), 'active' );
		$shop_schema = (bool) \smartcrawl_get_array_value( $this->get_options(), 'shop_schema' );

		if ( $woo_enabled && $shop_schema ) {
			return new Archive(
				'CollectionPage',
				$this->url,
				$this->posts,
				$this->title,
				$this->description
			);
		} else {
			$custom_schema_types = $this->utils->get_custom_schema_types();
			if ( $custom_schema_types ) {
				return $this->utils->add_custom_schema_types(
					array(),
					$custom_schema_types,
					$this->utils->get_webpage_id( $this->url )
				);
			} else {
				return array();
			}
		}
	}
}