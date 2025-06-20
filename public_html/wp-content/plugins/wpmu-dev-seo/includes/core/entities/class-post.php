<?php
/**
 * Post Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

use SmartCrawl\Html;
use SmartCrawl\Models\User;
use SmartCrawl\Schema\Fragments\Singular;

/**
 * Post Entity class.
 */
class Post extends Entity {
	/**
	 * Post ID.
	 *
	 * @var int
	 */
	private $post_id;
	/**
	 * Post object.
	 *
	 * @var \WP_Post|false
	 */
	private $wp_post;
	/**
	 * Post trimmed excerpt.
	 *
	 * @var string
	 */
	private $trimmed_excerpt;
	/**
	 * Post permalink.
	 *
	 * @var string
	 */
	private $permalink;
	/**
	 * Post thumbnail ID.
	 *
	 * @var int
	 */
	private $thumbnail_id;
	/**
	 * OpenGraph post meta.
	 *
	 * @var array
	 */
	private $opengraph_post_meta;
	/**
	 * Twitter post meta.
	 *
	 * @var array
	 */
	private $twitter_post_meta;
	/**
	 * Post date formatted.
	 *
	 * @var string
	 */
	private $post_date_formatted;
	/**
	 * Post category list as string.
	 *
	 * @var string
	 */
	private $category_list_string;
	/**
	 * Post type.
	 *
	 * @var string
	 */
	private $post_type;
	/**
	 * Post Author.
	 *
	 * @var User|false
	 */
	private $post_author;
	/**
	 * Page number.
	 *
	 * @var int
	 */
	private $page_number;
	/**
	 * Comments page.
	 *
	 * @var int
	 */
	private $comments_page;
	/**
	 * Focus keywords.
	 *
	 * @var array
	 */
	private $focus_keywords;

	/**
	 * Class constructor.
	 *
	 * @param \WP_Post|int $post The post object or post ID.
	 * @param int          $page_number The page number.
	 * @param int          $comments_page The comments page.
	 */
	public function __construct( $post, $page_number = 0, $comments_page = 0 ) {
		if ( is_a( $post, '\WP_Post' ) ) {
			$this->post_id = $post->ID;
			$this->wp_post = $post;
		} else {
			$this->post_id = $post;
		}

		$this->page_number   = $page_number;
		$this->comments_page = $comments_page;
	}

	/**
	 * Retrieves the post ID.
	 *
	 * @return int The post ID.
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * Retrieves the WP Post object.
	 *
	 * @return \WP_Post|false
	 */
	public function get_wp_post() {
		if ( is_null( $this->wp_post ) ) {
			$this->wp_post = $this->load_wp_post();
		}

		return $this->wp_post;
	}

	/**
	 * Loads the WP Post object associated with the post ID.
	 *
	 * @return \WP_Post|false The WP_Post object if found, false otherwise.
	 */
	private function load_wp_post() {
		$post_id = $this->get_post_id();

		if ( ! $post_id ) {
			return false;
		}

		$wp_post = get_post( $post_id );

		return $wp_post ? $wp_post : false;
	}

	/**
	 * Retrieves the title of the post.
	 *
	 * @return string The title of the post.
	 */
	public function get_title() {
		$wp_post = $this->get_wp_post();

		return $wp_post ? $wp_post->post_title : '';
	}

	/**
	 * Retrieves the excerpt of the post.
	 *
	 * @return string The post excerpt or an empty string if the post does not exist or the content is password protected.
	 */
	public function get_excerpt() {
		$wp_post = $this->get_wp_post();

		// Handle password protected content.
		if ( $wp_post && post_password_required( $this->get_post_id() ) ) {
			return __( 'This content is password protected.', 'wds' );
		}

		return $wp_post ? $wp_post->post_excerpt : '';
	}

	/**
	 * Retrieves the content of the post.
	 *
	 * @return string The content of the post.
	 */
	public function get_content() {
		$wp_post = $this->get_wp_post();

		// Handle password protected content.
		if ( $wp_post && post_password_required( $this->get_post_id() ) ) {
			return __( 'This content is password protected.', 'wds' );
		}

		return $wp_post ? $wp_post->post_content : '';
	}

