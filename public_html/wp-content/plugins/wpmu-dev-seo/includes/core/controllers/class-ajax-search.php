<?php
/**
 * Controller to handle commonly used ajax requests.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Singleton;

/**
 * Ajax Search controller.
 */
class Ajax_Search extends Controller {

	use Singleton;

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'wp_ajax_wds_search_post', array( $this, 'search_post' ) );
		add_action( 'wp_ajax_wds-search-term', array( $this, 'search_taxonomy_term' ) );

		add_action( 'wp_ajax_smartcrawl_get_posts_by_ids', array( $this, 'get_posts_by_ids' ) );
		add_action( 'wp_ajax_smartcrawl_get_posts_paged', array( $this, 'get_posts_paged' ) );
	}

	/**
	 * Searches posts by post type or post ID.
	 *
	 * @return void
	 */
	public function search_post() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search_query = \smartcrawl_get_array_value( $_GET, 'term' );
		$post_type    = \smartcrawl_get_array_value( $_GET, 'type' );
		$request_type = \smartcrawl_get_array_value( $_GET, 'request_type' );
		$post_id      = \smartcrawl_get_array_value( $_GET, 'id' );
		// phpcs:enable

		if ( empty( $search_query ) && empty( $post_id ) ) {
			wp_send_json( array( 'results' => array() ) );
		}

		$results = array();

		$args = array(
			'post_status'         => 'attachment' === $post_type ? 'inherit' : 'publish',
			'posts_per_page'      => 10,
			'ignore_sticky_posts' => true,
			'post_type'           => $post_type,
			's'                   => $search_query,
		);

		if ( 'text' === $request_type && $post_id ) {
			$args['post__in'] = is_array( $post_id ) ? $post_id : array( $post_id );
		}

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$results[] = array(
				'id'   => $post->ID,
				'text' => $post->post_title,
				'url'  => get_permalink( $post ),
			);
		}

		wp_send_json( array( 'results' => $results ) );
	}

	/**
	 * Searches taxonomy term by search query or term ID.
	 *
	 * @return void
	 */
	public function search_taxonomy_term() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search_query = \smartcrawl_get_array_value( $_GET, 'term' );
		$taxonomy     = \smartcrawl_get_array_value( $_GET, 'type' );
		$request_type = \smartcrawl_get_array_value( $_GET, 'request_type' );
		$term_id      = \smartcrawl_get_array_value( $_GET, 'id' );
		// phpcs:enable

		$results = array();

		if ( empty( $search_query ) && empty( $term_id ) ) {
			wp_send_json( array( 'results' => $results ) );
		}

		$args = array(
			'hide_empty' => false,
			'taxonomy'   => $taxonomy,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);
		if ( 'text' === $request_type && $term_id ) {
			$args['include'] = is_array( $term_id ) ? $term_id : array( $term_id );
			$args['number']  = is_array( $term_id ) ? count( $term_id ) : 1;
		} else {
			$args['search'] = $search_query;
			$args['number'] = 10;
		}

		$terms = get_terms( $args );

		foreach ( $terms as $term ) {
			$results[] = array(
				'id'   => $term->term_id,
				'text' => $term->name,
			);
		}

		wp_send_json( array( 'results' => $results ) );
	}


	/**
	 * Makes the post response format uniform
	 *
	 * @param object $post WP_Post instance.
	 *
	 * @return array Post response hash
	 */
	private function post_to_response_data( $post ) {
		$result = array(
			'id'    => 0,
			'title' => '',
			'type'  => '',
			'date'  => '',
		);

		if ( empty( $post->ID ) ) {
			return $result;
		}

		static $date_format;

		if ( empty( $date_format ) ) {
			$date_format = get_option( 'date_format' );
		}

		$post_id         = $post->ID;
		$result['id']    = $post_id;
		$result['title'] = get_the_title( $post_id );
		$result['type']  = get_post_type( $post_id );
		$result['date']  = get_post_time( $date_format, false, $post_id );

		return $result;
	}

	/**
	 * Retrieves posts by specific IDs.
	 */
	public function get_posts_by_ids() {
		$data = isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-admin-nonce' ) ? $_POST : array();

		$result = array(
			'meta'  => array(),
			'posts' => array(),
		);

		if ( ! current_user_can( 'edit_others_posts' ) || empty( $data ) ) {
			wp_send_json( $result );
		}

		$post_ids = ! empty( $data['posts'] ) && is_array( $data['posts'] )
			? array_values( array_filter( array_map( 'intval', $data['posts'] ) ) )
			: array();

		if ( empty( $post_ids ) ) {
			wp_send_json_success( $result );
		}

		$args = array(
			'post_status'         => 'publish',
			'posts_per_page'      => - 1,
			'post__in'            => $post_ids,
			'orderby'             => 'post__in',
			'ignore_sticky_posts' => true,
			'post_type'           => 'any',
		);

		$query = new \WP_Query( $args );

		$result['meta'] = array(
			'total' => $query->max_num_pages,
			'page'  => 1,
		);

		foreach ( $query->posts as $post ) {
			if ( ! empty( $post->ID ) ) {
				$result['posts'][ $post->ID ] = $this->post_to_response_data( $post );
			}
		}

		wp_send_json_success( $result );
	}

	/**
	 * Retrieves paged posts of certain type
	 */
	public function get_posts_paged() {
		$data = isset( $_GET['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wds_nonce'] ) ), 'wds-admin-nonce' ) ? $_GET : array();

		$result = array(
			'meta'  => array(),
			'posts' => array(),
		);

		if ( ! current_user_can( 'edit_others_posts' ) || empty( $data ) ) {
			wp_send_json( $result );
		}

		$args = array(
			'post_status'         => 'publish',
			'posts_per_page'      => 10,
			'ignore_sticky_posts' => true,
		);

		$page = 1;

		if ( ! empty( $data['type'] ) && in_array( $data['type'], array_keys( $this->get_post_types() ), true ) ) {
			$args['post_type'] = sanitize_key( $data['type'] );
		}

		if ( ! empty( $data['term'] ) ) {
			$args['s'] = sanitize_text_field( $data['term'] );
		}

		if ( ! empty( $data['page'] ) && is_numeric( $data['page'] ) ) {
			$args['paged'] = (int) $data['page'];
			$page          = $args['paged'];
		}

		$query = new \WP_Query( $args );

		$result['meta'] = array(
			'total' => $query->max_num_pages,
			'page'  => $page,
		);

		foreach ( $query->posts as $post ) {
			$result['posts'][] = $this->post_to_response_data( $post );
		}

		wp_send_json( $result );
	}

	/**
	 * Returns a list of known post type objects.
	 *
	 * @return array
	 */
	private function get_post_types() {
		static $post_types;

		if ( empty( $post_types ) ) {
			$exclusions = array(
				'revision',
				'nav_menu_item',
				'attachment',
			);
			$raw        = get_post_types(
				array( 'public' => true ),
				'objects'
			);
			foreach ( $raw as $pt => $pto ) {
				if ( in_array( $pt, $exclusions, true ) ) {
					continue;
				}
				$post_types[ $pt ] = $pto;
			}
		}

		return is_array( $post_types )
			? $post_types
			: array();
	}
}