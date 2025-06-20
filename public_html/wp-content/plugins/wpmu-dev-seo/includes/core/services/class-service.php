<?php
/**
 * Service class for handling various services in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Services;

use SmartCrawl\Logger;

/**
 * Abstract class Service
 *
 * Provides a base for all service classes.
 */
abstract class Service {

	const INTERMEDIATE_CACHE_EXPIRY = 300;

	const ERR_CACHE_EXPIRY = 120;

	const SERVICE_SEO = 'seo';

	const SERVICE_SITE = 'site';

	const SERVICE_LIGHTHOUSE = 'lighthouse';

	/**
	 * Array of error messages.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Service factory method
	 * TODO: remove this method and implement get() static methods in individual classes
	 *
	 * @param string $type Requested service type.
	 *
	 * @return Site|Seo|Lighthouse Service instance
	 */
	public static function get( $type ) {
		$types = array( self::SERVICE_SEO, self::SERVICE_SITE, self::SERVICE_LIGHTHOUSE );
		// Default type is SEO.
		$type = ! empty( $type ) && in_array( $type, $types, true ) ? $type : self::SERVICE_SEO;

		if ( self::SERVICE_SITE === $type ) {
			$class_name = '\SmartCrawl\Services\Site';
		} elseif ( self::SERVICE_LIGHTHOUSE === $type ) {
			$class_name = '\SmartCrawl\Services\Lighthouse';
		} else {
			$class_name = '\SmartCrawl\Services\Seo';
		}

		return new $class_name();
	}

	/**
	 * Check if status code is within radix
	 *
	 * @param int $code  Code to check.
	 * @param int $base  Base to check.
	 * @param int $radix Optional increment.
	 *
	 * @return bool
	 */
	public static function is_code_within( $code, $base, $radix = 10 ) {
		$code  = (int) $code;
		$base  = (int) $base;
		$radix = (int) $radix;
		if ( ! $code || ! $base || ! $radix ) {
			return false;
		}

		$min = $base * $radix;
		$max = ( ( $base + 1 ) * $radix ) - 1;

		return $code >= $min && $code <= $max;
	}

	/**
	 * Service URL implementation
	 *
	 * @return string Remote service URL
	 */
	abstract public function get_service_base_url();

	/**
	 * Check if the user can access service functionality
	 *
	 * @return bool
	 */
	public function can_access() {
		$can_access = false;
		if ( ! $this->has_dashboard() ) {
			$can_access = $this->can_install();
		} elseif (
			class_exists( '\WPMUDEV_Dashboard' ) &&
			! empty( \WPMUDEV_Dashboard::$site ) &&
			is_callable(
				array(
					\WPMUDEV_Dashboard::$site,
					'allowed_user',
				)
			)
		) {
			$can_access = \WPMUDEV_Dashboard::$site->allowed_user();
		}

		return (bool) apply_filters(
			$this->get_filter( 'can_access' ),
			$can_access
		);
	}

	/**
	 * Check if we have dashboard installed
	 *
	 * @return bool
	 */
	public function has_dashboard() {
		return (bool) apply_filters(
			$this->get_filter( 'has_dashboard' ),
			$this->is_dashboard_active() && $this->has_dashboard_key()
		);
	}

	/**
	 * Filter/action name getter
	 *
	 * @param string $filter Filter name to convert.
	 *
	 * @return string Full filter name
	 */
	public function get_filter( $filter = false ) {
		if ( empty( $filter ) ) {
			return false;
		}
		if ( ! is_string( $filter ) ) {
			return false;
		}

		return 'wds-model-service-' . $filter;
	}

	/**
	 * Check if we have WPMU DEV Dashboard plugin installed and activated.
	 *
	 * @since 3.2.1 Removed is_admin() check.
	 *
	 * @return bool
	 */
	public function is_dashboard_active() {
		$active = class_exists( '\WPMUDEV_Dashboard' );

		return (bool) apply_filters(
			$this->get_filter( 'is_dahsboard_active' ),
			$active
		);
	}

	/**
	 * Check if we have our API key
	 *
	 * If we do, this means the user has logged into the dashboard
	 *
	 * @return bool
	 */
	public function has_dashboard_key() {
		$key = $this->get_dashboard_api_key();

		return (bool) apply_filters(
			$this->get_filter( 'has_dashboard_key' ),
			! empty( $key )
		);
	}

	/**
	 * Actual dashborad API key getter.
	 *
	 * @return string Dashboard API key
	 */
	public function get_dashboard_api_key() {
		$api_key = defined( 'WPMUDEV_APIKEY' ) && WPMUDEV_APIKEY
			? WPMUDEV_APIKEY
			: get_site_option( 'wpmudev_apikey', false );

		return apply_filters(
			$this->get_filter( 'api_key' ),
			$api_key
		);
	}

