<?php
/**
 * UPS Legacy API Client class.
 *
 * @package WC_Shipping_UPS
 */

namespace WooCommerce\UPS\API\Legacy;

use Exception;
use SimpleXMLElement;
use WC_Product;
use WC_Safe_DOMDocument;
use WooCommerce\UPS\API\Abstract_API_Client;
use WooCommerce\UPS\Notifier;

defined( 'ABSPATH' ) || exit;

require_once WC_SHIPPING_UPS_PLUGIN_DIR . '/includes/api/class-abstract-api-client.php';
require_once WC_SHIPPING_UPS_PLUGIN_DIR . '/includes/api/legacy/class-address-validator.php';

/**
 * Legacy API_Client Class.
 */
class API_Client extends Abstract_API_Client {

	/**
	 * Endpoints for the UPS XML API.
	 *
	 * @var array
	 */
	protected static array $endpoints = array(
		'rate' => 'https://onlinetools.ups.com/ups.app/xml/Rate',
	);

	/**
	 * Make a post request to the rate endpoint.
	 *
	 * @param string $request  The request body.
	 * @param string $endpoint The request endpoint.
	 *
	 * @inheritDoc
	 */
	protected function post_rate_request( $request, string $endpoint ) {
		$request = str_replace( array( "\n", "\r" ), '', $request );

		/**
		 * Filter the request body before sending it to the UPS API.
		 *
		 * @param string $request          The request body.
		 * @param array  $package_requests The package requests.
		 * @param array  $package          The package.
		 * @param string $class            The class name.
		 * @param string $endpoint         The request endpoint.
		 *
		 * @since 3.4.3
		 */
		$body = apply_filters( 'woocommerce_shipping_ups_request', $request, $this->package_requests, $this->package, get_class( $this ), $endpoint );

		return wp_remote_post(
			$endpoint,
			array(
				'timeout'   => 70,
				'sslverify' => 0,
				'body'      => $body,
			)
		);
	}


	/**
	 * Build a package element for a Box Packer packed box which will be added to the rate request.
	 *
	 * @param object $packed_box         The packed box.
	 * @param int    $packed_boxes_count The number of packed boxes.
	 *
	 * @return string
	 */
	public function build_packed_box_package_for_rate_request( object $packed_box, int $packed_boxes_count ): string {
		// The dimensions are currently in the UPS instance's dimension unit.
		$box_dimensions = array(
			'length' => $packed_box->length,
			'width'  => $packed_box->width,
			'height' => $packed_box->height,
		);

		$box_weight = $packed_box->weight;

		$request = '<Package>' . "\n";

		$use_simple_rate = false;

		// UPS Simple Rate is only available for domestic US shipments and for a single package request.
		if ( $this->is_package_eligible_for_simple_rate( $packed_boxes_count ) ) {

			$code = $this->shipping_method->maybe_get_simple_rate_code(
				round( $this->shipping_method->get_converted_dimension( $box_dimensions['length'], $this->shipping_method->get_dimension_unit(), 'in' ) ),
				round( $this->shipping_method->get_converted_dimension( $box_dimensions['width'], $this->shipping_method->get_dimension_unit(), 'in' ) ),
				round( $this->shipping_method->get_converted_dimension( $box_dimensions['height'], $this->shipping_method->get_dimension_unit(), 'in' ) ),
				$this->shipping_method->get_converted_weight( $box_weight, $this->shipping_method->get_weight_unit(), 'lbs' )
			);

			if ( $code ) {
				$request .= '	<SimpleRate>' . "\n";
				$request .= '		<Code>' . $code . '</Code>' . "\n";
				$request .= '		<Description>UPS Simple Rate</Description>' . "\n";
				$request .= '	</SimpleRate>' . "\n";

				$use_simple_rate = true;
			}
		}

		$request .= '	<PackagingType>' . "\n";

		/**
		 * Change package code to 01 UPS Letter to get lower rates if the items fit into the
		 * UPS Letter envelope and if Simple Rate isn't being used
		 */
		if ( 'UPS Letter' === $packed_box->id && ! $use_simple_rate ) {
			$request .= '		<Code>01</Code>' . "\n";
			$request .= '		<Description>UPS Letter</Description>' . "\n";
		} else {
			$request .= '		<Code>02</Code>' . "\n";
			$request .= '		<Description>Package/customer supplied</Description>' . "\n";
		}

		$request .= '	</PackagingType>' . "\n";

		$request .= '	<Description>Rate</Description>' . "\n";

		$request .= '	<Dimensions>' . "\n";
		$request .= '		<UnitOfMeasurement>' . "\n";
		$request .= '			<Code>' . $this->shipping_method->get_dimension_unit() . '</Code>' . "\n";
		$request .= '		</UnitOfMeasurement>' . "\n";
		$request .= '		<Length>' . round( $box_dimensions['length'] ) . '</Length>' . "\n";
		$request .= '		<Width>' . round( $box_dimensions['width'] ) . '</Width>' . "\n";
		$request .= '		<Height>' . round( $box_dimensions['height'] ) . '</Height>' . "\n";
		$request .= '	</Dimensions>' . "\n";

		$request = $this->add_package_weight_element( $request, $box_weight );

		if ( $this->shipping_method->has_package_service_options( $this->package['destination']['country'] ) ) {
			$request .= '	<PackageServiceOptions>' . "\n";

			// InsuredValue.
			if ( $this->shipping_method->is_insured_value_enabled() ) {

				$request .= '		<InsuredValue>' . "\n";
				$request .= '			<CurrencyCode>' . get_woocommerce_currency() . '</CurrencyCode>' . "\n";
				$request .= '			<MonetaryValue>' . $packed_box->value . '</MonetaryValue>' . "\n";
				$request .= '		</InsuredValue>' . "\n";
			}

			// Delivery confirmation.
			$request = $this->maybe_add_delivery_confirmation_element( $this->package['destination']['country'], $request );
		}
		$request .= '</Package>' . "\n";

		return $request;
	}


