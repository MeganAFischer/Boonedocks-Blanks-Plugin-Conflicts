<?php
/**
 * Abstract class Query for handling sitemap queries in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

use SmartCrawl\Work_Unit;

/**
 * Class Query
 *
 * Abstract class for handling sitemap queries.
 */
abstract class Query extends Work_Unit {

	const NO_LIMIT = PHP_INT_MAX;

	/**
	 * Retrieves the items for the given type and page number.
	 *
	 * @param string $type The type of items to retrieve.
	 * @param int    $page_number The page number for pagination.
	 *
	 * @return General\Item[] Array of sitemap items.
	 */
	abstract public function get_items( $type = '', $page_number = 0 );

	/**
	 * Retrieves the item count for the given type.
	 *
	 * @param string $type The type of items to count.
	 *
	 * @return int The item count.
	 */
	public function get_item_count( $type = '' ) {
		return count( $this->get_items( $type ) );
	}

	/**
	 * Checks if the type can be handled.
	 *
	 * @param string $type The type to check.
	 *
	 * @return bool True if the type can be handled, false otherwise.
	 */
	public function can_handle_type( $type ) {
		$allowed = $this->get_supported_types();

		return in_array( $type, $allowed, true );
	}

	/**
	 * Retrieves the supported types.
	 *
	 * @return mixed The supported types.
	 */
	abstract public function get_supported_types();

	/**
	 * Retrieves the limit for the given page number.
	 *
	 * @param int $page_number The page number for pagination.
	 *
	 * @return int The limit.
	 */
	protected function get_limit( $page_number ) {
		if ( 0 === $page_number ) { // 0 means all items are requested.
			return self::NO_LIMIT;
		}

		// Otherwise return the limit based on page number.
		return Utils::get_items_per_sitemap();
	}

	/**
	 * Retrieves the offset for the given page number.
	 *
	 * @param int $page_number The page number for pagination.
	 *
	 * @return float|int The offset.
	 */
	protected function get_offset( $page_number ) {
		return $page_number > 1
			? ( $page_number - 1 ) * Utils::get_items_per_sitemap()
			: 0;
	}

	/**
	 * Finds images in the given haystack.
	 *
	 * @param string $haystack The string to search for images.
	 *
	 * @return array The found images.
	 */
	protected function find_images( $haystack ) {
		preg_match_all( '|(<img [^>]+?>)|', $haystack, $matches, PREG_SET_ORDER );
		if ( ! $matches ) {
			return array();
		}

		$images = array();
		foreach ( $matches as $tmp ) {
			$img = $tmp[0];

			$res = preg_match( '/src=(["\'])([^"\']+)(["\'])/', $img, $match );
			$src = $res ? $match[2] : '';
			if ( strpos( $src, 'http' ) !== 0 ) {
				$src = site_url( $src );
			}

			$res   = preg_match( '/title=(["\'])([^"\']+)(["\'])/', $img, $match );
			$title = $res ? str_replace( '-', ' ', str_replace( '_', ' ', $match[2] ) ) : '';

			$res = preg_match( '/alt=(["\'])([^"\']+)(["\'])/', $img, $match );
			$alt = $res ? str_replace( '-', ' ', str_replace( '_', ' ', $match[2] ) ) : '';

			$images[] = array(
				'src'   => $src,
				'title' => $title,
				'alt'   => $alt,
			);
		}

		return $images;
	}

	/**
	 * Retrieves the index items.
	 *
	 * @return Index_Item[] The index items.
	 */
	public function get_index_items() {
		$types       = $this->get_supported_types();
		$index_items = array();
		foreach ( $types as $type ) {
			$index_items_for_type = $this->get_index_items_for_type( $type );

			$index_items = array_merge(
				$index_items,
				$index_items_for_type
			);
		}

		return $index_items;
	}

	/**
	 * Retrieves the index items for the given type.
	 *
	 * @param string $type The type of items.
	 *
	 * @return array The index items.
	 */
	protected function get_index_items_for_type( $type ) {
		return $this->make_index_items( $type );
	}

	/**
	 * Retrieves the index item URL for the given type and sitemap number.
	 *
	 * @param string $type The type of items.
	 * @param int    $sitemap_num The sitemap number.
	 *
	 * @return string The index item URL.
	 */
	protected function get_index_item_url( $type, $sitemap_num ) {
		return home_url( "/$type-sitemap$sitemap_num.xml" );
	}

	/**
	 * Creates index items for the given type.
	 *
	 * @param string $type The type of items.
	 *
	 * @return array The index items.
	 */
	protected function make_index_items( $type ) {
		$per_sitemap = Utils::get_items_per_sitemap();
		$item_count  = $this->get_item_count( $type );
		if ( empty( $per_sitemap ) ) {
			return array();
		}

		$sitemap_count = (int) ceil( $item_count / $per_sitemap );
		$index_items   = array();

		for ( $sitemap_num = 1; $sitemap_num <= $sitemap_count; $sitemap_num++ ) {
			$location = $this->get_index_item_url( $type, $sitemap_num );

			$index_item = new Index_Item();
			$index_item->set_location( $location );

			$index_items[] = $index_item;
		}

		return $index_items;
	}
}