	/**
	 * Retrieves the thumbnail ID of the post.
	 *
	 * If the thumbnail ID is not yet set, it will be retrieved from the WP_Post object.
	 *
	 * @return int The thumbnail ID.
	 */
	public function get_thumbnail_id() {
		if ( is_null( $this->thumbnail_id ) ) {
			$wp_post            = $this->get_wp_post();
			$this->thumbnail_id = $wp_post ? get_post_thumbnail_id( $wp_post ) : 0;
		}

		return $this->thumbnail_id;
	}

	/**
	 * Retrieves the post author.
	 *
	 * @return User|false The User object representing the post author, or false if no author is found.
	 */
	public function get_post_author() {
		if ( is_null( $this->post_author ) ) {
			$wp_post           = $this->get_wp_post();
			$this->post_author = $wp_post ? User::get( $wp_post->post_author ) : false;
		}

		return $this->post_author;
	}

	/**
	 * Retrieves the ID of the author of the post.
	 *
	 * @return int The author ID. Returns 0 if the author is not found.
	 */
	public function get_post_author_id() {
		$author = $this->get_post_author();

		return $author ? $author->get_id() : 0;
	}

	/**
	 * Retrieves the display name of the post author.
	 *
	 * @return string The display name of the post author.
	 */
	public function get_post_author_display_name() {
		$author = $this->get_post_author();

		return $author ? $author->get_display_name() : '';
	}

	/**
	 * Retrieves the author description of the post.
	 *
	 * @return string The author description.
	 */
	public function get_post_author_description() {
		$author = $this->get_post_author();

		return $author ? $author->get_description() : '';
	}

	/**
	 * Retrieves the modified date of the post.
	 *
	 * @return string The modified date of the post.
	 */
	public function get_post_modified() {
		$wp_post = $this->get_wp_post();

		return $wp_post ? $wp_post->post_modified : '';
	}

	/**
	 * Retrieves the permalink for the post.
	 *
	 * If the permalink is not already loaded, it will be loaded using the `load_permalink` method.
	 *
	 * @return string The permalink for the object.
	 */
	public function get_permalink() {
		if ( is_null( $this->permalink ) ) {
			$this->permalink = $this->load_permalink();
		}

		return $this->permalink;
	}

	/**
	 * Loads the permalink for the post.
	 *
	 * @return string The permalink of the post.
	 */
	private function load_permalink() {
		$wp_post = $this->get_wp_post();

		return $wp_post ? get_permalink( $wp_post->ID ) : '';
	}

	/**
	 * Retrieves the trimmed excerpt.
	 *
	 * If the trimmed excerpt has not been set yet, it will be calculated using
	 * the get_excerpt() and get_content() methods and then stored for future use.
	 *
	 * @return string The trimmed excerpt.
	 */
	public function get_trimmed_excerpt() {
		if ( is_null( $this->trimmed_excerpt ) ) {
			$this->trimmed_excerpt = \smartcrawl_get_trimmed_excerpt( $this->get_excerpt(), $this->get_content() );
		}

		return $this->trimmed_excerpt;
	}

	/**
	 * Retrieves the post date.
	 *
	 * @return string The post date or an empty string if the WordPress post is not available.
	 */
	public function get_post_date() {
		$wp_post = $this->get_wp_post();

		return $wp_post
			? $wp_post->post_date
			: '';
	}

	/**
	 * Retrieves the formatted post date.
	 *
	 * This function retrieves the formatted post date by calling the internal
	 * `load_post_date_formatted()` method and caches the result to avoid redundant
	 * operations.
	 *
	 * @return string The formatted post date.
	 */
	public function get_post_date_formatted() {
		if ( is_null( $this->post_date_formatted ) ) {
			$this->post_date_formatted = $this->load_post_date_formatted();
		}

		return $this->post_date_formatted;
	}

	/**
	 * Loads the formatted post date.
	 *
	 * @return string The formatted post date or an empty string if the WordPress post date is not available.
	 */
	private function load_post_date_formatted() {
		$post_date = $this->get_post_date();

		if ( ! $post_date ) {
			return '';
		}

		return mysql2date( get_option( 'date_format' ), $post_date );
	}

	/**
	 * Retrieves the category list as a string.
	 *
	 * If the category list has not been loaded yet, it will be loaded using the load_category_list_string() method.
	 *
	 * @return string The category list as a string.
	 */
	public function get_category_list_string() {
		if ( is_null( $this->category_list_string ) ) {
			$this->category_list_string = $this->load_category_list_string();
		}

		return $this->category_list_string;
	}

