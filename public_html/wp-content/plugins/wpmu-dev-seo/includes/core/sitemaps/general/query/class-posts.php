<?php
/**
 * This file contains the Posts class for handling the querying of general posts for the sitemap.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\General\Queries;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Sitemaps\General\Item;
use SmartCrawl\Sitemaps\Post_Fetcher;
use SmartCrawl\Sitemaps\Query;
use SmartCrawl\Sitemaps\Utils;

/**
 * Class Posts
 *
 * Handles the querying of general posts for the sitemap.
 */
class Posts extends Query {

	use Singleton;

	/**
	 * Get items for the sitemap.
	 *
	 * @param string $type The type of items to fetch.
	 * @param int    $page_number The page number for pagination.
	 *
	 * @return array|Item[] The list of items.
	 */
	public function get_items( $type = '', $page_number = 0 ) {
		$items = array();
		$posts = $this->fetch_full_data( $type, $page_number );
		foreach ( $posts as $post ) {
			$item = new Item();

			$item->set_location( $this->get_post_url( $post ) )
				->set_last_modified( $this->get_post_modified_time( $post ) )
				->set_images( $this->get_post_images( $post ) );

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Get images for a post.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return array The list of images.
	 */
	private function get_post_images( $post ) {
		if ( ! Utils::sitemap_images_enabled() ) {
			return array();
		}

		$thumbnail_id    = get_post_thumbnail_id( $post->ID );
		$thumbnail_image = wp_get_attachment_image( $thumbnail_id, 'full' );
		$html            = $thumbnail_image;

		/**
		 * Filter hook to add additional images to a post on sitemap.
		 *
		 * @since 3.4.0
		 *
		 * @param int[]   $image_ids Attachment IDs.
		 * @param \WP_Post $post      Post object.
		 */
		$additional_image_ids = apply_filters( 'wds_sitemap_additional_images', array(), $post );
		if ( ! empty( $additional_image_ids ) ) {
			foreach ( $additional_image_ids as $image_id ) {
				// Only if a valid attachment.
				$image = wp_get_attachment_image( $image_id, 'full' );
				if ( ! empty( $image ) ) {
					$html .= "\n" . $image;
				}
			}
		}

		if ( ! empty( $post->post_content ) ) {
			$html .= "\n" . $post->post_content;
		}

		return $this->find_images( $html );
	}

	/**
	 * Get the URL of a post.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return false|string The post URL.
	 */
	private function get_post_url( $post ) {
		return get_permalink( $post->ID );
	}

	/**
	 * Get the last modified time of a post.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return false|int The post modified time.
	 */
	private function get_post_modified_time( $post ) {
		return ! empty( $post->post_modified )
			? strtotime( $post->post_modified )
			: time();
	}

	/**
	 * Get the filter prefix.
	 *
	 * @return string The filter prefix.
	 */
	public function get_filter_prefix() {
		return 'wds-sitemap-posts';
	}

	/**
	 * Get ignored URL IDs.
	 *
	 * @return array The list of ignored URL IDs.
	 */
	private function get_ignored_url_ids() {
		$ignore_urls = Utils::get_ignore_urls();
		$post_ids    = array();

		foreach ( $ignore_urls as $ignore_url ) {
			$post_id = url_to_postid( $ignore_url );

			if ( $post_id ) {
				$post_ids[] = $post_id;
			}
		}

		return $post_ids;
	}

	/**
	 * Create a post fetcher.
	 *
	 * @param int          $offset The offset for fetching posts.
	 * @param int          $limit The limit for fetching posts.
	 * @param array|string $post_types The post types to fetch.
	 *
	 * @return Post_Fetcher The post fetcher instance.
	 */
	private function make_fetcher( $offset, $limit, $post_types ) {
		$post_types = is_array( $post_types )
			? $post_types
			: array( $post_types );

		$fetcher = new Post_Fetcher();

		return $fetcher->set_offset( $offset )
						->set_limit( $limit )
						->set_post_types( $post_types )
						->set_ignore_ids( $this->get_ignore_ids( $post_types ) )
						->set_include_ids( $this->get_include_ids( $post_types ) );
	}

	/**
	 * Get the IDs of posts to include.
	 *
	 * @param array $post_types The post types to include.
	 *
	 * @return array The list of post IDs to include.
	 */
	private function get_include_ids( $post_types ) {
		$include = apply_filters( 'wds_posts_sitemap_include_post_ids', array(), $post_types );

		return empty( $include ) || ! is_array( $include )
			? array()
			: array_filter( array_map( 'intval', $include ) );
	}

	/**
	 * Check if a post is included in the sitemap.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return bool True if the post is included, false otherwise.
	 */
	public function is_post_included( $post ) {
		if ( ! is_a( $post, '\WP_Post' ) ) {
			return false;
		}

		if ( ! in_array( $post->post_type, $this->get_supported_types(), true ) ) {
			return false;
		}

		$ignored_ids = $this->get_ignore_ids( $post->post_type );

		if ( in_array( $post->ID, $ignored_ids, true ) ) {
			return false;
		}

		$fetcher = $this->make_fetcher( 0, 1, $post->post_type )
						->set_include_ids( array( $post->ID ) );

		return ! empty( $fetcher->fetch() );
	}

	/**
	 * Fetch full data for posts.
	 *
	 * @param string $type The type of items to fetch.
	 * @param int    $page_number The page number for pagination.
	 *
	 * @return array The list of posts with full data.
	 */
	private function fetch_full_data( $type, $page_number ) {
		$extra_columns = Utils::sitemap_images_enabled()
			? array( 'post_content' )
			: array();

		$fetcher = $this->make_fetcher(
			$this->get_offset( $page_number ),
			$this->get_limit( $page_number ),
			empty( $type ) ? $this->get_supported_types() : $type
		)->set_extra_columns( $extra_columns );

		return $fetcher->fetch();
	}

	/**
	 * Get custom ignore IDs.
	 *
	 * @param string $post_type The post type to ignore.
	 *
	 * @return array The list of custom ignore IDs.
	 */
	private function get_custom_ignore_ids( $post_type ) {
		$post_types = is_string( $post_type )
			? array( $post_type )
			: $post_type;

		$ignored_ids = array();
		foreach ( $post_types as $post_type ) {
			$ignored_post_type_ids = apply_filters( "wds_sitemap_ignored_{$post_type}_ids", array() );
			$ignored_post_type_ids = ! empty( $ignored_post_type_ids ) && is_array( $ignored_post_type_ids )
				? $ignored_post_type_ids
				: array();

			$ignored_ids = array_merge( $ignored_ids, $ignored_post_type_ids );
		}

		return $ignored_ids;
	}

	/**
	 * Get the IDs of posts to ignore.
	 *
	 * @param array $post_types The post types to ignore.
	 *
	 * @return array The list of post IDs to ignore.
	 */
	public function get_ignore_ids( $post_types ) {
		$custom_ignored_ids = $this->get_custom_ignore_ids( $post_types );

		return array_unique(
			array_merge(
				Utils::get_ignore_ids(),
				$this->get_ignored_url_ids(),
				$this->get_front_page_id(),
				$custom_ignored_ids
			)
		);
	}

	/**
	 * Get the supported post types.
	 *
	 * @return array The list of supported post types.
	 */
	public function get_supported_types() {
		$options = Settings::get_options();
		$types   = array();
		$raw     = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			)
		);
		foreach ( $raw as $type ) {
			if ( ! empty( $options[ 'post_types-' . $type . '-not_in_sitemap' ] ) ) {
				continue;
			}
			$types[] = $type;
		}

		return $types;
	}

	/**
	 * Get the count of items.
	 *
	 * @param string $type The type of items to count.
	 *
	 * @return int The count of items.
	 */
	public function get_item_count( $type = '' ) {
		return $this->make_fetcher(
			0,
			Query::NO_LIMIT,
			empty( $type ) ? $this->get_supported_types() : $type
		)->count();
	}

	/**
	 * Get the front page ID.
	 *
	 * @return array The list of front page IDs.
	 */
	private function get_front_page_id() {
		return 'page' === get_option( 'show_on_front' )
			? array( (int) get_option( 'page_on_front' ) )
			: array();
	}
}