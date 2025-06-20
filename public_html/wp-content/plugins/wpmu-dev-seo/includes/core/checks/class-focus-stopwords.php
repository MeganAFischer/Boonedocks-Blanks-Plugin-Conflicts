<?php
/**
 * Focus stopwords check.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\SmartCrawl_String;

/**
 * Class Smartcrawl_Check_Focus_Stopwords
 */
class Focus_Stopwords extends Check {

	/**
	 * State.
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
			? __( 'There are stop words in focus keyphrases', 'wds' )
			: __( 'Focus to the point', 'wds' );
	}

	/**
	 * Applies check to the subject.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function apply() {
		$focus = $this->get_raw_focus();
		$state = true;
		foreach ( $focus as $phrase ) {
			$phrase = new SmartCrawl_String( $phrase, $this->get_language() );
			if ( ! $phrase->has_stopwords() ) {
				continue;
			}
			$state = false;
			break;
		}

		$this->state = $state;

		return ! ! $this->state;
	}

	/**
	 * Retrieves check result.
	 *
	 * @since 3.4.0
	 *
	 * @return array
	 */
	public function get_result() {
		$focus = $this->get_raw_focus();

		if ( count( $focus ) > 1 ) {
			$phrase = __( 'keyphrases or key phrases', 'wds' );
		} else {
			$subj   = end( $focus );
			$phrase = false === strpos( $subj, ' ' )
				? __( 'Keyphrases', 'wds' )
				: __( 'key phrase', 'wds' );
		}

		return array(
			'state'  => $this->state,
			'phrase' => $phrase,
		);
	}
}