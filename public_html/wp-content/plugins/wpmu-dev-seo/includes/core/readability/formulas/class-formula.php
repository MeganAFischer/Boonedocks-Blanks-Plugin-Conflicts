<?php
/**
 * Abstract class for readability formulas.
 *
 * @package SmartCrawl\Readability\Formulas
 */

namespace SmartCrawl\Readability\Formulas;

use SmartCrawl\SmartCrawl_String;

/**
 * Abstract class Formula
 *
 * Provides a base for readability formulas.
 */
abstract class Formula {

	/**
	 * Constructor.
	 *
	 * @param SmartCrawl_String $string        String object for analysis.
	 * @param string            $language_code Language code for the text.
	 */
	abstract public function __construct( SmartCrawl_String $string, $language_code );

	/**
	 * Calculates the readability score.
	 *
	 * @return int The readability score.
	 */
	abstract public function get_score();
}