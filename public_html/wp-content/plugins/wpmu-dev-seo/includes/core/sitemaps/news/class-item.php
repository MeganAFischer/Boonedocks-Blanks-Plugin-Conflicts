<?php
/**
 * Sitemap news item.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\News;

/**
 * Item class
 */
class Item {

	/**
	 * Item title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Item location.
	 *
	 * @var string
	 */
	private $location = '';

	/**
	 * Google News publication timestamp.
	 *
	 * @var int
	 */
	private $publication_time = 0;

	/**
	 * Google News publication.
	 *
	 * @var string
	 */
	private $publication = '';

	/**
	 * Language.
	 *
	 * @var string
	 */
	private $language;

	/**
	 * Get title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Set title.
	 *
	 * @param string $title Title.
	 *
	 * @return Item
	 */
	public function set_title( $title ) {
		$this->title = $title;

		return $this;
	}

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
	 * @return Item
	 */
	public function set_location( $location ) {
		$this->location = $location;

		return $this;
	}

	/**
	 * Get publication timestamp.
	 *
	 * @return int
	 */
	public function get_publication_time() {
		return $this->publication_time;
	}

	/**
	 * Set publication timestamp.
	 *
	 * @param int $publication_time Publication timestamp.
	 *
	 * @return Item
	 */
	public function set_publication_time( $publication_time ) {
		$this->publication_time = $publication_time;

		return $this;
	}

	/**
	 * Get publication.
	 *
	 * @return string
	 */
	public function get_publication() {
		return $this->publication;
	}

	/**
	 * Set publication.
	 *
	 * @param string $publication Publication name.
	 *
	 * @return Item
	 */
	public function set_publication( $publication ) {
		$this->publication = $publication;

		return $this;
	}

	/**
	 * Set language.
	 *
	 * @param string $language Language.
	 *
	 * @return void
	 */
	public function set_language( $language ) {
		$this->language = $language;
	}

	/**
	 * Get language.
	 *
	 * @return string
	 */
	public function get_language() {
		return $this->language;
	}

	/**
	 * Retrieve xml format of sitemap.
	 *
	 * @return string
	 */
	public function to_xml() {
		return sprintf(
			'<url>
				<loc>%s</loc>
				<news:news>
					<news:publication>
						<news:name>%s</news:name>
						<news:language>%s</news:language>
					</news:publication>
					<news:publication_date>%s</news:publication_date>
					<news:title>%s</news:title>
				</news:news>
			</url>',
			esc_url( $this->get_location() ),
			esc_xml( $this->get_publication() ),
			esc_xml( $this->get_language() ),
			\SmartCrawl\Sitemaps\Utils::format_timestamp( $this->get_publication_time() ),
			esc_xml( $this->get_title() )
		);
	}
}