<?php
/**
 * Underscores check for page URLs.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

/**
 * Class Slug_Underscores
 */
class Slug_Underscores extends Post_Check {

	/**
	 * Holds state reference
	 *
	 * @var bool
	 */
	private $state;

	/**
	 * Retrieves the message for the check.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_status_msg() {
		return false === $this->state
			? __( 'Your URL contains underscores', 'wds' )
			: __( 'Your URL doesn’t contain underscores', 'wds' );
	}

	/**
	 * Applies check to the subject.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function apply() {
		$this->state = false === strpos( $this->get_markup(), '_' );

		return $this->state;
	}

	/**
	 * Retrieves markup data for the check.
	 *
	 * @since 3.4.0
	 *
	 * @return mixed|string|\WP_Post
	 */
	public function get_markup() {
		$post_id = $this->get_post_id();
		// Get parent ID if post revision.
		$post_parent = wp_is_post_revision( $post_id );
		// If it's a revision use parent post ID.
		if ( $post_parent ) {
			$post_id = $post_parent;
		}

		if ( function_exists( '\get_sample_permalink' ) ) {
			list( , $name ) = get_sample_permalink( $post_id );

			return $name;
		}

		return '';
	}

	/**
	 * Retrieves check result.
	 *
	 * @since 3.4.0
	 *
	 * @return array
	 */
	public function get_result() {
		return array(
			'state'   => $this->state,
			'mark_up' => $this->get_markup(),
		);
	}
}