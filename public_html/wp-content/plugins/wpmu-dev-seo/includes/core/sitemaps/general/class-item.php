<?php
/**
 * Item class for handling sitemap items in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\General;

use SmartCrawl\Sitemaps\Index_Item;

/**
 * Class Item
 *
 * Represents an item in the sitemap.
 */
class Item extends Index_Item {

	/**
	 * The images associated with the item.
	 *
	 * @var array
	 */
	private $images = array();

	/**
	 * Retrieves the images associated with the item.
	 *
	 * @return array The images.
	 */
	public function get_images() {
		return $this->images;
	}

	/**
	 * Sets the images associated with the item.
	 *
	 * @param array $images The images to set.
	 *
	 * @return $this
	 */
	public function set_images( $images ) {
		$this->images = $images;

		return $this;
	}

	/**
	 * Converts the images to XML format.
	 *
	 * @return string The images in XML format.
	 */
	private function images_xml() {
		$images = array();
		foreach ( $this->get_images() as $image ) {
			$images[] = $this->image_xml( $image );
		}
		return join( "\n", $images );
	}

	/**
	 * Converts a single image to XML format.
	 *
	 * @param array $image The image to convert.
	 *
	 * @return string The image in XML format.
	 */
	private function image_xml( $image ) {
		$text = ! empty( $image['title'] )
			? $image['title']
			: (string) \smartcrawl_get_array_value( $image, 'alt' );
		$src  = (string) \smartcrawl_get_array_value( $image, 'src' );

		$image_tag  = '<image:image>';
		$image_tag .= '<image:loc>' . esc_url( $src ) . '</image:loc>';
		$image_tag .= '<image:title>' . esc_xml( $text ) . '</image:title>';
		$image_tag .= '</image:image>';

		return $image_tag;
	}

	/**
	 * Converts the item to XML format.
	 *
	 * @return string The item in XML format.
	 */
	public function to_xml() {
		$tags = array();

		$location = $this->get_location();
		if ( empty( $location ) ) {
			\SmartCrawl\Logger::error( 'Sitemap item with empty location found' );
			return '';
		}

		$tags[] = sprintf( '<loc>%s</loc>', esc_url( $location ) );

		// Last modified date.
		$tags[] = sprintf( '<lastmod>%s</lastmod>', $this->format_timestamp( $this->get_last_modified() ) );

		// Images.
		$images = $this->images_xml();
		if ( ! empty( $images ) ) {
			$tags[] = $images;
		}

		return sprintf( "<url>\n%s\n</url>", implode( "\n", $tags ) );
	}
}