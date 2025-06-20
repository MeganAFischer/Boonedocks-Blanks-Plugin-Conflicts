<?php
/**
 * UPS OAuth API.
 *
 * @package WC_Shipping_UPS
 */

namespace WooCommerce\UPS\API\REST;

use WC_Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class UPS_OAuth
 *
 * Handles the OAuth authentication for the UPS REST API.
 *
 * @package WooCommerce\UPS
 */
class OAuth {

	/**
	 * The client ID.
	 *
	 * @var string
	 */
	private string $client_id;
	/**
	 * The client secret.
	 *
	 * @var string
	 */
	private string $client_secret;
	/**
	 * The OAuth endpoint.
	 *
	 * @var string
	 */
	private string $endpoint = 'https://onlinetools.ups.com/security/v1/oauth/token';
	/**
	 * Array of transient names.
	 *
	 * @var array
	 */
	private static array $transient_names = array(
		'access_token'  => 'woocommerce_ups_oauth_access_token',
		'client_id'     => 'woocommerce_ups_oauth_client_id',
		'client_secret' => 'woocommerce_ups_oauth_client_secret',
	);

	/**
	 * The logger.
	 *
	 * @var WC_Logger
	 */
	private WC_Logger $logger;

	/**
	 * UPS_OAuth constructor.
	 *
	 * @param string $client_id     The client ID.
	 * @param string $client_secret The client secret.
	 */
	public function __construct( string $client_id, string $client_secret ) {
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->logger        = wc_get_logger();
	}

	/**
	 * Check if we've successfully authenticated.
	 *
	 * @return bool
	 */
	public function is_authenticated(): bool {
		return (bool) $this->get_access_token();
	}

	/**
	 * Get an access token.
	 *
	 * @return string|bool
	 */
	public function get_access_token() {
		// If we don't have a client ID or secret, we can't authenticate.
		if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
			return false;
		}

		$this->maybe_delete_cached_access_token();

		$access_token = get_transient( self::$transient_names['access_token'] );

		if ( false === $access_token ) {

			$response = $this->request_access_token();
			if ( ! empty( $response->access_token ) && ! empty( $response->expires_in ) ) {
				set_transient( self::$transient_names['access_token'], $response->access_token, $response->expires_in );

				$access_token = $response->access_token;
			} elseif ( ! empty( $response->response->errors ) ) {
				foreach ( $response->response->errors as $error ) {
					if ( ! isset( $error->code ) || ! isset( $error->message ) ) {
						continue;
					}

					$this->logger->error( "UPS_OAuth::get_access_token: The UPS OAuth endpoint returned the following error: $error->message [$error->code]" );

					if ( is_admin() ) {
						wp_admin_notice( "UPS REST API Authentication Error: $error->message", array( 'type' => 'error' ) );
					}
				}
			}
		}

		return $access_token;
	}

	/**
	 * Maybe delete the cached access token.
	 * This is useful when the user changes the client ID or secret.
	 */
	private function maybe_delete_cached_access_token() {

		// Get the cached client ID and secret so we can compare them.
		$cached_client_id     = get_transient( self::$transient_names['client_id'] );
		$cached_client_secret = get_transient( self::$transient_names['client_secret'] );
		$cache_expiration     = 60 * 60 * 24;

		// If we don't have a cached client ID or secret, set them and return.
		if ( false === $cached_client_id || false === $cached_client_secret ) {

			// Set the cached client ID and secret.
			set_transient( self::$transient_names['client_id'], $this->client_id, $cache_expiration );
			set_transient( self::$transient_names['client_secret'], $this->client_secret, $cache_expiration );

			return;
		}

		// Clear the access token transient if the client ID or secret has changed.
		if ( $cached_client_id !== $this->client_id || $cached_client_secret !== $this->client_secret ) {

			// Clear the cached access token.
			delete_transient( self::$transient_names['access_token'] );

			// Update the cached client ID and secret.
			set_transient( self::$transient_names['client_id'], $this->client_id, $cache_expiration );
			set_transient( self::$transient_names['client_secret'], $this->client_secret, $cache_expiration );
		}
	}

	/**
	 * Request access token from the UPS OAuth API.
	 *
	 * @return object|bool
	 */
	private function request_access_token() {
		static $attempt_count = 1;

		if ( $attempt_count >= 3 ) {
			$this->logger->error( 'UPS_OAuth::request_access_token: The UPS OAuth endpoint returned an error after 3 attempts.' );

			return false;
		}

		++$attempt_count;

		$headers = array(
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode --- Reason: This is for UPS API basic auth.
			'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
			'Content-Type'  => 'application/x-www-form-urlencoded',
		);

		$body = array(
			'grant_type' => 'client_credentials',
		);

		$response = wp_remote_post(
			$this->endpoint,
			array(
				'headers' => $headers,
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			$this->logger->error( "UPS_OAuth::request_access_token: The UPS OAuth endpoint returned the following error: $error_message" );

			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}
}
