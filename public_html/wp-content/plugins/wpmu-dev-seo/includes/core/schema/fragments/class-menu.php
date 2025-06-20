<?php
/**
 * Menu class for handling menu schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Menu
 *
 * Handles menu schema fragments.
 */
class Menu extends Fragment {

	/**
	 * The URL of the menu.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Menu constructor.
	 *
	 * @param string $url The URL of the menu.
	 */
	public function __construct( $url ) {
		$this->url   = $url;
		$this->utils = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array|false The raw schema data or false if the main menu is not set.
	 */
	protected function get_raw() {
		$main_menu_slug = $this->utils->get_schema_option( 'schema_main_navigation_menu' );
		if ( empty( $main_menu_slug ) ) {
			return false;
		}

		$menu_items = wp_get_nav_menu_items( $main_menu_slug );
		if ( empty( $menu_items ) || ! is_array( $menu_items ) ) {
			return false;
		}

		$schema = array();
		foreach ( $menu_items as $menu_item ) {
			/**
			 * Menu item object.
			 *
			 * @var $menu_item \WP_Post
			 */
			$schema[] = array(
				'@type' => 'SiteNavigationElement',
				'@id'   => $this->utils->url_to_id( $this->url, '#schema-nav-element-' . $menu_item->ID ),
				'name'  => $menu_item->post_title,
				'url'   => $menu_item->url,
			);
		}

		return $schema;
	}
}