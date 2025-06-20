<?php
/**
 * Provider class for handling sitemap-related services in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

/**
 * Class Provider
 *
 * Handles the generation of sitemap URLs.
 */
class Provider extends \WP_Sitemaps_Provider {
	/**
	 * Query object for retrieving sitemap items.
	 *
	 * @var \SmartCrawl\Sitemaps\Query
	 */
	private $query;

	/**
	 * Constructor for the Provider class.
	 *
	 * @param string                     $name  The name of the provider.
	 * @param \SmartCrawl\Sitemaps\Query $query The query object.
	 */
	public function __construct( $name, $query ) {
		$this->name        = $name;
		$this->object_type = $name;

		$this->query = $query;
	}

	/**
	 * Retrieves the list of URLs for the sitemap.
	 *
	 * @param int    $page_num      The page number.
	 * @param string $object_subtype The object subtype.
	 *
	 * @return array|array[] The list of URLs.
	 */
	public function get_url_list( $page_num, $object_subtype = '' ) {
		$sitemap_items = $this->query->get_items( $object_subtype, $page_num );

		return array_map( array( $this, 'convert_to_array' ), $sitemap_items );
	}

	/**
	 * Retrieves the maximum number of pages for the sitemap.
	 *
	 * @param string $object_subtype The object subtype.
	 *
	 * @return int The maximum number of pages.
	 */
	public function get_max_num_pages( $object_subtype = '' ) {
		$index_items = $this->query->get_index_items();

		return count( $index_items );
	}

	/**
	 * Converts a sitemap item to an array.
	 *
	 * @param \SmartCrawl\Sitemaps\General\Item $sitemap_item The sitemap item.
	 *
	 * @return array The sitemap item as an array.
	 */
	private function convert_to_array( $sitemap_item ) {
		return array(
			'loc' => $sitemap_item->get_location(),
		);
	}
}