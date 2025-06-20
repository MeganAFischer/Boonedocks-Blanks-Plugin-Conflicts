<?php
/**
 * WooCommerce Shop Page Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

use SmartCrawl\Integration\Woocommerce\Api;

/**
 * TODO: woo shop is not just a page, product post type archive can also be a shop
 */

/**
 * Woo_Shop_Page Entity class.
 */
class Woo_Shop_Page extends Post {
	/**
	 * WooCommerce API provider.
	 *
	 * @var Api
	 */
	private $woo_api;
	/**
	 * WP Post objects.
	 *
	 * @var array
	 */
	private $posts;

	/**
	 * Constructor.
	 *
	 * @param \WP_Post[] $posts   Posts.
	 * @param Api        $woo_api Woo API.
	 */
	public function __construct( $posts = array(), $woo_api = null ) {
		if ( ! $woo_api ) {
			$woo_api = new Api();
		}

		parent::__construct( $woo_api->wc_get_page_id( 'shop' ) );

		$this->woo_api = $woo_api;
		$this->posts   = $posts;
	}

	/**
	 * Loads schema.
	 *
	 * @return array The schema.
	 */
	protected function load_schema() {
		$wp_posts = $this->get_wp_post();

		if ( ! $wp_posts ) {
			return array();
		}

		$archive = new \SmartCrawl\Schema\Fragments\Woo_Shop(
			$this->woo_api->wc_get_page_permalink( 'shop' ),
			$this->posts,
			$this->get_meta_title(),
			$this->get_meta_description()
		);

		return $archive->get_schema();
	}
}