	/**
	 * Build a package element for an individually packed product which will be added to the rate request.
	 *
	 * @param array $cart_item The cart item.
	 *
	 * @return string
	 */
	public function build_individually_packed_package_for_rate_request( array $cart_item ): string {

		/**
		 * The cart item data is a WC_Product instance.
		 *
		 * @var WC_Product $product Product instance.
		 */
		$product = $cart_item['data'];

		$product_has_dimensions = $product->get_length() && $product->get_width() && $product->get_height();

		$request = '<Package>' . "\n";

		// UPS Simple Rate is only available for domestic US shipments.
		if ( $this->is_package_eligible_for_simple_rate( $cart_item['quantity'] ) && $product_has_dimensions && ! empty( $product->get_weight() ) ) {

			$code = $this->shipping_method->maybe_get_simple_rate_code(
				round( wc_get_dimension( floatval( $product->get_length() ), 'in' ) ),
				round( wc_get_dimension( floatval( $product->get_width() ), 'in' ) ),
				round( wc_get_dimension( floatval( $product->get_height() ), 'in' ) ),
				wc_get_weight( $product->get_weight(), 'lbs' )
			);

			if ( $code ) {
				$request .= '	<SimpleRate>' . "\n";
				$request .= '		<Code>' . $code . '</Code>' . "\n";
				$request .= '		<Description>UPS Simple Rate</Description>' . "\n";
				$request .= '	</SimpleRate>' . "\n";
			}
		}

		$request .= '	<PackagingType>' . "\n";
		// Always use code 02 with per-item shipping cause UPS API can't handle two 01 UPS Letter packages sent in the same request.
		$request .= '		<Code>02</Code>' . "\n";
		$request .= '		<Description>Package/customer supplied</Description>' . "\n";
		$request .= '	</PackagingType>' . "\n";
		$request .= '	<Description>Rate</Description>' . "\n";

		if ( $product_has_dimensions ) {
			$product_dimensions = $this->get_processed_product_dimensions( $product );

			$request .= '	<Dimensions>' . "\n";
			$request .= '		<UnitOfMeasurement>' . "\n";
			$request .= '			<Code>' . $this->shipping_method->get_dimension_unit() . '</Code>' . "\n";
			$request .= '		</UnitOfMeasurement>' . "\n";
			$request .= '		<Length>' . round( $product_dimensions[2] ) . '</Length>' . "\n";
			$request .= '		<Width>' . round( $product_dimensions[1] ) . '</Width>' . "\n";
			$request .= '		<Height>' . round( $product_dimensions[0] ) . '</Height>' . "\n";
			$request .= '	</Dimensions>' . "\n";
		}

		$request = $this->add_package_weight_element( $request, wc_get_weight( $product->get_weight(), $this->shipping_method->get_weight_unit() ) );

		if ( $this->shipping_method->has_package_service_options( $this->package['destination']['country'] ) ) {
			$request .= '	<PackageServiceOptions>' . "\n";

			// InsuredValue.
			if ( $this->shipping_method->is_insured_value_enabled() ) {

				$request .= '		<InsuredValue>' . "\n";
				$request .= '			<CurrencyCode>' . get_woocommerce_currency() . '</CurrencyCode>' . "\n";
				$request .= '			<MonetaryValue>' . $product->get_price() . '</MonetaryValue>' . "\n";
				$request .= '		</InsuredValue>' . "\n";
			}

			// Delivery confirmation.
			$request = $this->maybe_add_delivery_confirmation_element( $this->package['destination']['country'], $request );
		}
		$request .= '</Package>' . "\n";

		return $request;
	}


