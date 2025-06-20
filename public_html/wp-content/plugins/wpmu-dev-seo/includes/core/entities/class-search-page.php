<?php
/**
 * Search Page Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

/**
 * Class Search_Page
 *
 * Represents a search page entity.
 */
class Search_Page extends Entity {
	/**
	 * The search phrase entered by the user.
	 *
	 * @var string
	 */
	private $search_phrase;
	/**
	 * WP posts.
	 *
	 * @var \WP_Post[]
	 */
	private $posts;
	/**
	 * Page number.
	 *
	 * @var int
	 */
	private $page_number;

	/**
	 * Class constructor.
	 *
	 * @param string     $search_phrase The search phrase.
	 * @param \WP_Post[] $posts An array of posts.
	 * @param int        $page_number The page number.
	 */
	public function __construct( $search_phrase, $posts = array(), $page_number = 0 ) {
		$this->search_phrase = $search_phrase;
		$this->posts         = $posts;
		$this->page_number   = $page_number;
	}

	/**
	 * Returns the search phrase.
	 *
	 * @return string The search phrase.
	 */
	public function get_search_phrase() {
		return $this->search_phrase;
	}

	/**
	 * Sets the search phrase.
	 *
	 * @param string $search_phrase Search phrase.
	 */
	public function set_search_phrase( $search_phrase ) {
		$this->search_phrase = $search_phrase;
	}

	/**
	 * Loads the meta title.
	 *
	 * @return string The meta title.
	 */
	protected function load_meta_title() {
		return $this->load_option_string_value(
			'search',
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return '%%searchphrase%% %%sep%% %%sitename%%';
			}
		);
	}

	/**
	 * Loads the meta description.
	 *
	 * @return string The meta description string.
	 */
	protected function load_meta_description() {
		return $this->load_option_string_value(
			'search',
			array( $this, 'load_meta_desc_from_options' ),
			'__return_empty_string'
		);
	}

	/**
	 * Loads the robots meta tag value.
	 *
	 * @return string The value of the robots meta tag.
	 */
	protected function load_robots() {
		$noindex  = $this->get_noindex_setting( 'search' ) ? 'noindex' : 'index';
		$nofollow = $this->get_nofollow_setting( 'search' ) ? 'nofollow' : 'follow';

		return "{$noindex},{$nofollow}";
	}

	/**
	 * Loads the canonical URL.
	 *
	 * @return string The canonical URL string.
	 */
	protected function load_canonical_url() {
		return $this->is_noindex()
			? ''
			: \smartcrawl_append_archive_page_number(
				get_search_link( $this->search_phrase ),
				$this->page_number
			);
	}

	/**
	 * Loads the schema.
	 *
	 * @return array The schema data.
	 */
	protected function load_schema() {
		$search_schema = new \SmartCrawl\Schema\Fragments\Search(
			$this->search_phrase,
			$this->posts,
			$this->get_meta_title(),
			$this->get_meta_description()
		);

		return $search_schema->get_schema();
	}

	/**
	 * Loads the OpenGraph enabled status.
	 *
	 * @return bool The OpenGraph enabled status.
	 */
	protected function load_opengraph_enabled() {
		return $this->is_opengraph_enabled_for_location( 'search' );
	}

	/**
	 * Loads the OpenGraph title.
	 *
	 * @return string The OpenGraph title string.
	 */
	protected function load_opengraph_title() {
		return $this->load_option_string_value(
			'search',
			array( $this, 'load_opengraph_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the OpenGraph description.
	 *
	 * @return string The OpenGraph description string.
	 */
	protected function load_opengraph_description() {
		return $this->load_option_string_value(
			'search',
			array( $this, 'load_opengraph_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the OpenGraph images.
	 *
	 * @return array An array of URLs for the OpenGraph images.
	 */
	protected function load_opengraph_images() {
		$images = $this->load_opengraph_images_from_options( 'search' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Loads the Twitter is enabled status.
	 *
	 * @return bool True if Twitter is enabled, false otherwise.
	 */
	protected function load_twitter_enabled() {
		return $this->is_twitter_enabled_for_location( 'search' );
	}

	/**
	 * Loads the Twitter title.
	 *
	 * @return string The loaded Twitter title.
	 */
	protected function load_twitter_title() {
		return $this->load_option_string_value(
			'search',
			array( $this, 'load_twitter_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the Twitter description.
	 *
	 * @return string The loaded Twitter description.
	 */
	protected function load_twitter_description() {
		return $this->load_option_string_value(
			'search',
			array( $this, 'load_twitter_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the Twitter images.
	 *
	 * @return array The array of Twitter image URLs.
	 */
	protected function load_twitter_images() {
		$images = $this->load_twitter_images_from_options( 'search' );

		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Retrieves the macros for the given subject.
	 *
	 * @param string $subject The subject to get the macros for. Default is an empty string.
	 *
	 * @return array An array of macros.
	 */
	public function get_macros( $subject = '' ) {
		return array(
			'%%searchphrase%%' => array( $this, 'get_search_phrase' ),
		);
	}
}