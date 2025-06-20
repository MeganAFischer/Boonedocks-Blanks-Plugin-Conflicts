<?php
/**
 * Blog_Home class for handling blog home schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Blog_Home
 *
 * Schema for traditional blog style home page
 */
class Blog_Home extends Fragment {

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The URL of the site.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The title of the blog home page.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the blog home page.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Blog_Home constructor.
	 *
	 * @param string $title The title of the blog home page.
	 * @param string $description The description of the blog home page.
	 */
	public function __construct( $title, $description ) {
		$this->title       = $title;
		$this->description = $description;
		$this->url         = get_site_url();
		$this->utils       = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array The raw schema data.
	 */
	protected function get_raw() {
		$is_publisher_page = $this->is_publisher_output_page();

		$publisher = new Publisher( $is_publisher_page );
		$schema    = array(
			new Header( $this->url, $this->title, $this->description ),
			new Footer( $this->url, $this->title, $this->description ),
			$publisher,
			new Website(),
			new Breadcrumb(),
		);

		if ( $is_publisher_page && $this->utils->is_schema_type_person() ) {
			$schema[] = new Publishing_Person( $publisher->get_publisher_url() );
		}

		$custom_schema_types = $this->utils->get_custom_schema_types( null, true );
		if ( $custom_schema_types ) {
			$webpage_id = $this->utils->get_webpage_id( $this->url );

			$schema[] = new Minimal_Webpage(
				$this->url,
				$publisher->get_publisher_id()
			);

			$schema = $this->utils->add_custom_schema_types(
				$schema,
				$custom_schema_types,
				$webpage_id
			);
		} else {
			$schema[] = new \SmartCrawl\Schema\Fragments\Blog_Home_Webpage(
				$this->title,
				$this->description,
				$publisher->get_publisher_id()
			);
		}

		return $schema;
	}

	/**
	 * Checks if the current page is the publisher output page.
	 *
	 * @return bool True if the current page is the publisher output page, false otherwise.
	 */
	private function is_publisher_output_page() {
		$publisher_output_page = $this->utils->get_special_page( 'schema_output_page' );

		// We are on the home page which is the default schema_output_page if another page has not been specified.
		return ! $publisher_output_page;
	}
}