	/**
	 * Get the rate for a package.
	 *
	 * @return array
	 */
	public function get_rates(): array {
		$notice_group = self::$notice_group;

		Notifier::clear_notices( $notice_group );

		$rate_requests = $this->get_rate_requests_array();
		if ( empty( $rate_requests ) ) {
			return array();
		}

		// Add package request dimensions and weight to the rate metadata if available.
		$meta_data = $this->maybe_get_packed_box_details();

		$rates         = array();
		$ups_responses = array();

		// Get live or cached result for each rate.
		foreach ( $rate_requests as $code => $request ) {
			$transient              = 'ups_quote_' . md5( $request );
			$cached_response        = get_transient( $transient );
			$ups_responses[ $code ] = false;

			if ( false === $cached_response ) {
				$response = $this->post_rate_request( $request, self::$endpoints['rate'] );

				if ( is_wp_error( $response ) ) {
					$this->shipping_method->debug( __( 'Cannot retrieve rate: ', 'woocommerce-shipping-ups' ) . $response->get_error_message(), 'error', array(), $notice_group );
				} else {
					$ups_responses[ $code ] = $response['body'];
					set_transient( $transient, $response['body'], DAY_IN_SECONDS * 30 );
				}
			} else {
				$ups_responses[ $code ] = $cached_response;
			}

			$request = preg_replace( '/<AccessRequest(.*)<\/AccessRequest>/s', '', $request );

			$request_output = trim( str_replace( '<?xml version="1.0"?>', '', $request ) );
			$request_output = simplexml_load_string( $request_output );
			$request_output = json_decode( wp_json_encode( $request_output ), true );

			$response_output = trim( str_replace( '<?xml version="1.0"?>', '', $ups_responses[ $code ] ) );
			$response_output = simplexml_load_string( $response_output );
			$response_output = json_decode( wp_json_encode( $response_output ), true );

			$this->shipping_method->debug( 'UPS: Rate Request | Service Code: ' . $code, 'notice', $request_output, $notice_group );
			$this->shipping_method->debug( 'UPS: Rate Response | Service Code: ' . $code, 'notice', $response_output, $notice_group );
		}

		// Parse the results.
		foreach ( $ups_responses as $code => $response ) {
			if ( ! $response ) {
				continue;
			}

			$xml = $this->get_parsed_xml( $response );

			if ( ! $xml ) {
				$this->shipping_method->debug( __( 'Failed loading XML', 'woocommerce-shipping-ups' ), 'error', array(), $notice_group );
			}

			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- UPS uses camelCase.
			if ( isset( $xml->Response->ResponseStatusCode ) && 1 === (int) $xml->Response->ResponseStatusCode ) {

				$shipment = $xml->RatedShipment;
				$rate     = $this->maybe_get_rate( $xml, $code, $shipment );

				if ( empty( $rate ) ) {
					continue;
				}

				/**
				 * Allow 3rd parties to process the rates returned by UPS.
				 *
				 * @param array                       $rate       The rate array.
				 * @param string                      $currency   The currency code.
				 * @param false|null|SimpleXMLElement $xml        The XML response.
				 * @param Abstract_API_Client         $api_client The API client instance.
				 *
				 * @since 3.4.3
				 */
				$rates[ $rate['id'] ] = apply_filters(
					'woocommerce_shipping_ups_rate',
					array(
						'id'        => $rate['id'],
						'label'     => $rate['name'],
						'cost'      => $rate['cost'],
						'sort'      => $rate['sort'],
						'meta_data' => $meta_data,
					),
					$rate['currency'],
					$xml,
					$this
				);
			} else {
				/**
				 * Either there was an error on this rate, or the rate is
				 * not valid (i.e. it is a domestic rate, but shipping
				 * international).
				 */
				$this->shipping_method->debug(
					sprintf(
					/* translators: 1: Service code, 2: Error description, 3: Error code */
						__( '[UPS] No rate returned for service code %1$s, %2$s (UPS code: %3$s)', 'woocommerce-shipping-ups' ),
						$code,
						$xml->Response->Error->ErrorDescription,
						$xml->Response->Error->ErrorCode
					),
					'notice',
					array(),
					$notice_group
				);
			}
		}

		return $rates;
	}


