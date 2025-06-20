<?php
/**
 * Meta description length check.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;
use SmartCrawl\Cache\Post_Cache;
use SmartCrawl\String_Utils;

/**
 * Class instance to check meta description length
 */
class Metadesc_Length extends Post_Check {
	/**
	 * Holds check state
	 *
	 * @var int
	 */
	private $state;

	/**
	 * Holds length
	 *
	 * @var int
	 */
	protected $length;

	/**
	 * Retrieves the message for the check.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_status_msg() {
		if ( ! is_numeric( $this->state ) ) {
			return __( 'Your meta description is a good length', 'wds' );
		}

		return 0 === $this->state
			? __( "You haven't specified a meta description yet", 'wds' )
			: ( $this->state > 0
				/* translators: %d: Maximum length of characters */
				? sprintf( __( 'Your meta description is greater than %d characters', 'wds' ), $this->get_max() )
				/* translators: %d: Minimum length of characters */
				: sprintf( __( 'Your meta description is less than %d characters', 'wds' ), $this->get_min() )
			);
	}

	/**
	 * Retrieves the max length for meta description.
	 *
	 * @since 3.4.0
	 *
	 * @return int
	 */
	public function get_max() {
		return \smartcrawl_metadesc_max_length();
	}

	/**
	 * Retrieves the min length for meta description.
	 *
	 * @since 3.4.0
	 *
	 * @return int
	 */
	public function get_min() {
		return \smartcrawl_metadesc_min_length();
	}

	/**
	 * Applies check to the subject.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function apply() {
		$post = $this->get_subject();

		if ( ! is_object( $post ) || empty( $post->ID ) ) {
			$subject = $this->get_markup();
		} else {
			$smartcrawl_post = Post_Cache::get()->get_post( $post->ID );
			$subject         = $smartcrawl_post
				? $smartcrawl_post->get_meta_description()
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
		return array( 'state' => $this->state );
	}
}