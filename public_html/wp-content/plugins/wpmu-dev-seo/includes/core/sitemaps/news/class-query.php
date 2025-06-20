<?php
/**
 * This file contains the Query class for handling the querying of news items for the sitemap.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\News;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Sitemaps\Post_Fetcher;
use SmartCrawl\Sitemaps;

/**
 * Class Query
 *
 * Handles the querying of news items for the sitemap.
 */
class Query extends Sitemaps\Query {

	use Singleton;

	/**
	 * Get items for the sitemap.
	 *
	 * @param string $type The type of items to fetch.
	 * @param int    $page_number The page number for pagination.
	 *
	 * @return Item[] The list of items.
	 */
	public function get_items( $type = '', $page_number = 0 ) {
		$posts = $this->make_fetcher(
			$this->get_offset( $page_number ),
			$this->get_limit( $page_number ),
			empty( $type ) ? $this->get_supported_types() : array( $type )
		)->fetch();

		$items = array();
		foreach ( $posts as $post ) {
			$item = new Item();
			$item->set_title( $this->get_post_title( $post ) )
				->set_location( $this->get_post_url( $post ) )
				->set_publication_time( $this->get_post_timestamp( $post ) )
				->set_publication( $this->get_publication() )
				->set_language( $this->get_language_code() );

			$items[] = $item;
		}

		return $items;
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
			self::NO_LIMIT,
			empty( $type ) ? $this->get_supported_types() : array( $type )
		)->count();
	}

	/**
	 * Get the language code.
	 *
	 * @return mixed|void The language code.
	 */
	private function get_language_code() {
		$locale = get_locale();
		if ( 'zh_TW' === $locale ) {
			$language_code = 'zh-tw';
		} elseif ( 'zh_CN' === $locale ) {
			$language_code = 'zh-cn';
		} else {
			$contains_underscore = strpos( $locale, '_' ) !== false;
			$contains_dash       = strpos( $locale, '-' ) !== false;
			if ( $contains_underscore ) {
				$parts = explode( '_', $locale );
			} elseif ( $contains_dash ) {
				$parts = explode( '-', $locale );
			} else {
				$parts = array( $locale );
			}

			$language_code = empty( $parts )
				? 'en'
				: $parts[0];
		}

		return apply_filters( 'wds_news_sitemap_language_code', $language_code );
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
		$fetcher    = new Post_Fetcher();
		$post_types = is_string( $post_types )
			? array( $post_types )
			: $post_types;

		return $fetcher->set_offset( $offset )
			->set_limit( $limit )
			->set_post_types( $post_types )
			->set_date_query(
				array(
					array(
						'after'     => '2 days ago',
						'inclusive' => true,
					),
				)
			)
			->set_order_by( 'post_date' )
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
		$include = apply_filters( 'wds_news_sitemap_include_post_ids', array(), $post_types );

		return empty( $include ) || ! is_array( $include )
			? array()
			: array_filter( array_map( 'intval', $include ) );
	}

	/**
	 * Get the title of a post.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return array|int|string The post title.
	 */
	private function get_post_title( $post ) {
		return ! empty( $post->post_title )
			? $post->post_title
			: get_post_field( 'post_title', $post->ID );
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
	 * Get custom ignore IDs.
	 *
	 * @param array $post_types The post types to ignore.
	 *
	 * @return array The list of custom ignore IDs.
	 */
	private function get_custom_ignore_ids( $post_types ) {
		$ignored_ids = array();
		foreach ( $post_types as $post_type ) {
			$ignored_post_type_ids = apply_filters( "wds_news_sitemap_ignored_{$post_type}_ids", array() );
			$ignored_post_type_ids = ! empty( $ignored_post_type_ids ) && is_array( $ignored_post_type_ids )
				? $ignored_post_type_ids
				: array();

			$ignored_ids = array_merge(
				$ignored_ids,
				$ignored_post_type_ids
			);
		}

		return $ignored_ids;
	}

	/**
	 * Get the IDs of posts to ignore.
	 *
	 * @param array $post_types The post types to ignore.
	 *
	 * @return array|mixed|null The list of post IDs to ignore.
	 */
	public function get_ignore_ids( $post_types ) {
		$options          = $this->get_sitemap_options();
		$ignored_post_ids = \smartcrawl_get_array_value( $options, 'news-sitemap-excluded-post-ids' );
		$ignored_post_ids = empty( $ignored_post_ids ) ? array() : $ignored_post_ids;

		$ignored_post_ids = array_merge(
			$ignored_post_ids,
			$this->get_custom_ignore_ids( $post_types )
		);

		$excluded_term_ids = $this->get_excluded_term_ids( $options, $post_types );
		if ( empty( $excluded_term_ids ) ) {
			return $ignored_post_ids;
		}

		$terms = get_terms( array( 'include' => $excluded_term_ids ) );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $ignored_post_ids;
		}

		$taxonomy_terms = array();
		foreach ( $terms as $term ) {
			$taxonomy_terms[ $term->taxonomy ][] = $term->term_id;
		}

		$tax_query = array( 'relation' => 'OR' );
		foreach ( $taxonomy_terms as $taxonomy => $term_ids ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_ids,
			);
		}

		$post_ids         = get_posts(
			array(
				'post_type'      => $post_types,
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			)
		);
		$ignored_post_ids = array_merge(
			$ignored_post_ids,
			empty( $post_ids ) ? array() : $post_ids
		);

		return array_unique( $ignored_post_ids );
	}

	/**
	 * Get the excluded term IDs.
	 *
	 * @param array $options The sitemap options.
	 * @param array $post_types The post types to exclude.
	 *
	 * @return array The list of excluded term IDs.
	 */
	private function get_excluded_term_ids( $options, $post_types ) {
		$excluded_term_ids = array();
		foreach ( $post_types as $post_type ) {
			$post_type_excluded_term_ids = \smartcrawl_get_array_value( $options, "news-sitemap-$post_type-excluded-term-ids" );
			$excluded_term_ids           = array_merge(
				$excluded_term_ids,
				empty( $post_type_excluded_term_ids ) || ! is_array( $post_type_excluded_term_ids )
					? array()
					: $post_type_excluded_term_ids
			);
		}

		return array_unique( $excluded_term_ids );
	}

	/**
	 * Get the timestamp of a post.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return false|int The post timestamp.
	 */
	private function get_post_timestamp( $post ) {
		return ! empty( $post->post_date )
			? strtotime( $post->post_date )
			: time();
	}

	/**
	 * Get the publication name.
	 *
	 * @return string The publication name.
	 */
	private function get_publication() {
		$options = $this->get_sitemap_options();

		return (string) \smartcrawl_get_array_value( $options, 'news-publication' );
	}

	/**
	 * Get the supported post types.
	 *
	 * @return array|mixed The list of supported post types.
	 */
	public function get_supported_types() {
		$options             = $this->get_sitemap_options();
		$included_post_types = \smartcrawl_get_array_value( $options, 'news-sitemap-included-post-types' );

		return empty( $included_post_types )
			? array()
			: $included_post_types;
	}

	/**
	 * Get the filter prefix.
	 *
	 * @return string The filter prefix.
	 */
	public function get_filter_prefix() {
		return 'wds-sitemap-news-posts';
	}

	/**
	 * Get the sitemap options.
	 *
	 * @return array The sitemap options.
	 */
	private function get_sitemap_options() {
		return Settings::get_component_options( Settings::COMP_SITEMAP );
	}

	/**
	 * Get the URL for the index item.
	 *
	 * @param string $type The type of the item.
	 * @param int    $sitemap_num The sitemap number.
	 *
	 * @return string|void The URL for the index item.
	 */
	protected function get_index_item_url( $type, $sitemap_num ) {
		return home_url( "/news-$type-sitemap$sitemap_num.xml" );
	}
}