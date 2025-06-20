<?php
/**
 * Flesch readability formula class.
 *
 * @package SmartCrawl\Readability\Formulas
 */

namespace SmartCrawl\Readability\Formulas;

use SmartCrawl\SmartCrawl_String;

/**
 * Class Flesch
 *
 * Calculates the Flesch readability score for a given text.
 */
class Flesch extends Formula {

	/**
	 * Supported languages and their respective weights.
	 *
	 * @var array
	 */
	private $languages = array(
		'cs' => array(
			'base' => 206.835,
			'asl'  => 1.388,
			'asw'  => 65.090,
		),
		'de' => array(
			'base' => 180,
			'asl'  => 1,
			'asw'  => 58.5,
		),
		'en' => array(
			'base' => 206.835,
			'asl'  => 1.015,
			'asw'  => 84.6,
		),
		'fr' => array(
			'base' => 207,
			'asl'  => 1.015,
			'asw'  => 73.6,
		),
		'nl' => array(
			'base' => 206.84,
			'asl'  => 0.93,
			'asw'  => 77,
		),
		'it' => array(
			'base' => 217,
			'asl'  => 1.3,
			'asw'  => 60,
		),
		'ru' => array(
			'base' => 206.835,
			'asl'  => 1.3,
			'asw'  => 60.1,
		),
		'es' => array(
			'base' => 206.84,
			'asl'  => 1.02,
			'asw'  => 60,
		),
	);

	/**
	 * String object for readability analysis.
	 *
	 * @var SmartCrawl_String
	 */
	private $string;

	/**
	 * Language code for the text.
	 *
	 * @var string
	 */
	private $language_code;

	/**
	 * Constructor.
	 *
	 * @param SmartCrawl_String $string        String object for analysis.
	 * @param string            $language_code Language code for the text.
	 */
	public function __construct( SmartCrawl_String $string, $language_code ) {
		$this->string        = $string;
		$this->language_code = $language_code;
	}

	/**
	 * Retrieves the language configuration.
	 *
	 * @return array|null Language configuration or null if not found.
	 */
	private function get_language() {
		return \smartcrawl_get_array_value(
			$this->languages,
			$this->language_code
		);
	}

	/**
	 * Checks if the language is supported.
	 *
	 * @return bool True if the language is supported, false otherwise.
	 */
	public function is_language_supported() {
		return ! empty( $this->get_language() );
	}

	/**
	 * Calculates the Flesch readability score.
	 *
	 * @return int|false The readability score or false if calculation fails.
	 */
	public function get_score() {
		$language = $this->get_language();

		if ( empty( $language ) ) {
			return false;
		}

		return $this->calculate_score(
			$language['base'],
			$language['asl'],
			$language['asw']
		);
	}

	/**
	 * Performs the Flesch readability score calculation.
	 *
	 * @param float $base                   Base score.
	 * @param float $sentence_length_weight Weight for sentence length.
	 * @param float $syllable_weight        Weight for syllables.
	 *
	 * @return int|false The readability score or false if calculation fails.
	 */
	protected function calculate_score( $base, $sentence_length_weight, $syllable_weight ) {
		$sentence_count = $this->string->get_sentence_count();
		$word_count     = $this->string->get_word_count();
		$syllable_count = $this->string->get_syllable_count();

		if ( $sentence_count > $word_count || $word_count > $syllable_count ) {
			return false;
		}

		if ( ! $sentence_count || ! $word_count ) {
			return false;
		}

		$average_sentence_length    = $word_count / $sentence_count;
		$average_syllables_per_word = $syllable_count / $word_count;
		$score                      = $base - ( $sentence_length_weight * $average_sentence_length ) - ( $syllable_weight * $average_syllables_per_word );

		return intval( round( $score ) );
	}
}