	/**
	 * Extract the packed box dimensions and weights if available and return in an array.
	 *
	 * @return array|false
	 */
	protected function maybe_get_packed_box_details() {
		$meta_data = array();
		foreach ( $this->package_requests as $index => $request ) {
			try {
				++$index;
				$request_object = new SimpleXMLElement( $request );

				$meta_data = $this->maybe_get_packed_box_details_meta( $meta_data, $request_object, $index );
			} catch ( Exception $e ) {
				$this->shipping_method->debug( 'Failed generating SimpleXMLElement from package request XML string.', 'error' );
			}
		}

		return ! empty( $meta_data ) ? $meta_data : false;
	}

	/**
	 * Add the package weight element to the request.
	 *
	 * @param string $request The request.
	 * @param mixed  $weight  The weight.
	 *
	 * @return string
	 */
	private function add_package_weight_element( string $request, $weight ): string {
		$request .= '	<PackageWeight>' . "\n";
		$request .= '		<UnitOfMeasurement>' . "\n";
		$request .= '			<Code>' . $this->shipping_method->get_weight_unit() . '</Code>' . "\n";
		$request .= '		</UnitOfMeasurement>' . "\n";
		$request .= '		<Weight>' . $weight . '</Weight>' . "\n";
		$request .= '	</PackageWeight>' . "\n";

		return $request;
	}

	/**
	 * Maybe add the delivery confirmation element to the request.
	 *
	 * @param string $country The country.
	 * @param string $request The request.
	 *
	 * @return string
	 */
	private function maybe_add_delivery_confirmation_element( string $country, string $request ): string {
		if ( $this->shipping_method->needs_delivery_confirmation() && 'package' === $this->shipping_method->delivery_confirmation_level( $country ) ) {
			$request .= '		<DeliveryConfirmation>' . "\n";
			$request .= '			<DCISType>' . ( 'regular' === $this->shipping_method->get_signature() ? '2' : '3' ) . '</DCISType>' . "\n";
			$request .= '		</DeliveryConfirmation>' . "\n";
		}

		$request .= '	</PackageServiceOptions>' . "\n";

		return $request;
	}

	/**
	 * Retrieves an array of rate requests.
	 *
	 * @return array
	 */
	private function get_rate_requests_array(): array {
		$rate_requests = array();

		foreach ( $this->shipping_method->get_custom_services() as $code => $params ) {
			if ( ! empty( $params['enabled'] ) ) {
				$rate_requests[ $code ] = $this->build_rate_request( $this->package_requests, $this->package, $code );
			}
		}

		return $rate_requests;
	}

	/**
	 * Get access request XML element.
	 *
	 * @return SimpleXMLElement
	 */
	private function get_access_request_xml_element(): SimpleXMLElement {
		// Ampersand will break XML doc, so replace with encoded version.
		$password = str_replace( '&', '&amp;', $this->shipping_method->get_password() );

		$access_request_xml = new SimpleXMLElement( '<AccessRequest></AccessRequest>' );
		$access_request_xml->addChild( 'AccessLicenseNumber', $this->shipping_method->get_access_key() );
		$access_request_xml->addChild( 'UserId', $this->shipping_method->get_user_id() );
		$access_request_xml->addChild( 'Password', $password );

		return $access_request_xml;
	}

