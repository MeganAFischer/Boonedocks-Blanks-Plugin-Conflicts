<?php
/**
 * Abstract class for Archive Entities.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

/**
 * Abstract Class Entity_With_Archive.
 */
abstract class Entity_With_Archive extends Entity {

	/**
	 * Appends the page number to the given URL
	 *
	 * @param string $url The URL to append the page number to.
	 * @param int    $page_number The page number to be appended.
	 *
	 * @return string The updated URL with the appended page number.
	 */
	protected function append_page_number( $url, $page_number ) {
		return \smartcrawl_append_archive_page_number( $url, $page_number );
	}

	/**
	 * Retrieves the robots metadata for a specific page number.
	 *
	 * @param int $page_number The page number to retrieve the robots metadata for.
	 *
	 * @return string
	 */
	abstract protected function get_robots_for_page_number( $page_number );

	/**
	 * Checks if the first page is indexed by checking the robots meta tag.
	 *
	 * @return bool Returns true if the first page is indexed, false otherwise.
	 */
	protected function is_first_page_indexed() {
		$first_page_robots = $this->get_robots_for_page_number( 1 );

		return strpos( $first_page_robots, 'noindex' ) === false;
	}
}