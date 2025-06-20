<?php
/**
 * Footer class for handling footer schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Footer
 *
 * Handles footer schema fragments.
 */
class Footer extends Fragment {

	/**
	 * The URL of the footer.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The title of the footer.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the footer.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Footer constructor.
	 *
	 * @param string $url The URL of the footer.
	 * @param string $title The title of the footer.
	 * @param string $description The description of the footer.
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
	 * @return array|false The raw schema data.
	 */
	protected function get_raw() {
		$enable_header_footer = (bool) $this->utils->get_schema_option( 'schema_wp_header_footer' );
		if ( ! $enable_header_footer ) {
			return false;
		}

		return array(
			'@type'         => 'WPFooter',
			'url'           => $this->url,
			'headline'      => $this->title,
			'description'   => $this->description,
			'copyrightYear' => gmdate( 'Y' ),
		);
	}
}