	/**
	 * Loads the category list as a string.
	 *
	 * @return string The category list as a string or an empty string if the WordPress post is not available.
	 */
	private function load_category_list_string() {
		$wp_post = $this->get_wp_post();
		if ( ! $wp_post ) {
			return '';
		}

		return get_the_category_list( ', ', '', $wp_post->ID );
	}

	/**
	 * Loads the meta title.
	 *
	 * @return string The meta title.
	 */
	protected function load_meta_title() {
		return $this->load_string_value(
			$this->get_post_type(),
			array( $this, 'load_meta_title_from_post_meta' ),
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return '%%title%% %%sep%% %%sitename%%';
			}
		);
	}

	/**
	 * Loads the meta title from the post object.
	 *
	 * @return string The meta title value or an empty string if the WordPress post is not available.
	 */
	protected function load_meta_title_from_post_meta() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return '';
		}

		return \smartcrawl_get_value( 'title', $wp_post->ID );
	}

	/**
	 * Loads the meta description for the post object.
	 *
	 * @return string The loaded meta description or an empty string if it is not available.
	 */
	protected function load_meta_description() {
		return $this->load_string_value(
			$this->get_post_type(),
			array( $this, 'load_meta_desc_from_post_meta' ),
			array( $this, 'load_meta_desc_from_options' ),
			array( $this, 'get_trimmed_excerpt' )
		);
	}

	/**
	 * Retrieves the meta description from the WordPress post meta.
	 *
	 * @return string The meta description or an empty string if the WordPress post is not available.
	 */
	protected function load_meta_desc_from_post_meta() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return '';
		}

		return \smartcrawl_get_value( 'metadesc', $wp_post->ID );
	}

	/**
	 * Loads the robots meta tag for the post object.
	 *
	 * @return string The robots meta tag value for the post object.
	 */
	protected function load_robots() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return '';
		}

		$post_id  = $wp_post->ID;
		$robots[] = $this->is_post_noindex( $post_id ) ? 'noindex' : 'index';
		$robots[] = $this->is_post_nofollow( $post_id ) ? 'nofollow' : 'follow';

		$advanced_value = \smartcrawl_get_value( 'meta-robots-adv', $post_id );

		if ( $advanced_value && 'none' !== $advanced_value ) {
			$robots[] = $advanced_value;
		}

		return implode( ',', $robots );
	}

	/**
	 * Checks if the post should have a noindex meta tag.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return bool True if the post should have a noindex meta tag, false otherwise.
	 */
	private function is_post_noindex( $post_id ) {
		// Checks if a comment page.
		if ( $this->comments_page ) {
			return true;
		}

		// Check at post type level.
		$post_type_noindexed = $this->get_noindex_setting( $this->get_post_type() );

		// Check at post level.
		$index   = (bool) \smartcrawl_get_value( 'meta-robots-index', $post_id );
		$noindex = (bool) \smartcrawl_get_value( 'meta-robots-noindex', $post_id );

		if ( $post_type_noindexed ) {
			return ! $index;
		} else {
			return $noindex;
		}
	}

	/**
	 * Checks if a post has the 'nofollow' attribute set.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return bool Whether the post has the 'nofollow' attribute set.
	 */
	private function is_post_nofollow( $post_id ) {
		// Checks at post type level.
		$post_type_nofollowed = $this->get_nofollow_setting( $this->get_post_type() );

		// Checks at post level.
		$follow   = (bool) \smartcrawl_get_value( 'meta-robots-follow', $post_id );
		$nofollow = (bool) \smartcrawl_get_value( 'meta-robots-nofollow', $post_id );

		if ( $post_type_nofollowed ) {
			return ! $follow;
		} else {
			return $nofollow;
		}
	}

	/**
	 * Loads the canonical URL.
	 * An empty string if the WordPress post is not available,
	 * or the post is set to noindex, or no canonical URL is set.
	 *
	 * @return string
	 */
	protected function load_canonical_url() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return '';
		}

		if ( $this->is_noindex() ) {
			return '';
		}

		$canonical = \smartcrawl_get_value( 'canonical', $wp_post->ID );

		if ( empty( $canonical ) ) {
			$canonical = $this->get_default_canonical();
		}

		return $canonical;
	}

	/**
	 * Retrieves the default canonical URL for a page.
	 *
	 * @return string The default canonical URL for the page.
	 */
	private function get_default_canonical() {
		// Starts with the permalink.
		$canonical_url = $this->get_permalink();

		// Appends the page number.
		if ( $this->page_number > 1 ) {
			if ( ! get_option( 'permalink_structure' ) ) {
				$canonical_url = add_query_arg( 'page', $this->page_number, $canonical_url );
			} else {
				$canonical_url = trailingslashit( $canonical_url ) . user_trailingslashit( $this->page_number, 'single_paged' );
			}
		}

		// As opposed to wp_get_canonical_url, we are not going to include the comment part because we add noindex to comment pages.
		return $canonical_url;
	}

	/**
	 * Loads the schema for the post.
	 *
	 * @return array The loaded schema or an empty array if the WordPress post is not available.
	 */
	protected function load_schema() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return array();
		}

		$schema = new Singular( $this );

		return $schema->get_schema();
	}

	/**
	 * Determines if OpenGraph is enabled.
	 *
	 * @return bool Returns true if OpenGraph is enabled, false otherwise.
	 */
	protected function load_opengraph_enabled() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return false;
		}

		$enabled_in_options = $this->is_opengraph_enabled_for_location( $this->get_post_type() );

		if ( ! $enabled_in_options ) {
			return false;
		}

		$post_meta = $this->get_opengraph_post_meta();

		return ! \smartcrawl_get_array_value( $post_meta, 'disabled' );
	}

	/**
	 * Retrieves the OpenGraph post meta.
	 *
	 * @return array The OpenGraph post meta or null if it has not been loaded yet.
	 */
	private function get_opengraph_post_meta() {
		if ( is_null( $this->opengraph_post_meta ) ) {
			$this->opengraph_post_meta = $this->load_opengraph_post_meta();
		}

		return $this->opengraph_post_meta;
	}

	/**
	 * Loads the OpenGraph post meta.
	 *
	 * @return array The OpenGraph post meta as an array.
	 */
	private function load_opengraph_post_meta() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return array();
		}

		return (array) \smartcrawl_get_value( 'opengraph', $wp_post->ID );
	}

	/**
	 * Loads and returns the Open Graph title.
	 *
	 * It loads the Open Graph title from different sources in the following order:
	 *   1. The post type
	 *   2. The post meta
	 *   3. The options
	 *   4. The meta title
	 *
	 * @return string The Open Graph title.
	 */
	protected function load_opengraph_title() {
		return $this->load_string_value(
			$this->get_post_type(),
			array( $this, 'load_opengraph_title_from_post_meta' ),
			array( $this, 'load_opengraph_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Retrieves the OpenGraph title from the post metadata.
	 *
	 * @return string The OpenGraph title or null if it is not found.
	 */
	protected function load_opengraph_title_from_post_meta() {
		return \smartcrawl_get_array_value( $this->get_opengraph_post_meta(), 'title' );
	}

	/**
	 * Loads the OpenGraph description.
	 *
	 * @return string The OpenGraph description.
	 */
	protected function load_opengraph_description() {
		return $this->load_string_value(
			$this->get_post_type(),
			array( $this, 'load_opengraph_description_from_post_meta' ),
			array( $this, 'load_opengraph_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the OpenGraph description from the post meta.
	 *
	 * @return string The OpenGraph description from the post meta, or an empty string if it is not available.
	 */
	protected function load_opengraph_description_from_post_meta() {
		return \smartcrawl_get_array_value( $this->get_opengraph_post_meta(), 'description' );
	}

	/**
	 * Loads OpenGraph images.
	 *
	 * @return array The result of the "load_social_images" method.
	 */
	protected function load_opengraph_images() {
		return $this->load_social_images(
			array( $this, 'get_opengraph_post_meta' ),
			array( $this, 'load_opengraph_images_from_options' ),
			array( $this, 'use_first_content_image_for_opengraph' )
		);
	}

	/**
	 * Load social images for the post.
	 *
	 * @param callable $load_post_meta Determines if post meta should be loaded.
	 * @param callable $load_from_options The function to load images from options.
	 * @param callable $use_content_image The function to determine if the first image from content should be used.
	 *
	 * @return array The loaded social images as an array of URLs.
	 */
	protected function load_social_images( $load_post_meta, $load_from_options, $use_content_image ) {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return array();
		}

		// Checks meta in post metabox.
		$images = array_filter(
			\smartcrawl_get_array_value( call_user_func( $load_post_meta ), 'images', array() ),
			function ( $image ) {
				return wp_get_attachment_image_src( $image );
			}
		);

		// Includes post thumbnail, if available.
		if ( empty( $images ) && $this->get_thumbnail_id() ) {
			$images = array( $this->get_thumbnail_id() );
		}

		// Checks settings.
		if ( empty( $images ) ) {
			$images = call_user_func( $load_from_options, $this->get_post_type() );
		}

		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		// Still nothing? Retrieves the first image from the content.
		if ( call_user_func( $use_content_image, $this->get_post_type() ) ) {
			$from_content = $this->get_first_image_from_content();
			if ( $from_content ) {
				return array( $from_content => array( $from_content ) );
			}
		}

		return array();
	}

	/**
	 * Retrieves the first image URL from the content.
	 *
	 * @return string The first image URL or an empty string if the content is not available or no image is found.
	 */
	private function get_first_image_from_content() {
		if ( ! $this->get_content() ) {
			return '';
		}

		$attributes = Html::find_attributes( 'img', 'src', $this->get_content() );

		if ( empty( $attributes ) ) {
			return '';
		}

		return array_shift( $attributes );
	}

	/**
	 * Determines whether or not to use the first content image for OpenGraph.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return bool True if the first content image should be used, false otherwise.
	 */
	private function use_first_content_image_for_opengraph( $post_type ) {
		return ! $this->get_onpage_option( 'og-disable-first-image-' . $post_type );
	}

	/**
	 * Checks if the first content image should be used for Twitter.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return bool Returns true if the first content image should be used for Twitter, false otherwise.
	 */
	private function use_first_content_image_for_twitter( $post_type ) {
		return ! $this->get_onpage_option( 'twitter-disable-first-image-' . $post_type );
	}

	/**
	 * Loads the Twitter enabled state for the post object.
	 *
	 * @return bool Whether Twitter is enabled for the post object.
	 */
	protected function load_twitter_enabled() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return false;
		}

		$enabled_in_options = $this->is_twitter_enabled_for_location( $this->get_post_type() );

		if ( ! $enabled_in_options ) {
			return false;
		}

		$post_meta             = $this->get_twitter_post_meta();
		$disabled_in_post_meta = \smartcrawl_get_array_value( $post_meta, 'disabled' );

		return ! $disabled_in_post_meta;
	}

	/**
	 * Retrieves the Twitter post metadata.
	 *
	 * If the metadata is not available, it is loaded and stored in the class property
	 * for future use.
	 *
	 * @return mixed The Twitter post metadata.
	 */
	private function get_twitter_post_meta() {
		if ( is_null( $this->twitter_post_meta ) ) {
			$this->twitter_post_meta = $this->load_twitter_post_meta();
		}

		return $this->twitter_post_meta;
	}

	/**
	 * Loads the Twitter post meta.
	 *
	 * @return array The Twitter post meta or an empty array if the WordPress post is not available.
	 */
	private function load_twitter_post_meta() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return array();
		}

		return (array) \smartcrawl_get_value( 'twitter', $wp_post->ID );
	}

	/**
	 * Loads the Twitter title.
	 *
	 * @return string The Twitter title.
	 */
	protected function load_twitter_title() {
		return $this->load_string_value(
			$this->get_post_type(),
			array( $this, 'load_twitter_title_from_post_meta' ),
			array( $this, 'load_twitter_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the Twitter title from the post meta.
	 *
	 * @return string The Twitter title if available, otherwise null.
	 */
	protected function load_twitter_title_from_post_meta() {
		return \smartcrawl_get_array_value( $this->get_twitter_post_meta(), 'title', '' );
	}

	/**
	 * Loads the Twitter description.
	 *
	 * @return string The Twitter description.
	 */
	protected function load_twitter_description() {
		return $this->load_string_value(
			$this->get_post_type(),
			array( $this, 'load_twitter_description_from_post_meta' ),
			array( $this, 'load_twitter_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the Twitter description from the post meta.
	 *
	 * @return string The Twitter description or an empty string if not found.
	 */
	protected function load_twitter_description_from_post_meta() {
		return \smartcrawl_get_array_value( $this->get_twitter_post_meta(), 'description' );
	}

	/**
	 * Loads the Twitter images.
	 *
	 * @return array
	 */
	protected function load_twitter_images() {
		return $this->load_social_images(
			array( $this, 'get_twitter_post_meta' ),
			array( $this, 'load_twitter_images_from_options' ),
			array( $this, 'use_first_content_image_for_twitter' )
		);
	}

	/**
	 * Retrieves the specified post meta value.
	 *
	 * @param string $meta_key The meta key of the post meta to retrieve.
	 *
	 * @return string The value of the specified post meta key or an empty string if the WordPress post is not available.
	 */
	protected function get_post_meta( $meta_key ) {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return '';
		}

		return get_post_meta( $wp_post->ID, $meta_key, true );
	}

	/**
	 * Retrieves the terms of the taxonomy that are attached to the post object.
	 *
	 * @param string $taxonomy_name The name of the taxonomy.
	 *
	 * @return \WP_Term[] An array of linked terms or an empty array if the WordPress post is not available
	 * or if there are no linked terms for the given taxonomy.
	 */
	protected function get_attached_terms( $taxonomy_name ) {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return array();
		}

		$terms = get_the_terms( $wp_post->ID, $taxonomy_name );

		return $terms && ! is_wp_error( $terms ) ? $terms : array();
	}

	/**
	 * Retrieves an array of macros.
	 *
	 * @param string $subject The subject for finding dynamic replacements. Default empty string.
	 *
	 * @return array An array of macros.
	 */
	public function get_macros( $subject = '' ) {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return array();
		}

		$macros = array(
			'%%date%%'             => array( $this, 'get_post_date_formatted' ),
			'%%excerpt%%'          => array( $this, 'get_trimmed_excerpt' ),
			'%%excerpt_only%%'     => array( $this, 'get_excerpt' ),
			'%%id%%'               => array( $this, 'get_post_id' ),
			'%%modified%%'         => array( $this, 'get_post_modified' ),
			'%%name%%'             => array( $this, 'get_post_author_display_name' ),
			'%%title%%'            => array( $this, 'get_title' ),
			'%%userid%%'           => array( $this, 'get_post_author_id' ),
			'%%user_description%%' => array( $this, 'get_post_author_description' ),
			'%%caption%%'          => array( $this, 'get_excerpt' ),
			'%%category%%'         => array( $this, 'get_category_list_string' ),
		);

		$dynamic = $this->find_dynamic_replacements(
			$subject,
			array( $this, 'get_attached_terms' ),
			array( $this, 'get_post_meta' )
		);

		return array_merge(
			$macros,
			$dynamic
		);
	}

	/**
	 * Retrieves the post type.
	 *
	 * If the post type is not loaded, it will be loaded using the `load_post_type` method.
	 *
	 * @return string The post type.
	 */
	public function get_post_type() {
		if ( is_null( $this->post_type ) ) {
			$this->post_type = $this->load_post_type();
		}

		return $this->post_type;
	}

	/**
	 * Loads the post type.
	 *
	 * @return string The post type or an empty string if the post object is not set.
	 */
	private function load_post_type() {
		$wp_post = $this->get_wp_post();

		if ( ! $wp_post ) {
			return '';
		}

		if (
			'revision' === $wp_post->post_type &&
			$wp_post->post_parent
		) {
			return get_post_type( $wp_post->post_parent );
		}

		return $wp_post->post_type;
	}

	/**
	 * Loads the OpenGraph tags.
	 *
	 * @return array The array of OpenGraph tags.
	 */
	protected function load_opengraph_tags() {
		if ( ! $this->get_wp_post() ) {
			return array();
		}

		$tags = parent::load_opengraph_tags();

		if ( $this->is_front_page() ) {
			$tags['og:type'] = 'website';
		} else {
			$tags['og:type']                = 'article';
			$tags['article:published_time'] = mysql2date( 'Y-m-d\TH:i:s', $this->get_post_date() );
			$tags['article:author']         = $this->get_post_author_display_name();
		}

		return $tags;
	}

	/**
	 * Retrieves the primary keyword of the post.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_primary_keyword() {
		$keywords = $this->get_focus_keywords();

		if ( empty( $keywords ) ) {
			return '';
		}

		return array_shift( $keywords );
	}

	/**
	 * Retrieves the extra keywords of the post.
	 *
	 * @since 3.4.0
	 *
	 * @return array
	 */
	public function get_extra_keywords() {
		$keywords = $this->get_focus_keywords();
		$primary  = $this->get_primary_keyword();
		if ( empty( $keywords ) || empty( $primary ) ) {
			return array();
		}

		return array_diff( $keywords, array( $primary ) );
	}

	/**
	 * Retrieves focus keywords.
	 *
	 * @return array
	 */
	public function get_focus_keywords() {
		if ( is_null( $this->focus_keywords ) ) {
			$this->focus_keywords = $this->load_focus_keywords();
		}

		return $this->focus_keywords;
	}

	/**
	 * Sets focus keywords.
	 *
	 * @since 3.4.0
	 *
	 * @param array $keywords Keywords.
	 *
	 * @return void
	 */
	public function set_focus_keywords( $keywords = array() ) {
		// Makes sure it's unique.
		$keywords = $this->unique_focus_keywords( $keywords );

		// We need only 3.
		if ( count( $keywords ) > 3 ) {
			$keywords = array_slice( $keywords, 0, 3 );
		}

		// Removes leading and ending white spaces.
		$keywords = array_map( 'trim', $keywords );

		// Sanitizes keywords.
		$keywords = array_map( 'sanitize_text_field', $keywords );

		// Makes it a string.
		$keywords = implode( ',', $keywords );

		// Saves to post meta.
		\smartcrawl_set_value( 'focus-keywords', $keywords, $this->get_post_id() );

		$this->focus_keywords = $keywords;
	}

	/**
	 * Adds new focus keyword to the existing keywords.
	 *
	 * @param string $keyword Keyword.
	 *
	 * @return void
	 */
	public function add_focus_keyword( $keyword ) {
		// No need to continue if empty.
		if ( empty( $keyword ) ) {
			return;
		}

		// Separates keywords.
		$new_keywords = explode( ',', $keyword );

		// Gets current keywords.
		$keywords = $this->get_focus_keywords();

		if ( empty( $keywords ) ) {
			$keywords = $new_keywords;
		} else {
			$keywords = array_merge( $keywords, $new_keywords );
		}

		$this->set_focus_keywords( $keywords );
	}

	/**
	 * Removes a focus keyword from the existing keywords.
	 *
	 * @param string $keyword Keyword.
	 *
	 * @return void
	 */
	public function remove_focus_keyword( $keyword ) {
		// No need to continue if empty.
		if ( empty( $keyword ) ) {
			return;
		}

		// Gets current keywords.
		$keywords = $this->get_focus_keywords();
		if ( empty( $keywords ) ) {
			return;
		}
		// If found, removes it from the array.
		if ( in_array( $keyword, $keywords, true ) ) {
			$keywords = array_diff( $keywords, array( $keyword ) );
		}

		$this->set_focus_keywords( $keywords );
	}

	/**
	 * Saves focus keywords from string.
	 *
	 * @since 3.4.0
	 *
	 * @param string $keywords Keywords.
	 *
	 * @return void
	 */
	public function set_focus_keywords_from_string( $keywords = '' ) {
		// No need to continue if empty.
		if ( empty( $keywords ) ) {
			$this->set_focus_keywords( array() );
			return;
		}

		// Split by comma.
		$keywords = explode( ',', $keywords );

		$this->set_focus_keywords( $keywords );
	}

	/**
	 * Loads focus keywords from the meta.
	 *
	 * @return array
	 */
	private function load_focus_keywords() {
		$string = \smartcrawl_get_value( 'focus-keywords', $this->get_post_id() );
		if ( empty( $string ) || ! is_scalar( $string ) ) {
			return array();
		}

		$string = trim( strval( $string ) );
		$array  = $string ? explode( ',', $string ) : array();
		$array  = array_map( 'trim', $array );

		return array_values( array_filter( array_unique( $array ) ) );
	}

	/**
	 * Makes sure the keywords are unique.
	 *
	 * @since 3.4.0
	 *
	 * @param array $keywords Keywords.
	 *
	 * @return array
	 */
	private function unique_focus_keywords( array $keywords ) {
		return array_intersect_key(
			$keywords,
			array_unique( array_map( 'strtolower', $keywords ) )
		);
	}

	/**
	 * Is front page.
	 *
	 * @return bool
	 */
	public function is_front_page() {
		return 'page' === get_option( 'show_on_front' ) && $this->get_post_id() === (int) get_option( 'page_on_front' );
	}
}