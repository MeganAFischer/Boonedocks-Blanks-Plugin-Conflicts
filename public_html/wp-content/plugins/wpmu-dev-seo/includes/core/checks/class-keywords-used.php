<?php
/**
 * Check to find out the keywords in other articles.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

/**
 * Class to find out the keywords in articles.
 */
class Keywords_Used extends Post_Check {

	/**
	 * Hold check state
	 *
	 * @var bool
	 */
	private $state;

	/**
	 * Hold a list of other post ids which include primary keyword.
	 *
	 * @var array
	 */
	private $used_ids;

	/**
	 * Retrieves the message for the check.
	 *
	 * @return string
	 */
	public function get_status_msg() {
		return ! $this->state
			? __( 'Primary focus keyphrase is already used on another post/page', 'wds' )
			: __( 'Primary focus keyphrase isn’t used on another post/page', 'wds' );
	}

	/**
	 * Applies check to the subject.
	 *
	 * @return bool
	 */
	public function apply() {
		$kws = $this->get_focus();
		if ( empty( $kws ) ) {
			return true;
		}

		global $wpdb;
		$wild        = '%';
		$likes_array = array();
		foreach ( $kws as $kw_id => $kw ) {
			$likes_array[] = 'meta_value LIKE %s';
			$kws[ $kw_id ] = $wild . $wpdb->esc_like( $kw ) . $wild;
		}

		$subject    = $this->get_subject();
		$subject_id = $this->get_subject_post_id( $subject );

		$likes     = join( ' AND ', $likes_array );
		$query     = "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wds_focus-keywords' AND post_id != $subject_id AND $likes ORDER BY post_id DESC";
		$meta_rows = $wpdb->get_results( $wpdb->prepare( $query, ...$kws ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$meta_rows = empty( $meta_rows ) ? array() : $meta_rows;
		$post_ids  = $this->filter_out_supersets( $meta_rows );

		$this->state    = ! count( $post_ids );
		$this->used_ids = $post_ids;

		return $this->state;
	}

	/**
	 * "iphone access france" is a superset of "iphone access" and it should be ignored
	 *
	 * @param array $meta_rows Meta rows.
	 *
	 * @return array
	 */
	private function filter_out_supersets( $meta_rows ) {
		$filtered = array();
		foreach ( $meta_rows as $meta_row ) {
			$post_id   = (int) \smartcrawl_get_array_value( $meta_row, 'post_id' );
			$raw_focus = \smartcrawl_get_array_value( $meta_row, 'meta_value' );
			$focus     = $this->prepare_focus( array( $raw_focus ) );

			if ( count( $this->get_focus() ) === count( $focus ) ) {
				$filtered[] = $post_id;
			}
		}

		return $filtered;
	}

	/**
	 * Retrieves post id if subject is post.
	 *
	 * @return int|\WP_Post
	 */
	public function get_post_id() {
		$subject = $this->get_subject();

		return is_object( $subject ) ? $subject->ID : $subject;
	}

	/**
	 * Gets check result.
	 *
	 * @return array
	 */
	public function get_result() {
		$result = array(
			'state' => $this->state,
		);

		if ( ! $this->state && is_array( $this->used_ids ) && count( $this->used_ids ) > 0 ) {
			$result['used_in'] = array();

			foreach ( array_slice( $this->used_ids, 0, 10, true ) as $post_id ) {
				$result['used_in'][] = array(
					'title'     => get_the_title( $post_id ),
					'type'      => esc_html( get_post_type_object( get_post_type( $post_id ) )->labels->singular_name ),
					'permalink' => esc_html( get_permalink( $post_id ) ),
					'edit_link' => esc_url_raw( get_edit_post_link( $post_id, 'url' ) ),
				);
			}
		}

		return $result;
	}

	/**
	 * Retrieves subject post id.
	 *
	 * @param mixed $subject Subject.
	 *
	 * @return int
	 */
	private function get_subject_post_id( $subject ) {
		if ( is_a( $subject, '\WP_Post' ) ) {
			$post_parent = wp_is_post_revision( $subject->ID );

			if ( $post_parent ) {
				$subject_id = $post_parent;
			} else {
				$subject_id = $subject->ID;
			}
		} else {
			$subject_id = - 1;
		}

		return $subject_id;
	}
}