	/**
	 * Get Dashboard site ID
	 *
	 * @return int|bool
	 */
	public function get_dashboard_site_id() {
		if ( $this->has_dashboard() ) {
			return \WPMUDEV_Dashboard::$api->get_site_id();
		}

		return false;
	}

	/**
	 * Check if the user can install dashboard
	 *
	 * @return bool
	 */
	public function can_install() {
		$can_install = is_multisite()
			? current_user_can( 'manage_network_options' )
			: current_user_can( 'manage_options' );

		return (bool) apply_filters(
			$this->get_filter( 'can_install' ),
			$can_install
		);
	}

	/**
	 * Checks whether the account has current paid plan with us
	 *
	 * @return bool
	 */
	public function is_member() {
		if (
			$this->has_dashboard() &&
			$this->membership_includes_smartcrawl() &&
			defined( 'SMARTCRAWL_BUILD_TYPE' ) &&
			'full' === SMARTCRAWL_BUILD_TYPE
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current membership has access to SC.
	 *
	 * @since 3.2.1
	 *
	 * @return bool
	 */
	private function membership_includes_smartcrawl() {
		// Check if SmartCrawl is available for current membership.
		if ( class_exists( '\WPMUDEV_Dashboard' ) && isset( \WPMUDEV_Dashboard::$upgrader ) && method_exists( \WPMUDEV_Dashboard::$upgrader, 'user_can_install' ) ) {
			return \WPMUDEV_Dashboard::$upgrader->user_can_install( 167, true );
		}

		return false;
	}

	/**
	 * Clears the value from cache
	 *
	 * @param string $key Key for the value to clear.
	 *
	 * @return bool
	 */
	public function clear_cached( $key ) {
		$key = $this->get_cache_key( $key );
		if ( empty( $key ) ) {
			return false;
		}

		return delete_transient( $key );
	}

	/**
	 * Get the key used for caching
	 *
	 * @param string $key Key suffix.
	 *
	 * @return mixed Full cache key as string, or (bool)false on failure
	 */
	public function get_cache_key( $key ) {
		if ( empty( $key ) ) {
			return false;
		}

		return $this->get_filter( $key );
	}

	/**
	 * Actually perform a request on behalf of the implementing service
	 *
	 * @param string $verb Action string.
	 *
	 * @return mixed Service response hash on success, (bool)false on failure
	 */
	public function request( $verb ) {
		$response = $this->remote_call( $verb );

		return apply_filters(
			$this->get_filter( "request-{$verb}" ),
			apply_filters(
				$this->get_filter( 'request' ),
				$response,
				$verb
			)
		);
	}

	/**
	 * Actually send out remote request
	 *
	 * @param string $verb Service endpoint to call.
	 *
	 * @return mixed Service response hash on success, (bool)false on failure
	 */
	protected function remote_call( $verb ) {
		if ( empty( $verb ) || ! in_array( $verb, $this->get_known_verbs(), true ) ) {
			return false;
		}

		$cacheable = $this->is_cacheable_verb( $verb );

		if ( $cacheable ) {
			$cached = $this->get_cached( $verb );
			if ( false !== $cached ) {
				Logger::debug( "Fetching [{$verb}] result from cache." );

				return $cached;
			}
		}

		// Check to see if we have a valid error cache still.
		$error = $this->get_cached_error( $verb );
		if ( ! empty( $error ) ) {
			Logger::debug( "Error cache still in effect for [{$verb}]" );
			$errors = is_array( $error ) ? $error : array( $error );
			foreach ( $errors as $err ) {
				$this->set_error_message( $err );
			}

			return false;
		}

		$remote_url = $this->get_request_url( $verb );
		if ( empty( $remote_url ) ) {
			Logger::warning( "Unable to construct endpoint URL for [{$verb}]." );

			return false;
		}

		$request_arguments = $this->get_request_arguments( $verb );
		if ( empty( $request_arguments ) ) {
			Logger::warning( "Unable to obtain request arguments for [{$verb}]." );

			return false;
		}

		Logger::debug( "Sending a remote request to [{$remote_url}] ({$verb})" );
		$response = wp_remote_request( $remote_url, $request_arguments );
		Logger::debug( "Received a response from [{$remote_url}] ({$verb})" . var_export( $response, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		if ( is_wp_error( $response ) ) {
			Logger::error( "We were not able to communicate with [{$remote_url}] ({$verb})." );
			if ( is_callable( array( $response, 'get_error_messages' ) ) ) {
				$msgs = $response->get_error_messages();
				foreach ( $msgs as $msg ) {
					$this->set_error_message( $msg );
				}
				$this->set_cached_error( $verb, $msgs );
			}

			return false;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			Logger::error( "We had an error communicating with [{$remote_url}]:[{$response_code}] ({$verb})." );
			$this->handle_error_response( $response, $verb );

			return false;
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = $this->postprocess_response( $body );

		if ( $cacheable ) {
			Logger::debug( "Setting cache for [{$verb}]" );
			$this->set_cached( $verb, $result );
		}

		return $result;
	}

	/**
	 * Returns a flat list of known verbs as strings
	 *
	 * @return array
	 */
	abstract public function get_known_verbs();

	/**
	 * Determine if the action verb is able to be locally cached
	 *
	 * @param string $verb Action string.
	 *
	 * @return bool
	 */
	abstract public function is_cacheable_verb( $verb );

	/**
	 * Get cached value corresponding to internal key
	 *
	 * @param string $key Key to check.
	 *
	 * @return mixed Cached value, or (bool)false on failure
	 */
	public function get_cached( $key ) {
		$key = $this->get_cache_key( $key );
		if ( empty( $key ) ) {
			return false;
		}

		return get_transient( $key );
	}

	/**
	 * Special case error cache getter
	 *
	 * @param string $verb Verb to check cached errors for.
	 *
	 * @return mixed Cached error or (bool) false
	 */
	public function get_cached_error( $verb ) {
		if ( empty( $verb ) ) {
			return false;
		}

		return $this->get_cached( "{$verb}-error" );
	}

	/**
	 * Adds error message to the errors queue
	 *
	 * @param string $msg Error message.
	 */
	protected function set_error_message( $msg ) {
		Logger::error( $msg );
		$this->errors[] = $msg;
	}

	/**
	 * Get the full URL to perform the service request
	 *
	 * @param string $verb Action string.
	 *
	 * @return mixed Full URL as string or (bool)false on failure
	 */
	abstract public function get_request_url( $verb );

	/**
	 * Spawn the arguments for WP HTTP API request call
	 *
	 * @param string $verb Action string.
	 *
	 * @return mixed Array of WP HTTP API arguments on success, or (bool)false on failure
	 */
	abstract public function get_request_arguments( $verb );

	/**
	 * Special case error cache setter
	 *
	 * @param string $verb  Verb to set error cache for.
	 * @param mixed  $error Error to set.
	 *
	 * @return bool
	 */
	public function set_cached_error( $verb, $error ) {
		if ( empty( $verb ) ) {
			return false;
		}

		return $this->set_cached( "{$verb}-error", $error, self::ERR_CACHE_EXPIRY );
	}

	/**
	 * Sets cached value to the corresponding key
	 *
	 * @param string $key    Key for the value to set.
	 * @param mixed  $value  Value to set.
	 * @param int    $expiry Optional expiry time, in secs (one of the class expiry constants).
	 *
	 * @return bool
	 */
	public function set_cached( $key, $value, $expiry = false ) {
		$key = $this->get_cache_key( $key );
		if ( empty( $key ) ) {
			return false;
		}

		return set_transient( $key, $value, $this->get_cache_expiry( $expiry ) );
	}

	/**
	 * Get cache expiry, in seconds
	 *
	 * @param int $expiry Expiry time to approximate.
	 *
	 * @return int Cache expiry time, in seconds
	 */
	public function get_cache_expiry( $expiry = false ) {
		$expiry = ! empty( $expiry ) && is_numeric( $expiry )
			? (int) $expiry
			: self::INTERMEDIATE_CACHE_EXPIRY;

		return (int) apply_filters(
			$this->get_filter( 'cache_expiry' ),
			$expiry
		);
	}

	/**
	 * Handles error response (non-200) from service
	 *
	 * @param object $response WP HTTP API response.
	 * @param string $verb     Request verb.
	 */
	abstract public function handle_error_response( $response, $verb );

	/**
	 * Post-process the response body
	 *
	 * Passthrough as default implementation
	 *
	 * @param string $body Response body.
	 *
	 * @return mixed
	 */
	protected function postprocess_response( $body ) {
		return json_decode( $body, true );
	}

	/**
	 * Gets all error message strings
	 *
	 * @return array
	 */
	public function get_errors() {
		return (array) $this->errors;
	}

	/**
	 * Checks if we have any errors this far
	 *
	 * @return bool
	 */
	public function has_errors() {
		return ! empty( $this->errors );
	}

	/**
	 * Silently Sets all errors
	 *
	 * @param array $errs Errors to set.
	 *
	 * @return void|bool
	 */
	protected function set_all_errors( $errs ) {
		if ( ! is_array( $errs ) ) {
			return false;
		}
		$this->errors = $errs;
	}

	/**
	 * Gets the timeout value for service requests.
	 *
	 * @return int The timeout value in seconds.
	 */
	protected function get_timeout() {
		return defined( 'SMARTCRAWL_SERVICE_REQUEST_TIMEOUT' )
			? \SMARTCRAWL_SERVICE_REQUEST_TIMEOUT
			: 5;
	}
}