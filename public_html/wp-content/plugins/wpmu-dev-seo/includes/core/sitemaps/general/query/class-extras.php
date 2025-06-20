<?php
/**
 * Extras class for handling extra sitemap queries in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\General\Queries;

use SmartCrawl\Singleton;
use SmartCrawl\Sitemaps\General\Item;
use SmartCrawl\Sitemaps\Query;

/**
 * Class Extras
 *
 * Handles the retrieval of extra sitemap items.
 */
class Extras extends Query {

	use Singleton;

	const EXTRAS         = 'extras';
	const EXTRAS_STORAGE = 'wds-sitemap-extras';

	/**
	 * Retrieves the supported types.
	 *
	 * @return string[] The supported types.
	 */
	public function get_supported_types() {
		return array( self::EXTRAS );
	}

	/**
	 * Retrieves the items for the given type and page number.
	 *
	 * @param string $type The type of items to retrieve.
	 * @param int    $page_number The page number for pagination.
	 *
	 * @return array|Item[] The array of sitemap items.
	 */
	public function get_items( $type = '', $page_number = 0 ) {
		$extras = get_option( self::EXTRAS_STORAGE );
		$extras = empty( $extras ) || ! is_array( $extras )
			? array()
			: $extras;

		if ( ! empty( $page_number ) ) {
			$limit  = $this->get_limit( $page_number );
			$offset = $this->get_offset( $page_number );
			$extras = array_slice( $extras, $offset, $limit );
		}

		$items = array();
		foreach ( $extras as $extra_url ) {
			if ( \SmartCrawl\Sitemaps\Utils::is_url_ignored( $extra_url ) ) {
				continue;
			}

			$item = new Item();
			$item->set_location( $extra_url )
				->set_last_modified( time() );

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Retrieves the filter prefix.
	 *
	 * @return string The filter prefix.
	 */
	public function get_filter_prefix() {
		return 'wds-sitemap-extras';
	}
}