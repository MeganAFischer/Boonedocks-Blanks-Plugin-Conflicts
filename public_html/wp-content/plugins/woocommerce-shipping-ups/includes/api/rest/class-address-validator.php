<?php
/**
 * Address validator class.
 *
 * @package WC_Shipping_UPS
 */

namespace WooCommerce\UPS\API\REST;

use WooCommerce\UPS\API\Abstract_Address_Validator;
use WP_Error;

defined( 'ABSPATH' ) || exit;

require_once WC_SHIPPING_UPS_PLUGIN_DIR . '/includes/api/class-abstract-address-validator.php';

/**
 * Class Address_Validator
 */
class Address_Validator extends Abstract_Address_Validator {

	/**
	 * The UPS access token.
	 *
	 * @var string
	 */
	private string $access_token;

	/**
	 * The address validation request.
	 *
	 * @var array
	 */
	private array $request = array();

	/**
	 * Address_Validator constructor.
	 *
	 * @param array  $address_to_validate The address to validate.
	 * @param string $access_token        A UPS access token.
	 */
	public function __construct( array $address_to_validate, string $access_token ) {
		parent::__construct( $address_to_validate );

		$this->access_token = $access_token;
	}

	/**
	 * Build the address validation request.
	 *
	 * @return void
	 */
	public function build_address_validation_request() {
		$address = $this->get_address_to_validate();

		$address_line = array( $address['address_1'] );

		if ( ! empty( $address['address_2'] ) ) {
			$address_line[] = $address['address_2'];
		}

		$this->request = array(
			'XAVRequest' => array(
				'AddressKeyFormat' => array(
					'AddressLine'        => $address_line,
					'PoliticalDivision2' => $address['city'],
					'PoliticalDivision1' => $address['state'],
					'PostcodePrimaryLow' => $address['postcode'],
					'CountryCode'        => $address['country'],
				),
			),
		);
	}

	/**
	 * Get the address validation request.
	 */
	public function get_request(): array {
		return $this->request;
	}

	/**
	 * Get the address validation response.
	 */
	public function get_response() {
		return $this->response;
	}

	/**
	 * Check if the address validation request found a potential match.
	 *
	 * @return bool
	 */
	public function found_potential_match(): bool {
		return is_array( $this->response ) && ! isset( $this->response['XAVResponse']['NoCandidatesIndicator'] );
	}

	/**
	 * Check if the address validation request returned a valid address classification.
	 * Valid classifications are:
	 * - Residential
	 * - Commercial
	 *
	 * @return bool
	 */
	public function found_valid_classification(): bool {
		$classification = $this->get_address_classification();
		if ( empty( $classification ) ) {
			return false;
		}

		return in_array( $classification, array( 'Residential', 'Commercial' ), true );
	}

	/**
	 * Get the address classification from the address validation response if it exists.
	 *
	 * @return string|null
	 */
	public function get_address_classification(): ?string {
		if ( ! is_array( $this->response ) || ! isset( $this->response['XAVResponse']['AddressClassification']['Description'] ) ) {
			return null;
		}

		return $this->response['XAVResponse']['AddressClassification']['Description'];
	}

	/**
	 * Get the cached response key prefix. This will be prepended to the address hash to create the cached response transient key.
	 */
	protected function get_cached_response_key_prefix(): string {
		return 'ups_rest_av_';
	}

	/**
	 * Process the address validation response.
	 *
	 * @param array|WP_Error $response The response from the address validation request.
	 *
	 * @return void
	 */
	protected function process_response( $response ) {
		$this->set_response( $response );
		$this->maybe_set_cached_response( $response );
	}

	/**
	 * Set the address validation response.
	 *
	 * @param array|WP_Error $response The response from the address validation request.
	 *
	 * @return void
	 */
	protected function set_response( $response ) {
		if ( is_wp_error( $response ) ) {
			$this->response = $response;

			return;
		}

		if ( isset( $response['response']['code'], $response['response']['message'] ) && 200 !== $response['response']['code'] ) {
			$code    = $response['response']['code'];
			$message = $response['response']['message'];

			if ( 401 === $code ) {
				$message = __( 'Please add the Address Validation Product to the connected App in your UPS Developer Portal. Customers will bypass address validation until you\'ve completed that step.', 'woocommerce-shipping-ups' );
			}

			$this->response = new WP_Error( 'ups_address_validation_error_' . $code, $message );

			return;
		}

		$this->response = json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Post the address validation request.
	 *
	 * @return array|WP_Error
	 */
	protected function post_address_validation_request() {
		// Build the endpoint.
		$address_validation_endpoint = add_query_arg(
			array( 'maximumcandidatelistsize' => 1 ),
			$this->get_endpoint()
		);

		// Create the request headers.
		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
			'Content-Type'  => 'application/json',
		);

		/**
		 * Filter the address validation request body before sending it to the UPS API.
		 *
		 * @param array  $request The request body.
		 * @param string $class   The class name.
		 *
		 * @since 3.5.0
		 */
		$body = apply_filters( 'woocommerce_shipping_ups_address_validation_request', $this->request, get_class( $this ) );

		return wp_remote_post(
			$address_validation_endpoint,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			)
		);
	}

	/**
	 * Get the UPS address validation endpoint.
	 *
	 * @return string
	 */
	protected function get_endpoint(): string {
		return 'https://onlinetools.ups.com/api/addressvalidation/v2/3';
	}

	/**
	 * Get the first suggested address from the address validation response.
	 *
	 * @return array|false
	 */
	public function get_first_suggested_address() {
		if ( ! $this->found_potential_match() ) {
			return false;
		}

		if ( ! isset( $this->response['XAVResponse']['Candidate'][0]['AddressKeyFormat'] ) ) {
			return false;
		}

		$address = $this->response['XAVResponse']['Candidate'][0]['AddressKeyFormat'];

		$address_line = is_array( $address['AddressLine'] ) ? $address['AddressLine'] : array( $address['AddressLine'] );

		return array(
			'address_1' => $address_line[0],
			'address_2' => $address_line[1] ?? '',
			'city'      => $address['PoliticalDivision2'],
			'state'     => $address['PoliticalDivision1'],
			'postcode'  => $address['PostcodePrimaryLow'],
			'country'   => $address['CountryCode'],
		);
	}
}
