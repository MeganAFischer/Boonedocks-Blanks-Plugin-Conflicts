<?php
/**
 * Post Type Archive Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

use SmartCrawl\Admin\Settings\Onpage;
use SmartCrawl\Schema\Fragments\Post_Type_Archive;

/**
 * Post_Type Entity_With_Archive class
 */
class Post_Type extends Entity_With_Archive {
	/**
	 * Post Type object.
	 *
	 * @var \WP_Post_Type
	 */
	private $post_type;
	/**
	 * Posts within the archive.
	 *
	 * @var \WP_Post[]
	 */
	private $posts;
	/**
	 * Archive Location.
	 *
	 * @var string
	 */
	private $location;
	/**
	 * Archive name.
	 *
	 * @var string
	 */
	private $name;
	/**
	 * Archive singular name.
	 *
	 * @var string
	 */
	private $singular_name;
	/**
	 * Archive page number.
	 *
	 * @var int
	 */
	private $page_number;

	/**
	 * Constructor.
	 *
	 * @param \WP_Post_Type $post_type Post type object.
	 * @param \WP_Post[]    $posts     Posts.
	 * @param int           $page_number     Page number.
	 */
	public function __construct( $post_type, $posts = array(), $page_number = 0 ) {
		$this->post_type   = $post_type;
		$this->posts       = $posts;
		$this->location    = $this->post_type ? Onpage::PT_ARCHIVE_PREFIX . $this->post_type->name : '';
		$this->page_number = $page_number;
	}

	/**
	 * Retrieves the name.
	 *
	 * If the name is null, it will load the name and assign it to the name property.
	 *
	 * @return string The name.
	 */
	public function get_name() {
		if ( is_null( $this->name ) ) {
			$this->name = $this->load_name();
		}

		return $this->name;
	}

	/**
	 * Loads the name.
	 *
	 * @return string The name of the post type.
	 */
	private function load_name() {
		if ( ! $this->post_type ) {
			return '';
		}

		return $this->post_type->labels->name;
	}

	/**
	 * Retrieves the singular name of the post type.
	 *
	 * If the singular name has not been loaded yet, it loads it using the `load_singular_name()` method.
	 *
	 * @return string The singular name of the post type.
	 */
	public function get_singular_name() {
		if ( is_null( $this->singular_name ) ) {
			$this->singular_name = $this->load_singular_name();
		}

		return $this->singular_name;
	}

	/**
	 * Loads the singular name of the post type.
	 *
	 * @return string The singular name of the post type, empty string if post type is not set.
	 */
	private function load_singular_name() {
		if ( ! $this->post_type ) {
			return '';
		}

		return $this->post_type->labels->singular_name;
	}

	/**
	 * Load the meta title value.
	 *
	 * @return string The loaded meta title value.
	 */
	protected function load_meta_title() {
		return $this->load_option_string_value(
			$this->location,
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return '%%pt_plural%% %%sep%% %%sitename%%';
			}
		);
	}

	/**
	 * Loads the meta description from options.
	 *
	 * @return string The loaded meta description string.
	 */
	protected function load_meta_description() {
		return $this->load_option_string_value(
			$this->location,
			array( $this, 'load_meta_desc_from_options' ),
			'__return_empty_string'
		);
	}

	/**
	 * Loads the robots for the current page.
	 *
	 * @return string The loaded robots string.
	 */
	protected function load_robots() {
		return $this->get_robots_for_page_number( $this->page_number );
	}

	/**
	 * Loads the canonical URL based on the post type and page number.
	 *
	 * @return string The loaded canonical URL string.
	 */
	protected function load_canonical_url() {
		if ( ! $this->post_type ) {
			return '';
		}

		$first_page_indexed   = $this->is_first_page_indexed();
		$current_page_indexed = ! $this->is_noindex();
		$post_type_link       = get_post_type_archive_link( $this->post_type->name );

		if ( $current_page_indexed ) {
			return $this->append_page_number( $post_type_link, $this->page_number );
		}

		if ( $first_page_indexed ) {
			return $post_type_link;
		}

		return '';
	}

	/**
	 * Loads the schema data for the post object type archive.
	 *
	 * @return array The schema data.
	 */
	protected function load_schema() {
		if ( ! $this->post_type ) {
			return array();
		}

		$fragment = new Post_Type_Archive(
			$this->post_type,
			$this->posts,
			$this->get_meta_title(),
			$this->get_meta_description()
		);

		return $fragment->get_schema();
	}

	/**
	 * Determines if OpenGraph is enabled for the specified location.
	 *
	 * @return bool Indicates if OpenGraph is enabled for the location.
	 */
	protected function load_opengraph_enabled() {
		return $this->is_opengraph_enabled_for_location( $this->location );
	}

	/**
	 * Loads the OpenGraph title from options.
	 *
	 * @return string The loaded OpenGraph title string.
	 */
	protected function load_opengraph_title() {
		return $this->load_option_string_value(
			$this->location,
			array( $this, 'load_opengraph_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the OpenGraph description from options.
	 *
	 * @return string The loaded OpenGraph description string.
	 */
	protected function load_opengraph_description() {
		return $this->load_option_string_value(
			$this->location,
			array( $this, 'load_opengraph_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the OpenGraph images.
	 *
	 * @return array An array of OpenGraph image URLs.
	 */
	protected function load_opengraph_images() {
		$images = $this->load_opengraph_images_from_options( $this->location );

		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Loads the Twitter enabled status.
	 *
	 * @return bool True if Twitter is enabled for the location, false otherwise.
	 */
	protected function load_twitter_enabled() {
		return $this->is_twitter_enabled_for_location( $this->location );
	}

	/**
	 * Loads the Twitter title from options or the meta title if it's not available.
	 *
	 * @return string The loaded Twitter title string.
	 */
	protected function load_twitter_title() {
		return $this->load_option_string_value(
			$this->location,
			array( $this, 'load_twitter_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the Twitter description from the options.
	 *
	 * @return string The loaded Twitter description string.
	 */
	protected function load_twitter_description() {
		return $this->load_option_string_value(
			$this->location,
			array( $this, 'load_twitter_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the Twitter images from options.
	 *
	 * @return array The loaded Twitter image URLs.
	 */
	protected function load_twitter_images() {
		$images = $this->load_twitter_images_from_options( $this->location );
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
	 * @return array An associative array containing the macros.
	 */
	public function get_macros( $subject = '' ) {
		return array(
			'%%pt_plural%%'      => array( $this, 'get_name' ),
			'%%pt_single%%'      => array( $this, 'get_singular_name' ),
			'%%archive-title%%'  => get_the_archive_title(),
			'%%original-title%%' => post_type_archive_title( '', false ),
		);
	}

	/**
	 * Retrieves the robots meta tag value for a given page number.
	 *
	 * @param int $page_number The page number.
	 *
	 * @return string The robots meta tag value.
	 */
	protected function get_robots_for_page_number( $page_number ) {
		if (
			$this->show_robots_on_subsequent_pages_only( $this->location )
			&& $page_number < 2
		) {
			return '';
		}

		$noindex  = $this->get_noindex_setting( $this->location ) ? 'noindex' : 'index';
		$nofollow = $this->get_nofollow_setting( $this->location ) ? 'nofollow' : 'follow';

		return "{$noindex},{$nofollow}";
	}
}