	/**
	 * Build the rate request.
	 *
	 * @param array  $package_requests The package requests.
	 * @param array  $package          The package.
	 * @param string $code             The code.
	 *
	 * @return string
	 */
	private function build_rate_request( array $package_requests, array $package, string $code ): string {
		// Security Header.
		$request = $this->get_access_request_xml_element()->asXML();

		$request .= '<?xml version="1.0" ?>' . "\n";
		$request .= '<RatingServiceSelectionRequest>' . "\n";

		// Customer classification code.
		if ( ! empty( $this->shipping_method->get_customer_classification_code() ) ) {
			$request .= "	<CustomerClassification><Code>{$this->shipping_method->get_customer_classification_code()}</Code><Description>{$this->shipping_method->get_customer_classifications()[$this->shipping_method->get_customer_classification_code()]}</Description></CustomerClassification>\n";
		}

		$request .= '	<Request>' . "\n";
		$request .= '	<TransactionReference>' . "\n";
		$request .= '		<CustomerContext>Rating and Service</CustomerContext>' . "\n";
		$request .= '		<XpciVersion>1.0</XpciVersion>' . "\n";
		$request .= '	</TransactionReference>' . "\n";
		$request .= '	<RequestAction>Rate</RequestAction>' . "\n";
		$request .= '	<RequestOption>Rate</RequestOption>' . "\n";
		$request .= '	</Request>' . "\n";
		// Shipment information.
		$request .= '	<Shipment>' . "\n";
		$request .= '		<Description>WooCommerce Rate Request</Description>' . "\n";
		$request .= '		<Shipper>' . "\n";
		$request .= '			<ShipperNumber>' . $this->shipping_method->get_shipper_number() . '</ShipperNumber>' . "\n";
		$request .= '			<Address>' . "\n";
		if ( $this->shipping_method->get_origin_addressline() ) {
			$request .= '				<AddressLine>' . $this->shipping_method->get_origin_addressline() . '</AddressLine>' . "\n";
		}
		$request .= '				<City>' . $this->shipping_method->get_origin_city() . '</City>' . "\n";
		$request .= '				<PostalCode>' . $this->shipping_method->get_origin_postcode() . '</PostalCode>' . "\n";
		$request .= '				<CountryCode>' . $this->shipping_method->get_origin_country() . '</CountryCode>' . "\n";
		$request .= '			</Address>' . "\n";
		$request .= '		</Shipper>' . "\n";
		$request .= '		<ShipTo>' . "\n";
		$request .= '			<Address>' . "\n";
		$request .= '				<AddressLine1>' . $package['destination']['address_1'] . '</AddressLine1>' . "\n";
		$request .= '				<City>' . $package['destination']['city'] . '</City>' . "\n";
		$request .= '				<StateProvinceCode>' . $package['destination']['state'] . '</StateProvinceCode>' . "\n";
		$request .= '				<PostalCode>' . $package['destination']['postcode'] . '</PostalCode>' . "\n";
		// if Country / State is 'Puerto Rico', set it to be the country,
		// else use set country.
		if ( ( 'PR' === $package['destination']['state'] ) && ( 'US' === $package['destination']['country'] ) ) {
			$request .= '				<CountryCode>PR</CountryCode>' . "\n";
		} else {
			$request .= '				<CountryCode>' . $package['destination']['country'] . '</CountryCode>' . "\n";
		}
		if ( $this->shipping_method->is_residential() ) {
			$request .= '				<ResidentialAddressIndicator></ResidentialAddressIndicator>' . "\n";
		}
		$request .= '			</Address>' . "\n";
		$request .= '		</ShipTo>' . "\n";
		$request .= '		<ShipFrom>' . "\n";
		$request .= '			<Address>' . "\n";
		if ( $this->shipping_method->get_origin_addressline() ) {
			$request .= '				<AddressLine>' . $this->shipping_method->get_origin_addressline() . '</AddressLine>' . "\n";
		}
		$request .= '				<City>' . $this->shipping_method->get_origin_city() . '</City>' . "\n";
		$request .= '				<PostalCode>' . $this->shipping_method->get_origin_postcode() . '</PostalCode>' . "\n";
		$request .= '				<CountryCode>' . $this->shipping_method->get_origin_country() . '</CountryCode>' . "\n";
		if ( $this->shipping_method->is_negotiated_rates_enabled() && $this->shipping_method->get_origin_state() ) {
			$request .= '				<StateProvinceCode>' . $this->shipping_method->get_origin_state() . '</StateProvinceCode>' . "\n";
		}
		$request .= '			</Address>' . "\n";
		$request .= '		</ShipFrom>' . "\n";
		$request .= '		<Service>' . "\n";
		$request .= '			<Code>' . $code . '</Code>' . "\n";
		$request .= '		</Service>' . "\n";
		// Packages.
		foreach ( $package_requests as $package_request ) {
			$request .= $package_request;
		}
		// Negotiated rates flag.
		if ( $this->shipping_method->is_negotiated_rates_enabled() ) {
			$request .= '		<RateInformation>' . "\n";
			$request .= '			<NegotiatedRatesIndicator />' . "\n";
			$request .= '		</RateInformation>' . "\n";
		}

		// Delivery confirmation.
		if ( $this->shipping_method->needs_delivery_confirmation() && 'shipment' === $this->shipping_method->delivery_confirmation_level( $package['destination']['country'] ) ) {
			$request .= '		<ShipmentServiceOptions>' . "\n";
			$request .= '			<DeliveryConfirmation>' . "\n";
			$request .= '				<DCISType>' . ( 'regular' === $this->shipping_method->get_signature() ? '1' : '2' ) . '</DCISType>' . "\n";
			$request .= '			</DeliveryConfirmation>' . "\n";
			$request .= '		</ShipmentServiceOptions>' . "\n";
		}

		$request .= '	</Shipment>' . "\n";
		$request .= '</RatingServiceSelectionRequest>' . "\n";

		return $request;
	}

