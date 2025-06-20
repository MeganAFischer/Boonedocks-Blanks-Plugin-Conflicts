<?php
/**
 * Post Author Archive Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

use SmartCrawl\Settings;

/**
 * Post Author Archive Entity.
 */
class Post_Author extends Entity_With_Archive {
	/**
	 * WP User object.
	 *
	 * @var \WP_User
	 */
	private $user;
	/**
	 * Posts within the archive.
	 *
	 * @var \WP_Post[]
	 */
	private $posts;
	/**
	 * Author's display name.
	 *
	 * @var string
	 */
	private $display_name;
	/**
	 * Author's description.
	 *
	 * @var string
	 */
	private $description;
	/**
	 * Archive page number.
	 *
	 * @var int
	 */
	private $page_number;

	/**
	 * Class constructor.
	 *
	 * @param \WP_User   $user The user object.
	 * @param \WP_Post[] $posts An array of posts.
	 * @param int        $page_number The page number.
	 */
	public function __construct( $user, $posts = array(), $page_number = 0 ) {
		$this->user        = $user;
		$this->posts       = $posts;
		$this->page_number = $page_number;
	}

	/**
	 * Loads the meta title.
	 *
	 * @return string The loaded meta title.
	 */
	protected function load_meta_title() {
		return $this->load_string_value(
			'author',
			array( $this, 'load_meta_title_from_author_meta' ),
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return '%%name%% %%sep%% %%sitename%%';
			}
		);
	}

	/**
	 * Loads the meta title from the author meta.
	 *
	 * @return string The meta title.
	 */
	protected function load_meta_title_from_author_meta() {
		if ( ! $this->user ) {
			return '';
		}

		return get_the_author_meta( 'wds_title', $this->user->ID );
	}

	/**
	 * Loads the meta description.
	 *
	 * @return string The loaded meta description value.
	 */
	protected function load_meta_description() {
		return $this->load_string_value(
			'author',
			array( $this, 'load_meta_desc_from_author_meta' ),
			array( $this, 'load_meta_desc_from_options' ),
			function () {
				return '%%user_description%%';
			}
		);
	}

	/**
	 * Loads the meta description from the author meta.
	 *
	 * @return string The loaded meta description value.
	 */
	protected function load_meta_desc_from_author_meta() {
		if ( ! $this->user ) {
			return '';
		}

		return get_the_author_meta( 'wds_metadesc', $this->user->ID );
	}

	/**
	 * Loads the robots meta tag for the current page.
	 *
	 * @return string The loaded robots value.
	 */
	protected function load_robots() {
		return $this->get_robots_for_page_number( $this->page_number );
	}

	/**
	 * Loads the canonical URL.
	 *
	 * @return string The loaded canonical URL value.
	 */
	protected function load_canonical_url() {
		if ( ! $this->user ) {
			return '';
		}

		$first_page_indexed   = $this->is_first_page_indexed();
		$current_page_indexed = ! $this->is_noindex();
		$author_posts_url     = get_author_posts_url( $this->user->ID );

		if ( $current_page_indexed ) {
			return $this->append_page_number( $author_posts_url, $this->page_number );
		} elseif ( $first_page_indexed ) {
			return $author_posts_url;
		} else {
			return '';
		}
	}

	/**
	 * Loads the schema for the author archive.
	 *
	 * @return array The loaded schema value.
	 */
	protected function load_schema() {
		if ( ! $this->user ) {
			return array();
		}

		$fragment = new \SmartCrawl\Schema\Fragments\Author_Archive(
			$this->user,
			$this->posts,
			$this->get_meta_title(),
			$this->get_meta_description()
		);

		return $fragment->get_schema();
	}

	/**
	 * Loads the enabled status for OpenGraph.
	 *
	 * @return bool The enabled status for OpenGraph.
	 */
	protected function load_opengraph_enabled() {
		return $this->is_opengraph_enabled_for_location( 'author' );
	}

	/**
	 * Loads the OpenGraph title.
	 *
	 * @return string The loaded OpenGraph title value.
	 */
	protected function load_opengraph_title() {
		return $this->load_option_string_value(
			'author',
			array( $this, 'load_opengraph_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the OpenGraph description.
	 *
	 * @return string The loaded OpenGraph description value.
	 */
	protected function load_opengraph_description() {
		return $this->load_option_string_value(
			'author',
			array( $this, 'load_opengraph_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the OpenGraph images.
	 *
	 * @return array The loaded OpenGraph images URLs.
	 */
	protected function load_opengraph_images() {
		$images = $this->load_opengraph_images_from_options( 'author' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Loads the enabled status for Twitter.
	 *
	 * @return bool The enabled status for Twitter.
	 */
	protected function load_twitter_enabled() {
		return $this->is_twitter_enabled_for_location( 'author' );
	}

	/**
	 * Loads the Twitter title.
	 *
	 * @return string The loaded Twitter title value.
	 */
	protected function load_twitter_title() {
		return $this->load_option_string_value(
			'author',
			array( $this, 'load_twitter_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the Twitter description.
	 *
	 * @return string The loaded Twitter description value.
	 */
	protected function load_twitter_description() {
		return $this->load_option_string_value(
			'author',
			array( $this, 'load_twitter_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the Twitter images.
	 *
	 * @return array The loaded Twitter images URLs.
	 */
	protected function load_twitter_images() {
		$images = $this->load_twitter_images_from_options( 'author' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Retrieves the author ID.
	 *
	 * @return int The user ID or 0 if the author is not set.
	 */
	public function get_id() {
		if ( ! $this->user ) {
			return 0;
		}

		return $this->user->ID;
	}

	/**
	 * Retrieves the display name for the current object.
	 *
	 * If the display name is null, it will be loaded from the `load_display_name` method.
	 *
	 * @return string The display name for the current author.
	 */
	public function get_display_name() {
		if ( is_null( $this->display_name ) ) {
			$this->display_name = $this->load_display_name();
		}

		return $this->display_name;
	}

	/**
	 * Loads the display name for the current author.
	 *
	 * If the author is not set, it will return an empty string.
	 *
	 * @return string The display name for the current author.
	 */
	private function load_display_name() {
		if ( ! $this->user ) {
			return '';
		}

		return get_the_author_meta( 'display_name', $this->user->ID );
	}

	/**
	 * Retrieves the description for the current author.
	 *
	 * If the description is null, it will be loaded from the `load_description` method.
	 *
	 * @return string The description for the current object.
	 */
	public function get_description() {
		if ( is_null( $this->description ) ) {
			$this->description = $this->load_description();
		}

		return $this->description;
	}

	/**
	 * Loads the description for the current author.
	 *
	 * If the author property is empty, an empty string will be returned.
	 *
	 * @return string The description for the current object.
	 */
	private function load_description() {
		if ( ! $this->user ) {
			return '';
		}

		return get_the_author_meta( 'description', $this->user->ID );
	}

	/**
	 * Retrieves the macros for the current author.
	 *
	 * If the author is not set, an empty array will be returned.
	 * The macros include placeholders and their corresponding methods that can be used for replacing values in a subject string.
	 *
	 * @param string $subject The subject string to be replaced with macros.
	 *
	 * @return array The macros array that contains placeholders and their corresponding methods.
	 */
	public function get_macros( $subject = '' ) {
		if ( ! $this->user ) {
			return array();
		}

		return array(
			'%%name%%'             => array( $this, 'get_display_name' ),
			'%%userid%%'           => array( $this, 'get_id' ),
			'%%user_description%%' => array( $this, 'get_description' ),
			'%%archive-title%%'    => get_the_archive_title(),
			'%%original-title%%'   => get_the_author(),
		);
	}

	/**
	 * Retrieves the meta robots value for a specific page number.
	 *
	 * If the "enable-author-archive" option is empty, returns "noindex,follow" to prevent indexing.
	 * If the "show_robots_on_subsequent_pages_only" setting is enabled, returns an empty string for the first page.
	 * Otherwise, constructs the meta robots value based on the "noindex" and "nofollow" settings for the "author" key.
	 *
	 * @param int $page_number The page number for which to retrieve the meta robots value.
	 *
	 * @return string The meta robots value for the specified page number.
	 */
	protected function get_robots_for_page_number( $page_number ) {
		$options = Settings::get_options();

		if ( empty( $options['enable-author-archive'] ) ) {
			return 'noindex,follow';
		}

		$setting_key = 'author';
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