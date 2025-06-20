<?php
/**
 * Blog Home Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

/**
 * Blog_Home Entiry.
 */
class Blog_Home extends Entity_With_Archive {
	/**
	 * Page number.
	 *
	 * @var int
	 */
	private $page_number;

	/**
	 * Constructor for the class.
	 *
	 * @param int $page_number The page number. Default is 0.
	 */
	public function __construct( $page_number = 0 ) {
		$this->page_number = $page_number;
	}

	/**
	 * Loads the meta title.
	 *
	 * This function calls the load_option_string_value method to load the meta title value from the options,
	 * and falls back to a default value if the option is not set.
	 *
	 * @return string The loaded meta title value.
	 */
	protected function load_meta_title() {
		return $this->load_option_string_value(
			'home',
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return '%%sitename%%';
			}
		);
	}

	/**
	 * Loads the meta description value.
	 *
	 * @return string The meta description value.
	 */
	protected function load_meta_description() {
		return $this->load_option_string_value(
			'home',
			array( $this, 'load_meta_desc_from_options' ),
			function () {
				return '%%sitedesc%%';
			}
		);
	}

	/**
	 * Loads the robots value for the current page number.
	 *
	 * @return string The robots value for the current page number.
	 */
	protected function load_robots() {
		return $this->get_robots_for_page_number( $this->page_number );
	}

	/**
	 * Loads the canonical URL for the page.
	 *
	 * @return string The canonical URL for the page.
	 */
	protected function load_canonical_url() {
		$blog_home_url        = trailingslashit( get_bloginfo( 'url' ) );
		$first_page_indexed   = $this->is_first_page_indexed();
		$current_page_indexed = ! $this->is_noindex();
		if ( $current_page_indexed ) {
			return $this->append_page_number( $blog_home_url, $this->page_number );
		} elseif ( $first_page_indexed ) {
				return $blog_home_url;
		} else {
			return '';
		}
	}

	/**
	 * Loads the schema data.
	 *
	 * @return array The schema data.
	 */
	protected function load_schema() {
		$fragment = new \SmartCrawl\Schema\Fragments\Blog_Home(
			$this->get_meta_title(),
			$this->get_meta_description()
		);

		return $fragment->get_schema();
	}

	/**
	 * Loads the OpenGraph tags.
	 *
	 * @return array The OpenGraph tags.
	 */
	protected function load_opengraph_tags() {
		$tags = parent::load_opengraph_tags();

		$tags['og:type'] = 'website';

		return $tags;
	}

	/**
	 * Loads the value indicating whether OpenGraph is enabled for the specified location.
	 *
	 * @return bool The value indicating whether OpenGraph is enabled.
	 */
	protected function load_opengraph_enabled() {
		return $this->is_opengraph_enabled_for_location( 'home' );
	}

	/**
	 * Loads the OpenGraph title value.
	 *
	 * @return string The OpenGraph title value.
	 */
	protected function load_opengraph_title() {
		return $this->load_option_string_value(
			'home',
			array( $this, 'load_opengraph_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the OpenGraph description value.
	 *
	 * @return string The OpenGraph description value.
	 */
	protected function load_opengraph_description() {
		return $this->load_option_string_value(
			'home',
			array( $this, 'load_opengraph_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the OpenGraph images.
	 *
	 * @return array The array of OpenGraph image URLs.
	 */
	protected function load_opengraph_images() {
		$images = $this->load_opengraph_images_from_options( 'home' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Loads the Twitter enabled status for a specific location.
	 *
	 * @return bool The Twitter enabled status for the specified location.
	 */
	protected function load_twitter_enabled() {
		return $this->is_twitter_enabled_for_location( 'home' );
	}

	/**
	 * Loads the Twitter title value.
	 *
	 * @return string The Twitter title value.
	 */
	protected function load_twitter_title() {
		return $this->load_option_string_value(
			'home',
			array( $this, 'load_twitter_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the twitter description value.
	 *
	 * @return string The twitter description value.
	 */
	protected function load_twitter_description() {
		return $this->load_option_string_value(
			'home',
			array( $this, 'load_twitter_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the twitter images.
	 *
	 * @return array The twitter images URLs.
	 */
	protected function load_twitter_images() {
		$images = $this->load_twitter_images_from_options( 'home' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Retrieves the macros for a given subject.
	 *
	 * @param string $subject The subject for which to retrieve the macros. Default is an empty string.
	 *
	 * @return array An empty array, since no macros are available.
	 */
	public function get_macros( $subject = '' ) {
		return array();
	}

	/**
	 * Retrieves robots for page number.
	 *
	 * @param int $page_number Page number.
	 *
	 * @return string
	 */
	protected function get_robots_for_page_number( $page_number ) {
		$setting_key = 'main_blog_archive';
		if (
			$this->show_robots_on_subsequent_pages_only( $setting_key )
			&& $page_number < 2
		) {
			return '';
		}

		$noindex  = $this->get_noindex_setting( $setting_key ) ? 'noindex' : 'index';
		$nofollow = $this->get_nofollow_setting( $setting_key ) ? 'nofollow' : 'follow';

		return "{$noindex},{$nofollow}";
	}
}