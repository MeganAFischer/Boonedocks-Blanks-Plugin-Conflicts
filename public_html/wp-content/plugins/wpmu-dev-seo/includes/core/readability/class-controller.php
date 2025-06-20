<?php
/**
 * Controller for readability analysis.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Readability;

use SmartCrawl\Models\Analysis;
use SmartCrawl\Singleton;
use SmartCrawl\Controllers;

/**
 * Class Controller
 */
class Controller extends Controllers\Controller {

	use Singleton;

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	protected function init() {
	}

	/**
	 * Check if a language is supported for readability analysis.
	 *
	 * @since 3.4.0
	 *
	 * @param string $lang Language to check (By default current language).
	 *
	 * @return bool
	 */
	public function is_language_supported( $lang = '' ) {
		$analysis_model = new Analysis();

		return $analysis_model->is_readability_lang_supported( $lang );
	}
}