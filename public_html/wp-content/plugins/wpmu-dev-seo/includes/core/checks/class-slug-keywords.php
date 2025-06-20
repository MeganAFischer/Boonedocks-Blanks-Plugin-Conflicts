<?php
/**
 * Class to check slug keywords.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\SmartCrawl_String;

/**
 * Class to check slug keywords.
 */
class Slug_Keywords extends Post_Check {

	/**
	 * Status of check result.
	 *
	 * @var bool|null
	 */
	private $state;

	/**
	 * Prepare focus keywords.
	 *
	 * @param array $keywords Keywords as an array.
	 *
	 * @return array
	 */
	protected function prepare_focus( $keywords ) {
		$kwds = array();
		foreach ( $keywords as $k ) {
			$keyword_string = new SmartCrawl_String( $k, $this->get_language() );
			$kwds           = array_merge( $kwds, $keyword_string->get_keywords() );
		}

		return array_map( 'sanitize_title', array_unique( array_keys( $kwds ) ) );
	}

	/**
	 * Returns status message.
	 *
	 * @return string
	 */
	public function get_status_msg() {
		return false === $this->state
			? __( "You haven't used your focus keyphrases in the page URL", 'wds' )
			: __( "You've used your focus keyphrase in the page URL", 'wds' );
	}

	/**
	 * Applies to get check result.
	 *
	 * @return bool
	 */
	public function apply() {
		$text    = $this->get_markup();
		$subject = join( ' ', preg_split( '/[\-_]/', $text ) );

		$this->state = $this->has_focus( $subject );

		return ! ! $this->state;
	}

	/**
	 * Retrieves subject markup.
	 *
	 * @return string
	 */
	public function get_markup() {
		$subject = $this->get_subject();

		if ( is_object( $subject ) ) {
			if ( ! empty( $subject->ID ) && wp_is_post_revision( $subject->ID ) ) {
				$post = get_post( wp_is_post_revision( $subject->ID ) );
			} else {
				$post = $subject;
			}
			if ( function_exists( '\get_sample_permalink' ) ) {
				list( , $draft_name ) = \get_sample_permalink( $post->ID );
			} else {
				$draft_name = '';
			}
			$post_name = $post->post_name;
			$subject   = ! empty( $post_name ) ? $post_name : $draft_name;
		}

		return $subject;
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