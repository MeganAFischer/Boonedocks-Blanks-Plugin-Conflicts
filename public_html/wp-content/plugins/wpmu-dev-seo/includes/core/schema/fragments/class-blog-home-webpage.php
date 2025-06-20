<?php
/**
 * Blog_Home_Webpage class for handling the schema of the blog home webpage in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;
use SmartCrawl\Cache\Object_Cache;

/**
 * Class Blog_Home_Webpage
 *
 * Handles the schema of the blog home webpage.
 */
class Blog_Home_Webpage extends Fragment {

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The publisher ID.
	 *
	 * @var int
	 */
	private $publisher_id;

	/**
	 * The title of the webpage.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the webpage.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Blog_Home_Webpage constructor.
	 *
	 * @param string $title The title of the webpage.
	 * @param string $description The description of the webpage.
	 * @param int    $publisher_id The publisher ID.
	 */
	public function __construct( $title, $description, $publisher_id ) {
		$this->title        = $title;
		$this->description  = $description;
		$this->publisher_id = $publisher_id;
		$this->utils        = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array The raw schema data.
	 */
	protected function get_raw() {
		$site_url = get_site_url();
		$schema   = array(
			'@type'      => 'WebPage',
			'@id'        => $this->utils->get_webpage_id( $site_url ),
			'url'        => $site_url,
			'name'       => $this->title,
			'inLanguage' => get_bloginfo( 'language' ),
			'isPartOf'   => array(
				'@id' => $this->utils->get_website_id(),
			),
			'publisher'  => array(
				'@id' => $this->publisher_id,
			),
		);

		if ( $this->description ) {
			$schema['description'] = $this->utils->apply_filters( 'site-data-description', $this->description );
		}

		$last_post_date = get_lastpostmodified( 'blog' );
		if ( $last_post_date ) {
			$schema['dateModified'] = $last_post_date;
		}

		$schema['hasPart'] = new Menu( $site_url );

		return $schema;
	}
}