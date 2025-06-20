<?php
/**
 * Abstract class for admin pages.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin\Pages;

if ( ! defined( 'WPINC' ) ) {
	die;
}

use SmartCrawl\Controllers\Controller;

/**
 * Page Controller Abstract Class.
 */
abstract class Page extends Controller {

	/**
	 * Defines action hooks for this controller.
	 */
	protected function init() {
		add_filter( 'admin_body_class', array( $this, 'add_body_class' ), 20 );
	}

	/**
	 * Adds a class to the body tag.
	 *
	 * @param string $classes The existing classes of the body tag.
	 *
	 * @return string The modified classes of the body tag.
	 */
	public function add_body_class( $classes ) {
		$sui_class = \smartcrawl_sui_class();
		$screen    = get_current_screen();

		if (
			$screen->id
			&& strpos( $screen->id, $this->get_menu_slug() ) !== false
			&& strpos( $classes, $sui_class ) === false
		) {
			$classes .= " {$sui_class} ";
		}

		return $classes;
	}

	/**
	 * Abstract method to retrieve the menu slug.
	 *
	 * @return string The menu slug.
	 */
	abstract public function get_menu_slug();
}