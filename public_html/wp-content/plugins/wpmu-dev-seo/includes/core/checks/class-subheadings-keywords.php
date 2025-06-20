<?php
/**
 * Class to check subheading keywords.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;

/**
 * Class to check subheading keywords.
 */
class Subheadings_Keywords extends Check {

	/**
	 * Status of check result.
	 *
	 * @var bool|null
	 */
	private $state = null;

	/**
	 * Number of subheadings with focus keywords.
	 *
	 * @var int
	 */
	private $count;

	/**
	 * Returns status message.
	 *
	 * @return string
	 */
	public function get_status_msg() {
		if ( is_null( $this->state ) ) {
			return __( "You don't have any subheadings", 'wds' );
		}

		if ( $this->is_primary_keyword() ) {
			return false === $this->state
				? __( 'You haven\'t used your primary keyphrase in any subheadings', 'wds' )
				/* translators: %d: Subheading count */
				: sprintf( __( 'Your primary keyphrase was found in %d subheadings', 'wds' ), $this->count );
		} else {
			return false === $this->state
				? __( 'You haven\'t used this secondary keyphrase in any subheadings.', 'wds' )
				/* translators: %d: Subheading count */
				: sprintf( __( 'This secondary keyphrase was found in %d subheading(s).', 'wds' ), $this->count );
		}
	}

	/**
	 * Applies to get check result.
	 *
	 * @return bool
	 */
	public function apply() {
		$subjects = Html::find_content( 'h1,h2,h3,h4,h5,h6', $this->get_markup() );
		if ( empty( $subjects ) ) {
			return false;
		} // No subheadings, nothing to check.

		$count = 0;
		foreach ( $subjects as $subject ) {
			/**
			 * Convert subject into plain text to strip tags
			 */
			if ( $this->has_focus( Html::plaintext( $subject ) ) ) {
				++$count;
			}
		}

		$this->state = (bool) $count;
		$this->count = $count;

		return ! ! $this->state;
	}

	/**
	 * Retrieves recommendation message.
	 *
	 * @return array
	 */
	public function get_result() {
		return array(
			'state'      => $this->state,
			'count'      => $this->count,
			'is_primary' => $this->is_primary_keyword(),
		);
	}
}
