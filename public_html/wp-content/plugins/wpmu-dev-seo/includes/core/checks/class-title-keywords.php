<?php
/**
 * Title keyword check.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;
use SmartCrawl\Cache\Post_Cache;

/**
 * Class Title_Keywords
 */
class Title_Keywords extends Post_Check {

	/**
	 * Holds check state
	 *
	 * @since 3.4.0
	 *
	 * @var int|bool
	 */
	protected $state;

	/**
	 * Retrieves the message for the check.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_status_msg() {
		if ( - 1 === $this->state ) {
			return __( 'We couldn\'t find a title to check for keyphrases', 'wds' );
		}

		return false === $this->state
			? __( "Your focus keyphrase(s) aren't used in the SEO title", 'wds' )
			: __( 'The SEO title contains your focus keyphrase(s)', 'wds' );
	}

	/**
	 * Applies check to the subject.
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
				? $smartcrawl_post->get_meta_title()
				: '';
		}

		$this->state = $this->has_focus( $subject );

		return ! ! $this->state;
	}

	/**
	 * Retrieves check result.
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	public function get_result() {
		return array( 'state' => $this->state );
	}
}