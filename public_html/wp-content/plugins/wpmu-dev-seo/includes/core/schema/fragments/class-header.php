<?php
/**
 * Header class for handling header schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Header
 *
 * Handles header schema fragments.
 */
class Header extends Fragment {

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The URL of the header.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The title of the header.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the header.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Header constructor.
	 *
	 * @param string $url The URL of the header.
	 * @param string $title The title of the header.
	 * @param string $description The description of the header.
	 */
	public function __construct( $url, $title, $description ) {
		$this->url         = $url;
		$this->title       = $title;
		$this->description = $description;
		$this->utils       = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array|false The raw schema data or false if header/footer schema is disabled.
	 */
	protected function get_raw() {
		$enable_header_footer = (bool) $this->utils->get_schema_option( 'schema_wp_header_footer' );
		if ( ! $enable_header_footer ) {
			return false;
		}

		return array(
			'@type'       => 'WPHeader',
			'url'         => $this->url,
			'headline'    => $this->title,
			'description' => $this->description,
		);
	}
}