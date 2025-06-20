<?php
/**
 * Search class for handling search schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Search
 *
 * Handles search schema fragments.
 */
class Search extends Fragment {

	/**
	 * The search term.
	 *
	 * @var string
	 */
	private $search_term;

	/**
	 * The posts related to the search.
	 *
	 * @var \WP_Post[]
	 */
	private $posts;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The title of the search results.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the search results.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Search constructor.
	 *
	 * @param string     $search_term The search term.
	 * @param \WP_Post[] $posts The posts related to the search.
	 * @param string     $title The title of the search results.
	 * @param string     $description The description of the search results.
	 */
	public function __construct( $search_term, $posts, $title, $description ) {
		$this->search_term = $search_term;
		$this->posts       = $posts;
		$this->title       = $title;
		$this->description = $description;
		$this->utils       = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array|mixed|Archive The raw schema data.
	 */
	protected function get_raw() {
		$enabled    = (bool) $this->utils->get_schema_option( 'schema_enable_search' );
		$search_url = get_search_link( $this->search_term );

		if ( $enabled ) {
			return new Archive(
				'SearchResultsPage',
				$search_url,
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
					$this->utils->get_webpage_id( $search_url )
				);
			} else {
				return array();
			}
		}
	}
}