	/**
	 * Get Parsed XML response.
	 *
	 * @param string $xml XML.
	 *
	 * @return false|SimpleXMLElement|null Return false if failed to parse.
	 */
	private function get_parsed_xml( string $xml ) {
		if ( ! class_exists( 'WC_Safe_DOMDocument' ) ) {
			require_once WC_SHIPPING_UPS_PLUGIN_DIR . '/includes/api/legacy/class-wc-safe-domdocument.php';
		}

		libxml_use_internal_errors( true );

		$dom     = new WC_Safe_DOMDocument();
		$success = $dom->loadXML( $xml );

		if ( ! $success ) {
			if ( $this->shipping_method->is_debug_mode_enabled() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error --- Reason: Debugging.
				trigger_error( 'wpcom_safe_simplexml_load_string(): Error loading XML string', E_USER_WARNING );
			}

			return false;
		}

		if ( isset( $dom->doctype ) ) {
			if ( $this->shipping_method->is_debug_mode_enabled() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error --- Reason: Debugging.
				trigger_error( 'wpcom_safe_simplexml_import_dom(): Unsafe DOCTYPE Detected', E_USER_WARNING );
			}

			return false;
		}

		return simplexml_import_dom( $dom );
	}


	/**
	 * Handle destination address validation.
	 *
	 * @param array $destination_address The destination address.
	 *
	 * @return void
	 */
	public function validate_destination_address( array $destination_address ) {

		$this->shipping_method->set_is_valid_destination_address( false );

		// Validate the address.
		$this->set_address_validator( new Address_Validator( $destination_address, $this->get_access_request_xml_element() ) );
		$this->get_address_validator()->validate();

		$notice_group = $this->get_address_validator()::$notice_group;

		Notifier::clear_notices( $notice_group );

		// Print the request.
		$validation_request = preg_replace( '/<AccessRequest(.*)<\/AccessRequest>/s', '', $this->get_address_validator()->get_request() );
		$validation_request = trim( str_replace( '<?xml version="1.0"?>', '', $validation_request ) );
		$validation_request = simplexml_load_string( $validation_request );
		$validation_request = json_decode( wp_json_encode( $validation_request ), true );
		$this->shipping_method->debug( __( 'UPS: Address Validation Request', 'woocommerce-shipping-ups' ), 'notice', (array) $validation_request );

		// Print the response.
		$validation_response = $this->get_address_validator()->get_response();
		if ( is_wp_error( $validation_response ) ) {
			$this->shipping_method->debug( __( 'UPS: Address Validation Error', 'woocommerce-shipping-ups' ), 'error', array( $validation_response->get_error_message() ), $notice_group );
			$this->shipping_method->set_is_valid_destination_address( false );

			return;
		}
		$validation_response = simplexml_load_string( $validation_response );
		$validation_response = json_decode( wp_json_encode( $validation_response ), true );

		$this->shipping_method->debug( __( 'UPS: Address Validation Response', 'woocommerce-shipping-ups' ), 'notice', array( $validation_response ), $notice_group );

		// Set whether the destination address is valid.
		$this->set_is_valid_destination_address( $this->get_address_validator() );
	}
}
