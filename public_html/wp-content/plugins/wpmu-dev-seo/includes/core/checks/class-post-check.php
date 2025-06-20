<?php
/**
 * Post-specific check abstraction class
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;

/**
 * Smartcrawl_Check_Post_Abstract
 */
abstract class Post_Check extends Check {

	/**
	 * Gets post content markup
	 *
	 * @return string Decorated markup
	 */
	public function get_markup() {
		$subject = parent::get_markup();

		if ( is_object( $subject ) ) {
			return Html::decorate( $subject->post_content );
		}

		return $subject;
	}

	/**
	 * Gets subject directly
	 */
	public function get_subject() {
		return parent::get_markup();
	}
}