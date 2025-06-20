<?php
/**
 * Class to check paragraph keywords.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;

/**
 * Class to check paragraph keywords.
 */
class Para_Keywords extends Check {

	/**
	 * Status of check result.
	 *
	 * @var bool|null
	 */
	private $state;

	/**
	 * Returns status message.
	 *
	 * @return string
	 */
	public function get_status_msg() {
		return false === $this->state
			? __( "You haven't included the focus keyphrases in the first paragraph of your article", 'wds' )
			: __( 'The focus keyphrase appears in the first paragraph of your article', 'wds' );
	}

	/**
	 * Applies to get check result.
	 *
	 * @return bool
	 */
	public function apply() {
		$raw     = $this->get_markup();
		$content = wp_strip_all_tags( $raw );
		if ( ! ( $content ) ) {
			$this->state = false;

			return false;
		}

		$subjects = Html::find_content( 'p', $raw );
		if ( empty( $subjects ) ) {
			$this->state = true;

			return true;
		} // No paragraphs whatsoever, nothing to check.

		$subject = reset( $subjects );
		if ( empty( $subject ) ) {
			$this->state = false;

			return false;
		} // First paragraph empty, this fails.

		/**
		 * Convert subject into plain text to strip tags
		 */
		$this->state = $this->has_focus( Html::plaintext( $subject ) );

		return ! ! $this->state;
	}

	/**
	 * Retrieves recommendation message.
	 *
	 * @return array
	 */
	public function get_result() {
		return array( 'state' => $this->state );
	}
}