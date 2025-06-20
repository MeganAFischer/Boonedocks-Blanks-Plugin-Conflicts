<?php
/**
 * Site class for handling site-related services in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Services;

/**
 * Pass through service class
 *
 * Used to check membership info throughout the non-service code
 */
class Site extends Service {

	/**
	 * Retrieves the service base URL.
	 */
	public function get_service_base_url() {
	}

	/**
	 * Retrieves the request URL for the given verb.
	 *
	 * @param string $verb The verb to get the request URL for.
	 */
	public function get_request_url( $verb ) {
	}

	/**
	 * Retrieves the request arguments for the given verb.
	 *
	 * @param string $verb The verb to get the request arguments for.
	 */
	public function get_request_arguments( $verb ) {
	}

	/**
	 * Retrieves known verbs.
	 */
	public function get_known_verbs() {
	}

	/**
	 * Checks if the verb is cacheable.
	 *
	 * @param string $verb The verb to check.
	 */
	public function is_cacheable_verb( $verb ) {
	}

	/**
	 * Handles the error response.
	 *
	 * @param object $response The response to handle.
	 * @param string $verb     The verb that caused the error.
	 */
	public function handle_error_response( $response, $verb ) {
	}
}