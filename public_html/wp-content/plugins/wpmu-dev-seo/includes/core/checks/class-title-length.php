<?php
/**
 * Title length check.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;
use SmartCrawl\Cache\Post_Cache;
use SmartCrawl\String_Utils;

/**
 * Class instance to check title length
 */
class Title_Length extends Post_Check {

	/**
	 * Holds check state
	 *
	 * @var int
	 */
	private $state;

	/**
	 * Title length.
	 *
	 * @var int
	 */
	private $length;

	/**
	 * Retrieves the message for the check.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_status_msg() {
		if ( ! is_numeric( $this->state ) ) {
			return __( 'Your SEO title is a good length', 'wds' );
		}

		return 0 === $this->state
			? __( "You haven't added an SEO title yet", 'wds' )
			: ( $this->state > 0
				? sprintf( __( 'Your SEO title is too long', 'wds' ), $this->get_max() )
				: sprintf( __( 'Your SEO title is too short', 'wds' ), $this->get_min() )
			);
	}

	/**
	 * Retrieves the max length for title.
	 *
	 * @since 3.4.0
	 *
	 * @return int
	 */
	public function get_max() {
		return \smartcrawl_title_max_length();
	}

	/**
	 * Retrieves the min length for title.
	 *
	 * @since 3.4.0
	 *
	 * @return int
	 */
	public function get_min() {
		return \smartcrawl_title_min_length();
	}

	/**
	 * Applies check to the subject.
	 *
	 * @return bool
	 */
	public function apply() {
		$post       = $this->get_subject();
		$post_cache = Post_Cache::get();

		if ( ! is_object( $post ) || empty( $post->ID ) ) {
			$subject = $this->get_markup();
		} elseif ( wp_is_post_revision( $post->ID ) && ! empty( $post->post_title ) ) {
			$parent_post_id = wp_is_post_revision( $post->ID );
			$parent_post    = $post_cache->get_post( $parent_post_id );
			$parent_title   = $parent_post
				? $parent_post->get_title()
				: '';
			$parent_subject = $parent_post
				? $parent_post->get_meta_title()
				: '';
			$subject        = preg_replace( '/' . preg_quote( $parent_title, '/' ) . '/', $post->post_title, $parent_subject );
		} else {
			$smartcrawl_post = $post_cache->get_post( $post->ID );
			$subject         = $smartcrawl_post
				? $smartcrawl_post->get_meta_title()
				: '';
		}

		$this->state  = $this->is_within_char_length( $subject, $this->get_min(), $this->get_max() );
		$this->length = String_Utils::len( $subject );

		return ! is_numeric( $this->state );
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
			'length' => $this->length,
			'state'  => $this->state,
		);
	}
}