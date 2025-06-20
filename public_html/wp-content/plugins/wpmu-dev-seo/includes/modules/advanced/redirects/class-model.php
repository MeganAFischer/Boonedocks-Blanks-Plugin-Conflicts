<?php
/**
 * Model class for managing redirections.
 *
 * @package SmartCrawl\Modules\Advanced\Redirects
 */

namespace SmartCrawl\Modules\Advanced\Redirects;

use SmartCrawl\Models;
use SmartCrawl\Settings;

/**
 * TODO: delete after a few versions have passed with the new redirects table
 */
class Model extends Models\Model {

	const OPTIONS_KEY = 'wds-redirections';

	const OPTIONS_KEY_TYPES = 'wds-redirections-types';

	const AVAILABLE_TYPES = array( 301, 302, 307, 410, 451 );

	const DEFAULT_STATUS_TYPE = 302;

	/**
	 * Gets individual redirection value.
	 *
	 * @param string $source   Source URL.
	 * @param mixed  $fallback Optional fallback value.
	 *
	 * @return mixed (string)Redirection URL, or fallback value (defaults to (bool)false).
	 */
	public function get_redirection( $source, $fallback = false ) {
		$redirections = $this->get_all_redirections();

		$source = in_array( trailingslashit( $source ), array_keys( $redirections ), true )
			? trailingslashit( $source )
			: ( in_array( untrailingslashit( $source ), array_keys( $redirections ), true )
				? untrailingslashit( $source )
				: $source
			);

		return ! empty( $redirections[ $source ] )
			? $redirections[ $source ]
			: $fallback;
	}

	/**
	 * Get all defined redirections for current execution context
	 *
	 * @return array
	 */
	public function get_all_redirections() {
		$redirections = get_option( self::OPTIONS_KEY );
		if ( ! is_array( $redirections ) ) {
			$redirections = array();
		}

		return (array) apply_filters( $this->get_filter( 'get-all' ), array_filter( $redirections ) ); // phpcs:ignore
	}

	/**
	 * Gets individual redirection type value
	 *
	 * @param string $source Source URL.
	 *
	 * @return mixed (int)Redirection status type, or false for default
	 */
	public function get_redirection_type( $source ) {
		$types = $this->get_all_redirection_types();

		return ! empty( $types[ $source ] )
			? $types[ $source ]
			: false;
	}

	/**
	 * Get all defined redirection types for current execution context
	 *
	 * @return array
	 */
	public function get_all_redirection_types() {
		$types = get_option( self::OPTIONS_KEY_TYPES );
		if ( ! is_array( $types ) ) {
			$types = array();
		}

		return (array) apply_filters( $this->get_filter( 'get-all-types' ), array_filter( $types ) ); // phpcs:ignore
	}

	/**
	 * Updates a redirection for source URL in the list
	 *
	 * @param string $source      Source URL.
	 * @param string $redirection Redirection URL.
	 *
	 * @return bool
	 */
	public function set_redirection( $source, $redirection ) {
		$redirections            = $this->get_all_redirections();
		$redirections[ $source ] = $redirection;

		return $this->set_all_redirections( $redirections );
	}

	/**
	 * Batch-sets all redirections
	 *
	 * @param array $redirections Redirect items.
	 *
	 * @return bool
	 */
	public function set_all_redirections( $redirections ) {
		if ( ! is_array( $redirections ) ) {
			$redirections = array();
		}

		$redirections = (array) apply_filters( $this->get_filter( 'set-all' ), array_filter( $redirections ) ); // phpcs:ignore

		return update_option( self::OPTIONS_KEY, $redirections );
	}

	/**
	 * Updates a redirection type for source URL in the list
	 *
	 * @param string $source Source URL.
	 * @param int    $status Redirection status code.
	 *
	 * @return bool
	 */
	public function set_redirection_type( $source, $status ) {
		$status           = $this->get_valid_redirection_status_type( $status );
		$types            = $this->get_all_redirection_types();
		$types[ $source ] = $status;

		return $this->set_all_redirection_types( $types );
	}

	/**
	 * Returns a valid status redirection type.
	 *
	 * @param int $status Status to validate.
	 *
	 * @return mixed (int)Redirection status, or (bool)false for passthrough.
	 */
	public function get_valid_redirection_status_type( $status ) {
		return is_numeric( $status ) && in_array( (int) $status, self::AVAILABLE_TYPES, true )
			? (int) $status
			: false;
	}

	/**
	 * Batch-sets all redirection types.
	 *
	 * @param array $types Types.
	 */
	public function set_all_redirection_types( $types ) {
		if ( ! is_array( $types ) ) {
			$types = array();
		}

		$types = (array) apply_filters( $this->get_filter( 'set-all-types' ), array_filter( $types ) ); // phpcs:ignore

		return update_option( self::OPTIONS_KEY_TYPES, $types );
	}

	/**
	 * Check if we have any redirections set
	 *
	 * @return bool
	 */
	public function has_redirections() {
		return ! ! count( $this->get_all_redirections() );
	}

	/**
	 * Default status code getter
	 *
	 * @return int Default status code
	 */
	public function get_default_redirection_status_type() {
		$default_type = \smartcrawl_get_array_value( get_option( Settings::ADVANCED_MODULE ), 'default_type' );
		$status_code  = $default_type ? (int) $default_type : self::DEFAULT_STATUS_TYPE;
		$status_code  = $this->get_valid_redirection_status_type( $status_code );

		return ! empty( $status_code )
			? (int) $status_code
			: self::DEFAULT_STATUS_TYPE;
	}

	/**
	 * Build current URL string
	 *
	 * Omits query strings
	 *
	 * @return string Current URL.
	 */
	public function get_current_url() {
		$protocol = is_ssl() ? 'https:' : 'http:';
		$domain   = $_SERVER['HTTP_HOST']; // phpcs:ignore -- escaped later.

		$port = \smartcrawl_is_switch_active( 'SMARTCRAWL_OMIT_PORT_MATCHES' )
			? ''
			: $this->get_current_request_port();

		$request = wp_parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ); // phpcs:ignore -- escaped later.

		$source = esc_url_raw( $protocol . '//' . $domain . $port . $request );

		return (string) apply_filters( $this->get_filter( 'current_url' ), $source );
	}

	/**
	 * Fetches the current request port
	 *
	 * @return string Port number or empty string.
	 */
	public function get_current_request_port() {
		if ( ! isset( $_SERVER['SERVER_PORT'] ) ) {
			return '';
		}

		$port = (int) $_SERVER['SERVER_PORT'] ? ':' . (int) $_SERVER['SERVER_PORT'] : '';

		if ( is_ssl() && 443 === (int) $_SERVER['SERVER_PORT'] ) {
			$port = '';
		}
		if ( ! is_ssl() && 80 === (int) $_SERVER['SERVER_PORT'] ) {
			$port = '';
		}

		return $port;
	}

	/**
	 * Get the type of the model.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'redirection';
	}
}