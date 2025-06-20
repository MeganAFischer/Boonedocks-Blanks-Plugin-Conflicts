<?php
/**
 * Trait Singletone
 *
 * @package    SmartCrawl
 * @subpackage Trait
 */

namespace SmartCrawl;

if ( ! defined( 'WPINC' ) ) {
	die;
}

trait Singleton {

	/**
	 * Instance holder.
	 *
	 * @var static $instance
	 */
	private static $instance;

	/**
	 * Instance obtaining or resetting method.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $reset To reset the instance instead.
	 *
	 * @return static Called class instance.
	 */
	public static function get( $reset = false ) {
		// Just reset the instance.
		if ( $reset ) {
			self::$instance = null;

			return null;
		}

		$called_class_name = get_called_class();

		// Only if not already exist.
		if ( ! self::$instance instanceof $called_class_name ) {
			self::$instance = new $called_class_name();
		}

		return self::$instance;
	}
}