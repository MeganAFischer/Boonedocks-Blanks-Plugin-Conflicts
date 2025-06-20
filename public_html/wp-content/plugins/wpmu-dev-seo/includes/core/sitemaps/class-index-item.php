<?php
/**
 * Sitemap index item.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

/**
 * Index_Item class
 */
class Index_Item {
	/**
	 * Item location.
	 *
	 * @var string
	 */
	private $location = '';

	/**
	 * Last modified timestamp.
	 *
	 * @var int
	 */
	private $last_modified = 0;

	/**
	 * Get location.
	 *
	 * @return string
	 */
	public function get_location() {
		return urldecode( $this->location );
	}

	/**
	 * Set location.
	 *
	 * @param string $location Location.
	 *
	 * @return Index_Item
	 */
	public function set_location( $location ) {
		$this->location = $location;
		return $this;
	}

	/**
	 * Get last modified timestamp.
	 *
	 * @return int
	 */
	public function get_last_modified() {
		return $this->last_modified;
	}

	/**
	 * Set last modified timestamp.
	 *
	 * @param int $last_modified Last modified timestamp.
	 *
	 * @return Index_Item
	 */
	public function set_last_modified( $last_modified ) {
		$this->last_modified = $last_modified;
		return $this;
	}

	/**
	 * Format timestamp.
	 *
	 * @param int $timestamp Timestamp.
	 *
	 * @return string
	 */
	protected function format_timestamp( $timestamp ) {
		return Utils::format_timestamp( $timestamp );
	}

	/**
	 * Retrieve xml format of sitemap.
	 *
	 * @return string
	 */
	public function to_xml() {
		$tags = array();

		$location = $this->get_location();
		if ( empty( $location ) ) {
			\SmartCrawl\Logger::error( 'Index item with empty location found' );
			return '';
		}

		$tags[] = sprintf( '<loc>%s</loc>', esc_url( $location ) );

		return sprintf( '<sitemap>%s</sitemap>', implode( '', $tags ) );
	}
}