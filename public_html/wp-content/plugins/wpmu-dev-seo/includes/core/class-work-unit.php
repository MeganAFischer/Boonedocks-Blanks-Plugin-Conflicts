<?php
/**
 * Class Work_Unit
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

/**
 * Abstract class Work_Unit
 */
abstract class Work_Unit {

	/**
	 * Holds reference to WP error instance
	 *
	 * @var \WP_Error $_error
	 */
	protected $error;

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$this->reset_errors();
	}

	/**
	 * Adds error to the queue
	 *
	 * @param string $code Error code.
	 * @param string $msg  Error message.
	 *
	 * @return bool Status
	 */
	public function add_error( $code, $msg ) {
		$prefix     = $this->get_filter_prefix();
		$error_code = "{$prefix}::{$code}";

		Logger::debug( "({$error_code}) $msg" );

		$this->error->add( $error_code, $msg );

		return true;
	}

	/**
	 * Get prefix for filters.
	 *
	 * @return mixed
	 */
	abstract public function get_filter_prefix();

	/**
	 * Gets a list of detected errors.
	 *
	 * @return array List of detected errors, as check => msg pairs.
	 */
	public function get_errors() {
		$error_codes = $this->error->get_error_codes();
		$errors      = array();

		if ( empty( $error_codes ) ) {
			return $errors;
		}

		foreach ( $error_codes as $code ) {
			$errors[ $code ] = $this->error->get_error_message( $code );
		}

		return $errors;
	}

	/**
	 * Reset errors list.
	 *
	 * @since 3.4.0
	 */
	public function reset_errors() {
		$this->error = new \WP_Error();
	}

	/**
	 * Wrapper for `apply_filters` core call
	 *
	 * Accepts variable length of parameters, the first one
	 * being a filter suffix, the rest are passed as parameters.
	 *
	 * @return mixed Whatever the filter returns
	 */
	public function apply_filters() {
		$args = func_get_args();
		if ( empty( $args ) ) {
			return false;
		}

		$filter = array_splice( $args, 0, 1 );
		array_unshift( $args, $this->get_filter( reset( $filter ) ) );

		return call_user_func_array( 'apply_filters', $args );
	}

	/**
	 * Expands filter suffix name
	 *
	 * @param string $suffix Filter suffix.
	 *
	 * @return string Full filter name
	 */
	public function get_filter( $suffix ) {
		$prefix = $this->get_filter_prefix();

		return "{$prefix}-{$suffix}";
	}

}