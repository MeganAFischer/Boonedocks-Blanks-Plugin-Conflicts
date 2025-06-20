<?php
/**
 * Title secondary keyword check.
 *
 * This is a duplication of main check, but created to show as less important
 * check for secondary keywords.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

/**
 * Class Title_Secondary_Keywords
 */
class Title_Secondary_Keywords extends Title_Keywords {

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
			? __( 'You didn\'t use this secondary keyphrase in the title.', 'wds' )
			: __( 'You have used this secondary keyphrase in the title.', 'wds' );
	}

	/**
	 * Retrieves check result.
	 *
	 * @return array
	 */
	public function get_result() {
		return array( 'state' => $this->state );
	}
}