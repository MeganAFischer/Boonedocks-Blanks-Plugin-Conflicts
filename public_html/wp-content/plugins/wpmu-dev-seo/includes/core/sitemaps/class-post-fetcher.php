<?php
/**
 * Post_Fetcher class for handling post fetching in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

use SmartCrawl\Logger;

/**
 * Class Post_Fetcher
 *
 * Handles the retrieval of posts for sitemaps.
 */
class Post_Fetcher {

	/**
	 * Offset for the query.
	 *
	 * @var int
	 */
	private $offset = 0;

	/**
	 * Limit for the query.
	 *
	 * @var int
	 */
	private $limit = 10;

	/**
	 * Post types to include in the query.
	 *
	 * @var string[]
	 */
	private $post_types = array( 'post' );

	/**
	 * Extra columns to include in the query.
	 *
	 * @var array
	 */
	private $extra_columns = array();

	/**
	 * IDs to ignore in the query.
	 *
	 * @var array
	 */
	private $ignore_ids = array();

	/**
	 * IDs to include in the query.
	 *
	 * @var array
	 */
	private $include_ids = array();

	/**
	 * Date query parameters.
	 *
	 * @var array
	 */
	private $date_query = array();

	/**
	 * Column to order by.
	 *
	 * @var string
	 */
	private $order_by = 'post_modified';

	/**
	 * Fetches the posts based on the set parameters.
	 *
	 * @return array|mixed
	 */
	public function fetch() {
		global $wpdb;

		$columns = array(
			'ID',
			'post_title',
			'post_parent',
			'post_type',
			'post_modified',
			'post_date',
		);
		$columns = array_merge(
			$columns,
			$this->get_extra_columns()
		);

		$posts_query = $this->prepare_posts_query( $columns );
		if ( ! $posts_query ) {
			return array();
		}

		$query = "SELECT posts.*, canonical.meta_value AS canonical FROM ($posts_query) AS posts " .
				"LEFT OUTER JOIN $wpdb->postmeta AS canonical ON ID = canonical.post_id AND canonical.meta_key = '_wds_canonical'";

		$posts = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $this->remove_posts_with_external_canonicals( $posts );
	}

