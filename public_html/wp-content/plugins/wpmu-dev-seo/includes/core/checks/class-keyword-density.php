<?php
/**
 * Keyword density check.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Cache\String_Cache;
use SmartCrawl\Html;

/**
 * Class Smartcrawl_Check_Keyword_Density
 */
class Keyword_Density extends Check {

	/**
	 * Holds check state
	 *
	 * @var bool
	 */
	private $state;

	/**
	 * Holds keyword density value.
	 *
	 * @var null|int
	 */
	private $density = null;

	/**
	 * Retrieves the message for the check.
	 *
	 * @return string
	 */
	public function get_status_msg() {
		return $this->choose_status_message(
			__( "You haven't used any keyphrases yet", 'wds' ),
			// translators: %d low, %d high.
			__( 'Your %4$s density is between %1$d%% and %2$d%%', 'wds' ),
			// translators: %d low.
			__( 'Your %4$s density is less than %1$d%%', 'wds' ),
			// translators: %d high.
			__( 'Your %4$s density is greater than %2$d%%', 'wds' )
		);
	}

	/**
	 * Select status message based on density.
	 *
	 * @param string $no_keywords     No keywords message.
	 * @param string $correct_density Correct density keyword.
	 * @param string $low_density     Low density keyword.
	 * @param string $high_density    High density keyword.
	 *
	 * @return string
	 */
	private function choose_status_message( $no_keywords, $correct_density, $low_density, $high_density ) {
		$keyword_density = $this->density ? round( $this->density, 2 ) : 0;

		if ( 0 === $keyword_density ) {
			$message = $no_keywords;
		} elseif ( $this->state ) {
			$message = $correct_density;
		} elseif ( $keyword_density < $this->get_min() ) {
				$message = $low_density;
		} else {
			$message = $high_density;
		}

		return sprintf( $message, $this->get_min(), $this->get_max(), $keyword_density, $this->get_keyword_label() );
	}

	/**
	 * Retrieves minimum recommended density.
	 *
	 * @return int
	 */
	public function get_min() {
		return 1;
	}

	/**
	 * Retrieves maximum recommended density.
	 *
	 * @return int
	 */
	public function get_max() {
		return 3;
	}

	/**
	 * Applies check to the subject.
	 *
	 * @return bool
	 */
	public function apply() {
		$markup = $this->get_markup();
		if ( empty( $markup ) ) {
			$this->state = false;

			return false;
		}

		$kws = $this->get_focus();
		if ( empty( $kws ) ) {
			$this->state = true;

			return true; // Can't determine kw density.
		}
		$text      = Html::plaintext( $markup );
		$string    = String_Cache::get()->get_string( $text, $this->get_language() );
		$stopwords = $string->get_language_stopwords();

		$filtered_kws  = array( strtolower( implode( ' ', $kws ) ) );
		$filtered_text = implode( ' ', array_diff( explode( ' ', strtolower( $text ) ), $stopwords ) );
		$total_words   = str_word_count( $filtered_text );

		$densities = array();
		foreach ( $filtered_kws as $kw ) {
			$densities[ $kw ] = $total_words > 0 ? ( substr_count( $filtered_text, strtolower( $kw ) ) / $total_words ) * 100 : 0;
		}

		$this->density = ! empty( $densities ) ? array_sum( $densities ) / count( $densities ) : 0;
		$this->state   = $this->density >= $this->get_min() && $this->density <= $this->get_max();

		return ! ! $this->state;
	}

	/**
	 * Retrieves check result.
	 *
	 * @return array
	 */
	public function get_result() {
		$density = $this->density ? round( $this->density, 2 ) : 0;

		return array(
			'state'   => $this->state,
			'density' => $density,
			'min'     => $this->get_min(),
			'max'     => $this->get_max(),
			'type'    => $this->get_keyword_label(),
		);
	}
}