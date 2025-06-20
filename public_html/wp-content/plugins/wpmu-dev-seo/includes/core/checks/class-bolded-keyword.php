<?php
/**
 * Bolded keyword check.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;

/**
 * Class Bolded_Keyword
 */
class Bolded_Keyword extends Check {

	/**
	 * Holds check state
	 *
	 * @since 3.4.0
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
			// translators: %s keyword label.
			? sprintf( __( 'You haven\'t bolded this %s in your content.', 'wds' ), $this->get_keyword_label() )
			// translators: %s keyword label.
			: sprintf( __( 'The %s is bolded in your content.', 'wds' ), $this->get_keyword_label() );
	}

	/**
	 * Applies check to the subject.
	 *
	 * @since 3.4.0
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

		$subjects_b      = Html::find_content( 'b', $raw );
		$subjects_strong = Html::find_content( 'strong', $raw );
		if ( ! empty( $subjects_b ) || ! empty( $subjects_strong ) ) {
			$subjects = array_merge( $subjects_strong, $subjects_b );
			foreach ( $subjects as $subject ) {
				if ( $this->has_focus( $subject ) ) {
					$this->state = true;
					return true;
				}
			}
		}

		$this->state = false;

		return false;
	}

	/**
	 * Retrieves check result.
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	public function get_result() {
		return array(
			'state' => $this->state,
			'type'  => $this->get_keyword_label(),
		);
	}
}