	/**
	 * Counts the posts based on the set parameters.
	 *
	 * @deprecated
	 *
	 * @return int
	 */
	public function count() {
		global $wpdb;

		$posts_query = $this->prepare_posts_query( array( 'ID' ) );
		if ( ! $posts_query ) {
			Logger::error( 'Encountered empty posts sitemap query while calculating count.' );

			return 0;
		}

		$post_query           = "SELECT posts.*, canonical.meta_value AS canonical FROM ($posts_query) AS posts JOIN $wpdb->postmeta AS canonical ON ID = canonical.post_id AND canonical.meta_key = '_wds_canonical' AND canonical.meta_value != ''";
		$posts_with_canonical = $wpdb->get_results( $post_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$posts_with_external_canonical = array();
		if ( is_null( $posts_with_canonical ) ) {
			Logger::error( 'The sitemap DB query to fetch posts with canonicals failed.' );
		} else {
			foreach ( $posts_with_canonical as $post_with_canonical ) {
				if ( $this->is_post_with_external_canonical( $post_with_canonical ) ) {
					$posts_with_external_canonical[] = (int) $post_with_canonical->ID;
				}
			}
		}

		$count_query = "SELECT COUNT(posts.ID) FROM ($posts_query) AS posts";
		if ( ! empty( $posts_with_external_canonical ) ) {
			$not_in      = join( ',', $posts_with_external_canonical );
			$count_query = "$count_query WHERE posts.ID NOT IN ($not_in)";
		}

		return (int) $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Removes posts with external canonicals from the list.
	 *
	 * @param array $posts The list of posts.
	 *
	 * @return array
	 */
	private function remove_posts_with_external_canonicals( $posts ) {
		$posts = $posts ?: array();

		return array_filter(
			$posts,
			function ( $post ) {
				return ! $this->is_post_with_external_canonical( $post );
			}
		);
	}

	/**
	 * Checks if a post has an external canonical URL.
	 *
	 * @param object $post The post object.
	 *
	 * @return bool
	 */
	private function is_post_with_external_canonical( $post ) {
		if ( ! $post->canonical ) {
			return false;
		}

		return $this->normalize_url( $post->canonical ) !== $this->normalize_url( get_permalink( $post->ID ) );
	}

	/**
	 * Normalizes a URL by removing protocol and trailing slash.
	 *
	 * @param string $url The URL to normalize.
	 *
	 * @return string
	 */
	private function normalize_url( $url ) {
		return str_replace(
			array(
				'http://',
				'https://',
				'www.',
			),
			'',
			untrailingslashit( $url )
		);
	}

	/**
	 * Prepares the SQL query for fetching posts.
	 *
	 * @param array $columns The columns to select in the query.
	 *
	 * @return string|false The prepared SQL query or false on failure.
	 */
	protected function prepare_posts_query( $columns ) {
		global $wpdb;

		$offset = $this->get_offset();
		$limit  = $this->get_limit();

		$included_types = $this->post_types;
		if ( empty( $included_types ) ) {
			return false;
		}

		$included_types_placeholders = $this->get_db_placeholders( $included_types );
		$included_types_string       = $wpdb->prepare( $included_types_placeholders, $included_types ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$types_where                 = "AND post_type IN ($included_types_string)";

		$ignore_ids_where = '';
		$ignore_ids       = $this->get_ignore_ids();
		if ( $ignore_ids ) {
			$ignore_ids_placeholders = $this->get_db_placeholders( $ignore_ids, '%d' );
			$ignore_ids_string       = $wpdb->prepare( $ignore_ids_placeholders, $ignore_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$ignore_ids_where        = "AND ID NOT IN ($ignore_ids_string)";
		}

		$include_ids_where = '';
		$include_ids       = $this->get_include_ids();
		if ( $include_ids ) {
			$include_ids_placeholders = $this->get_db_placeholders( $include_ids, '%d' );
			$include_ids_string       = $wpdb->prepare( $include_ids_placeholders, $include_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$include_ids_where        = "AND ID IN ($include_ids_string)";
		}

		$date_where = $this->get_date_where();

		$column_string = join( ', ', $columns );

		$order_by = $this->get_order_by();

		return "SELECT $column_string FROM $wpdb->posts " .
				"WHERE post_status = 'publish' " .
				"AND post_password = '' " .
				"$include_ids_where " .
				"$types_where " .
				"$date_where " .
				"$ignore_ids_where " .
				"AND ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE (meta_key = '_wds_meta-robots-noindex' AND meta_value = 1) OR (meta_key = '_wds_redirect' AND meta_value != '')) " .
				"ORDER BY $order_by ASC LIMIT $limit OFFSET $offset";
	}

	/**
	 * Get database placeholders for the given items.
	 *
	 * @param array  $items The items to get placeholders for.
	 * @param string $single_placeholder The placeholder to use for each item.
	 *
	 * @return string The placeholders for the items.
	 */
	private function get_db_placeholders( $items, $single_placeholder = '%s' ) {
		return join( ',', array_fill( 0, count( $items ), $single_placeholder ) );
	}

	/**
	 * Get the offset for the query.
	 *
	 * @return int The offset for the query.
	 */
	public function get_offset() {
		return $this->offset;
	}

	/**
	 * Set the offset for the query.
	 *
	 * @param int $offset The offset for the query.
	 *
	 * @return Post_Fetcher
	 */
	public function set_offset( $offset ) {
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Get the limit for the query.
	 *
	 * @return int The limit for the query.
	 */
	public function get_limit() {
		return $this->limit;
	}

	/**
	 * Set the limit for the query.
	 *
	 * @param int $limit The limit for the query.
	 *
	 * @return Post_Fetcher
	 */
	public function set_limit( $limit ) {
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Get the post types to include in the query.
	 *
	 * @return array The post types to include in the query.
	 */
	public function get_post_types() {
		return $this->post_types;
	}

	/**
	 * Set the post types to include in the query.
	 *
	 * @param array $post_types The post types to include in the query.
	 *
	 * @return Post_Fetcher
	 */
	public function set_post_types( $post_types ) {
		$this->post_types = $post_types;

		return $this;
	}

	/**
	 * Get the extra columns to include in the query.
	 *
	 * @return array The extra columns to include in the query.
	 */
	public function get_extra_columns() {
		return $this->extra_columns;
	}

	/**
	 * Set the extra columns to include in the query.
	 *
	 * @param array $extra_columns The extra columns to include in the query.
	 *
	 * @return Post_Fetcher
	 */
	public function set_extra_columns( $extra_columns ) {
		$this->extra_columns = $extra_columns;

		return $this;
	}

	/**
	 * Get the IDs to ignore in the query.
	 *
	 * @return array The IDs to ignore in the query.
	 */
	public function get_ignore_ids() {
		return $this->ignore_ids;
	}

	/**
	 * Set the IDs to ignore in the query.
	 *
	 * @param array $ignore_ids The IDs to ignore in the query.
	 *
	 * @return Post_Fetcher
	 */
	public function set_ignore_ids( $ignore_ids ) {
		$this->ignore_ids = $ignore_ids;

		return $this;
	}

	/**
	 * Get the IDs to include in the query.
	 *
	 * @return array The IDs to include in the query.
	 */
	public function get_include_ids() {
		return $this->include_ids;
	}

	/**
	 * Set the IDs to include in the query.
	 *
	 * @param array $include_ids The IDs to include in the query.
	 *
	 * @return Post_Fetcher
	 */
	public function set_include_ids( $include_ids ) {
		$this->include_ids = $include_ids;

		return $this;
	}

	/**
	 * Get the date query parameters.
	 *
	 * @return array The date query parameters.
	 */
	public function get_date_query() {
		return $this->date_query;
	}

	/**
	 * Set the date query parameters.
	 *
	 * @param array $date_query The date query parameters.
	 *
	 * @return Post_Fetcher
	 */
	public function set_date_query( $date_query ) {
		$this->date_query = $date_query;

		return $this;
	}

	/**
	 * Get the date where clause for the query.
	 *
	 * @return string The date where clause for the query.
	 */
	private function get_date_where() {
		$date_query_args = $this->get_date_query();
		if ( $date_query_args && is_array( $date_query_args ) ) {
			$date_query = new \WP_Date_Query( $date_query_args );

			return $date_query->get_sql();
		}

		return '';
	}

	/**
	 * Get the column to order by.
	 *
	 * @return string The column to order by.
	 */
	public function get_order_by() {
		return $this->order_by;
	}

	/**
	 * Set the column to order by.
	 *
	 * @param string $order_by The column to order by.
	 *
	 * @return Post_Fetcher
	 */
	public function set_order_by( $order_by ) {
		$this->order_by = $order_by;

		return $this;
	}
}