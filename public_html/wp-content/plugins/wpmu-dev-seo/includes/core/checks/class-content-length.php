<?php
/**
 * Content length check.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;
use SmartCrawl\Cache\String_Cache;

/**
 * Class Smartcrawl_Check_Content_Length
 */
class Content_Length extends Check {

	/**
	 * Holds check state
	 *
	 * @var int|bool
	 */
	private $state;

	/**
	 * Word count.
	 *
	 * @var null
	 */
	private $word_count = null;

	/**
	 * Retrieves the message for the check.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_status_msg() {
		if ( - 1 === $this->state ) {
			return __( "Your article doesn't have any words yet, you might want to add some content", 'wds' );
		}

		return false === $this->state
			// translators: %s Word count.
			? sprintf( __( 'The text contains %1$d words which is less than the recommended minimum of %2$d words', 'wds' ), $this->word_count, $this->get_min() )
			// translators: %s Word count.
			: sprintf( __( 'The text contains %1$d words which is more than the recommended minimum of %2$d words', 'wds' ), $this->word_count, $this->get_min() );
	}

	/**
	 * Retrieves min value.
	 *
	 * @return int
	 */
	public function get_min() {
		return 300;
	}

	/**
	 * Applies check to the subject.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function apply() {
		$markup = $this->get_markup();
		if ( empty( $markup ) ) {
			$this->state = - 1;

			return false;
		}

		$text   = Html::plaintext( $markup );
		$string = String_Cache::get()->get_string( $text, $this->get_language() );

		$count            = $string->get_word_count();
		$this->word_count = $count;

		$this->state = $count > $this->get_min();

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
		return array(
			'state' => $this->state,
			'min'   => $this->get_min(),
			'count' => $this->word_count,
		);
	}
}