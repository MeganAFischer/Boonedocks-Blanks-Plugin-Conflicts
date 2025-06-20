<?php
/**
 * Minimal_Webpage class for handling minimal webpage schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Minimal_Webpage
 *
 * Handles minimal webpage schema fragments.
 */
class Minimal_Webpage extends Fragment {

	/**
	 * The URL of the webpage.
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
	 * The publisher ID.
	 *
	 * @var string
	 */
	private $publisher_id;

	/**
	 * Minimal_Webpage constructor.
	 *
	 * @param string $url The URL of the webpage.
	 * @param string $publisher_id The publisher ID.
	 */
	public function __construct( $url, $publisher_id ) {
		$this->url          = $url;
		$this->publisher_id = $publisher_id;
		$this->utils        = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array The raw schema data.
	 */
	protected function get_raw() {
		return array(
			'@type'     => 'WebPage',
			'@id'       => $this->utils->get_webpage_id( $this->url ),
			'isPartOf'  => array(
				'@id' => $this->utils->get_website_id(),
			),
			'publisher' => array(
				'@id' => $this->publisher_id,
			),
			'url'       => $this->url,
			'hasPart'   => new Menu( $this->url ),
		);
	}
}