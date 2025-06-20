<?php
/**
 * Shipping method class.
 *
 * @package WC_Shipping_UPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'api/rest/class-oauth.php';
require_once 'api/legacy/class-api-client.php';
require_once 'api/rest/class-api-client.php';
require_once 'class-notifier.php';
require_once 'class-logger.php';

use WooCommerce\UPS\API\Abstract_API_Client;
use WooCommerce\UPS\API\Legacy\API_Client as Legacy_API_Client;
use WooCommerce\UPS\API\REST\OAuth as REST_API_OAuth;
use WooCommerce\UPS\API\REST\API_Client as REST_API_Client;
use WooCommerce\UPS\Logger;
use WooCommerce\UPS\Notifier;
use WooCommerce\BoxPacker\Abstract_Packer;
use WooCommerce\BoxPacker\WC_Boxpack;

/**
 * WC_Shipping_UPS class.
 *
 * @version 3.2.6
 * @since   3.2.0
 * @see     WC_Shipping_Method
 */
class WC_Shipping_UPS extends WC_Shipping_Method {

	/**
	 * UPS API user ID.
	 *
	 * @var mixed
	 */
	private $user_id;
	/**
	 * UPS API password.
	 *
	 * @var mixed
	 */
	private $password;
	/**
	 * UPS API access key.
	 *
	 * @var mixed
	 */
	private $access_key;
	/**
	 * UPS API shipper number.
	 *
	 * @var mixed
	 */
	private $shipper_number;
	/**
	 * UPS API classification code.
	 *
	 * @var mixed
	 */
	private $classification_code;
	/**
	 * Is UPS Simple Rate enabled?
	 *
	 * @var bool
	 */
	private bool $simple_rate;
	/**
	 * Is negotiated rates enabled?
	 *
	 * @var bool
	 */
	private bool $negotiated;
	/**
	 * The UPS instance dimension unit.
	 *
	 * @var string
	 */
	private string $dim_unit;
	/**
	 * The UPS instance weight unit.
	 *
	 * @var string
	 */
	private string $weight_unit;
	/**
	 * Offer all rates or cheapest.
	 *
	 * @var mixed
	 */
	private $offer_rates;
	/**
	 * Flag the destination address as residential.
	 *
	 * @var bool
	 */
	private bool $residential;
	/**
	 * Is the destination address valid?
	 *
	 * @var bool
	 */
	private bool $is_valid_destination_address = true;
	/**
	 * Is the destination address validation enabled?
	 *
	 * @var bool
	 */
	private bool $destination_address_validation;
	/**
	 * The fallback cost to use if no rates are returned.
	 *
	 * @var mixed
	 */
	private $fallback;
	/**
	 * Whether to pack items into boxes or not.
	 *
	 * @var mixed
	 */
	private $packing_method;
	/**
	 * Sets the box packer library to use.
	 *
	 * @var string
	 */
	public $box_packer_library;
	/**
	 * The standard UPS packaging/box sizes selected by the user
	 * to be used for packing items into for this instance.
	 *
	 * @var mixed
	 */
	private $ups_packaging;
	/**
	 * The UPS services.
	 *
	 * @var mixed
	 */
	private $custom_services;
	/**
	 * The custom boxes defined by the user.
	 *
	 * @var mixed
	 */
	private $boxes;
	/**
	 * A flag to determine if the user wants to insure the package.
	 *
	 * @var bool
	 */
	private bool $insuredvalue;
	/**
	 * Whether to require a signature or not and if so, what type.
	 *
	 * @var mixed
	 */
	private $signature;
	/**
	 * Metric or imperial.
	 *
	 * @var string
	 */
	private string $units;
	/**
	 * The origin address line.
	 *
	 * @var string
	 */
	private string $origin_addressline;
	/**
	 * The origin city.
	 *
	 * @var string
	 */
	private string $origin_city;
	/**
	 * The origin state.
	 *
	 * @var string
	 */
	private string $origin_state;
	/**
	 * The origin country.
	 *
	 * @var string
	 */
	private string $origin_country;
	/**
	 * The origin postcode.
	 *
	 * @var string
	 */
	private string $origin_postcode;
	/**
	 * UPS API type.
	 *
	 * This determines whether to use XML API or REST API.
	 *
	 * @var string
	 */
	private string $api_type;
	/**
	 * UPS REST API client ID.
	 *
	 * @var string
	 */
	private string $client_id;
	/**
	 * UPS REST API client secret.
	 *
	 * @var string
	 */
	private string $client_secret;
	/**
	 * UPS REST API OAuth instance.
	 *
	 * @var REST_API_OAuth
	 */
	private REST_API_OAuth $ups_oauth;
	/**
	 * UPS API Client instance.
	 *
	 * @var Abstract_API_Client
	 */
	private Abstract_API_Client $ups_api;
	/**
	 * Debug mode.
	 *
	 * @var bool
	 */
	private bool $debug;
	/**
	 * Ordered services.
	 *
	 * @var array
	 */
	private array $ordered_services;
	/**
	 * Notifier instance.
	 *
	 * @var Notifier
	 */
	public Notifier $notifier;
	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	public Logger $logger;
	/**
	 * Servide codes mapped to the service names.
	 *
	 * @var array
	 */
	private array $services = array(
		// Domestic.
		'01' => 'Next Day Air',
		'03' => 'Ground',
		'02' => '2nd Day Air',
		'12' => '3 Day Select',
		'13' => 'Next Day Air Saver',
		'14' => 'Next Day Air Early AM',
		'59' => '2nd Day Air AM',

		// SurePost.
		'92' => 'UPS SurePost Less than 1LB',
		'93' => 'UPS SurePost 1LB or greater',
		'94' => 'UPS SurePost BPM',
		'95' => 'UPS SurePost Media Mail',

		// International.
		'11' => 'Standard',
		'07' => 'Worldwide Express',
		'54' => 'Worldwide Express Plus',
		'08' => 'Worldwide Expedited Standard',
		'65' => 'Worldwide Saver',

	);
	/**
	 * Country considered as EU.
	 *
	 * @var array
	 */
	private array $eu_array = array( 'BE', 'BG', 'CZ', 'DK', 'DE', 'EE', 'IE', 'GR', 'ES', 'FR', 'HR', 'IT', 'CY', 'LV', 'LT', 'LU', 'HU', 'MT', 'NL', 'AT', 'PT', 'RO', 'SI', 'SK', 'FI', 'GB' );
	/**
	 * Shipments Originating in the European Union.
	 *
	 * @var array
	 */
	private array $euservices = array(
		'07' => 'UPS Express',
		'08' => 'UPS ExpeditedSM',
		'11' => 'UPS Standard',
		'54' => 'UPS Express PlusSM',
		'65' => 'UPS Saver',
	);
	/**
	 * Poland services.
	 *
	 * @var array
	 */
	private array $polandservices = array(
		'07' => 'UPS Express',
		'08' => 'UPS ExpeditedSM',
		'11' => 'UPS Standard',
		'54' => 'UPS Express PlusSM',
		'65' => 'UPS Saver',
		'82' => 'UPS Today Standard',
		'83' => 'UPS Today Dedicated Courier',
		'84' => 'UPS Today Intercity',
		'85' => 'UPS Today Express',
		'86' => 'UPS Today Express Saver',
	);
	/**
	 * Services available with UPS Simple Rate
	 *
	 * @see https://www.ups.com/assets/resources/media/en_US/daily_rates.pdf#page=76
	 *
	 * @var array
	 */
	private array $simple_rate_services = array(
		'02',
		'03',
		'12',
		'13',
	);
	/**
	 * SurePost service codes.
	 *
	 * @var array
	 */
	private array $surepost_service_codes = array(
		'92',
		'93',
		'94',
		'95',
	);
	/**
	 * Packaging not offered at this time: 00 = UNKNOWN, 30 = Pallet, 04 = Pak
	 * Code 21 = Express box is valid code, but doesn't have dimensions.
	 *
	 * @see http://www.ups.com/content/us/en/resources/ship/packaging/supplies/envelopes.html.
	 * @see http://www.ups.com/content/us/en/resources/ship/packaging/supplies/paks.html.
	 * @see http://www.ups.com/content/us/en/resources/ship/packaging/supplies/boxes.html.
	 * @see https://www.ups.com/content/us/en/shipping/create/package_type_help.html.
	 *
	 * @var array
	 */
	private array $packaging = array(
		'01' => array(
			'name'   => 'UPS Letter',
			'length' => '12.5',
			'width'  => '9.5',
			'height' => '0.25',
			'weight' => '0.5',
		),
		'03' => array(
			'name'   => 'Tube',
			'length' => '38',
			'width'  => '6',
			'height' => '6',
			'weight' => '100', // No limit, but use 100.
		),
		'24' => array(
			'name'   => '25KG Box',
			'length' => '19.375',
			'width'  => '17.375',
			'height' => '14',
			'weight' => '55.1156',
		),
		'25' => array(
			'name'   => '10KG Box',
			'length' => '16.5',
			'width'  => '13.25',
			'height' => '10.75',
			'weight' => '22.0462',
		),
		'2a' => array(
			'name'   => 'Small Express Box',
			'length' => '13',
			'width'  => '11',
			'height' => '2',
			'weight' => '100', // No limit, but use 100.
		),
		'2b' => array(
			'name'   => 'Medium Express Box',
			'length' => '15',
			'width'  => '11',
			'height' => '3',
			'weight' => '100', // No limit, but use 100.
		),
		'2c' => array(
			'name'   => 'Large Express Box',
			'length' => '18',
			'width'  => '13',
			'height' => '3',
			'weight' => '30',
		),
	);
	/**
	 * Simple Rate Package requirements
	 *
	 * @see https://www.ups.com/assets/resources/media/en_US/daily_rates.pdf#page=76
	 *
	 * @var array
	 */
	private array $simple_rate_packaging = array(
		'XS' => array(
			'name'             => 'Extra Small Simple Rate Box',
			'cubic_inches_min' => '1',
			'cubic_inches_max' => '100',
			'weight'           => '50',
		),
		'S'  => array(
			'name'             => 'Small Simple Rate Box',
			'cubic_inches_min' => '101',
			'cubic_inches_max' => '250',
			'weight'           => '50',
		),
		'M'  => array(
			'name'             => 'Medium Simple Rate Box',
			'cubic_inches_min' => '251',
			'cubic_inches_max' => '650',
			'weight'           => '50',
		),
		'L'  => array(
			'name'             => 'Large Simple Rate Box',
			'cubic_inches_min' => '651',
			'cubic_inches_max' => '1050',
			'weight'           => '50',
		),
		'XL' => array(
			'name'             => 'Extra Large Simple Rate Box',
			'cubic_inches_min' => '1051',
			'cubic_inches_max' => '1728',
			'weight'           => '50',
		),
	);
	/**
	 * Packaging for select options.
	 *
	 * @var array
	 */
	private array $packaging_select = array(
		'01' => 'UPS Letter',
		'03' => 'Tube',
		'24' => '25KG Box',
		'25' => '10KG Box',
		'2a' => 'Small Express Box',
		'2b' => 'Medium Express Box',
		'2c' => 'Large Express Box',
	);

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'ups';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'UPS', 'woocommerce-shipping-ups' );
		$this->method_description = __( 'The UPS extension obtains rates dynamically from the UPS API during cart/checkout.', 'woocommerce-shipping-ups' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'settings',
		);
		$this->init();
	}

	/**
	 * Checks whether shipping method is available or not.
	 *
	 * @param array $package Package to ship.
	 *
	 * @return bool True if shipping method is available.
	 */
	public function is_available( $package ): bool {
		$is_available = true;

		// Postcode with wildcard asterisks (*) should not be validated to enable Apple Pay and Google Pay compatibility.
		if ( empty( $package['destination']['country'] ) || ( ! WC_Validation::is_postcode( $package['destination']['postcode'], $package['destination']['country'] ) && false === strpos( $package['destination']['postcode'], '*' ) ) ) {
			$is_available = false;
		}

		/**
		 * Filter the availability of the UPS shipping method.
		 *
		 * @param bool  $is_available True if the shipping method is available, false otherwise.
		 * @param array $package      Package to ship.
		 *
		 * @since 3.2.0
		 */
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package );
	}

	/**
	 * Output a debug message.
	 *
	 * @param string $message Debug message.
	 * @param string $type    Optional. Message type - either error, success or notice. Default is notice.
	 * @param array  $data    Optional. Additional data to pass.
	 * @param string $group   Optional. Group to categorize notices.
	 */
	public function debug( string $message, string $type = 'notice', array $data = array(), string $group = '' ) {
		$this->notifier->debug( $message, $type, $data, $group );
		$this->logger->debug( $message, $data );
	}

	/**
	 * Output a WC notice for the customer.
	 *
	 * @param string $message Customer message.
	 * @param string $type    Optional. Message type - either error, success or notice.
	 * @param array  $data    Optional. Additional data to pass.
	 * @param string $group   Optional. Group to categorize notices.
	 */
	public function add_customer_notice( string $message, string $type = 'notice', array $data = array(), string $group = '' ) {
		$this->notifier->maybe_add_notice( $message, $type, $data, $group );
	}

	/**
	 * Initialize settings.
	 *
	 * @return bool
	 * @since   3.2.0
	 *
	 * @version 3.2.0
	 */
	private function set_settings(): bool {
		// Define user set variables.
		$this->title = $this->get_option( 'title', $this->method_title );

		// API Settings.
		$this->api_type            = $this->get_option( 'api_type', $this->get_default_api_type() );
		$this->client_id           = $this->get_option( 'client_id', '' );
		$this->client_secret       = $this->get_option( 'client_secret', '' );
		$this->user_id             = $this->get_option( 'user_id' );
		$this->password            = $this->get_option( 'password' );
		$this->access_key          = $this->get_option( 'access_key' );
		$this->shipper_number      = $this->get_option( 'shipper_number' );
		$this->classification_code = $this->get_option( 'customer_classification_code' );
		$this->simple_rate         = $this->get_option( 'simple_rate' ) === 'yes';
		$this->negotiated          = $this->get_option( 'negotiated' ) === 'yes';
		$this->origin_addressline  = $this->get_option( 'origin_addressline' );
		$this->origin_city         = $this->get_option( 'origin_city' );
		$this->origin_postcode     = $this->get_option( 'origin_postcode' );
		$origin_country_state      = $this->get_option( 'origin_country_state' );
		$this->debug               = $this->get_option( 'debug' ) === 'yes';

		// Destination.
		$this->residential                    = $this->get_option( 'residential' ) === 'yes';
		$this->destination_address_validation = $this->get_option( 'destination_address_validation' ) === 'yes';

		// Services and Packaging.
		$this->offer_rates        = $this->get_option( 'offer_rates', 'all' );
		$this->fallback           = $this->get_option( 'fallback' );
		$this->packing_method     = $this->get_option( 'packing_method', 'per_item' );
		$this->box_packer_library = $this->get_option( 'box_packer_library', $this->get_default_box_packer_library() );
		$this->ups_packaging      = $this->get_option( 'ups_packaging', array() );
		$this->custom_services    = $this->get_option( 'services', array() );
		$this->boxes              = $this->get_option( 'boxes', array() );
		$this->insuredvalue       = $this->get_option( 'insuredvalue' ) === 'yes';
		$this->signature          = $this->get_option( 'signature', 'none' );
		$this->tax_status         = $this->get_option( 'tax_status' );

		// Initialize API class objects.
		$this->ups_oauth = new REST_API_OAuth( $this->client_id, $this->client_secret );
		$this->maybe_disable_xml_api();
		$this->ups_api = 'rest' === $this->get_api_type() ? new REST_API_Client( $this ) : new Legacy_API_Client( $this );

		/**
		 * If no origin country / state saved / exists, set it to store base country:
		 */
		if ( ! $origin_country_state ) {
			$origin               = wc_get_base_location();
			$this->origin_country = $origin['country'];
			$this->origin_state   = $origin['state'];
		} else {
			$this->split_country_state( $origin_country_state );
		}

		// Set weight/dimension units.
		$this->units = $this->get_option( 'units', 'imperial' );

		switch ( $this->units ) {
			case 'metric':
				$this->weight_unit = 'KGS';
				$this->dim_unit    = 'CM';
				break;
			case 'imperial':
				$this->weight_unit = 'LBS';
				$this->dim_unit    = 'IN';
				break;
			case 'auto':
			default:
				/**
				 * Filter the countries that use LBS for weight and IN for dimensions.
				 *
				 * @since 3.2.8
				 *
				 * @param array $countries Countries that use LBS for weight.
				 */
				$lbs_countries = apply_filters( 'woocommerce_shipping_ups_lbs_countries', array( 'US' ) );

				/**
				 * Filter the countries that use IN for dimensions.
				 *
				 * @since 3.2.8
				 *
				 * @param array $countries Countries that use IN for dimensions.
				 */
				$in_countries = apply_filters( 'woocommerce_shipping_ups_in_countries', array( 'US' ) );

				$this->weight_unit = in_array( $this->origin_country, $lbs_countries, true ) ? 'LBS' : 'KGS';
				$this->dim_unit    = in_array( $this->origin_country, $in_countries, true ) ? 'IN' : 'CM';
				break;
		}

		/**
		 * Set the notifier.
		 */
		$this->notifier = new Notifier( $this->debug );

		/**
		 * Set the logger.
		 */
		$this->logger = new Logger( $this->debug );

		return true;
	}

	/**
	 * Initialization.
	 *
	 * @return void
	 */
	private function init() {

		// Load the settings.
		$this->init_form_fields();
		$this->set_settings();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'clear_transients' ) );

		add_action( 'woocommerce_before_calculate_totals', array( $this, 'maybe_clear_shipping_cache' ), 11 );

		add_action( 'woocommerce_after_get_rates_for_package', array( $this, 'woocommerce_after_get_rates_for_package_callback' ), 11, 2 );
	}

	/**
	 * If the box packer library option is not yet set and there are existing
	 * USPS shipping method instances, we can assume that this is not a
	 * new/fresh installation of the USPS plugin,
	 * so we should default to 'original'
	 *
	 * If the box packer library option is not set and there are no
	 * USPS shipping method instances, then this is likely a new
	 * installation of the USPS plugin,
	 * so we should default to 'dvdoug'
	 *
	 * @return string
	 */
	public function get_default_box_packer_library(): string {
		if ( ( empty( $this->get_option( 'box_packer_library' ) ) && $this->instances_exist() ) ) {
			return 'original';
		} else {
			return 'dvdoug';
		}
	}

	/**
	 * Maybe clear the shipping cache.
	 *
	 * This is essential for address validation to work correctly.
	 */
	public function maybe_clear_shipping_cache() {
		// Only run on classic checkout.
		if ( ! $this->is_classic_checkout_page() ) {
			return;
		}

		static $has_run = false;

		if ( $has_run ) {
			return;
		}

		// Loop through cart shipping methods and check if at least one UPS method is found with address validation enabled.
		$address_validation_enabled = false;
		foreach ( WC()->cart->get_shipping_packages() as $package ) {
			$shipping_zone    = WC_Shipping_Zones::get_zone_matching_package( $package );
			$shipping_methods = $shipping_zone->get_shipping_methods( true );

			$ups_instance = null;
			foreach ( $shipping_methods as $shipping_method ) {
				if ( $shipping_method instanceof WC_Shipping_UPS ) {
					$ups_instance = $shipping_method;
					break;
				}
			}

			if ( ! $ups_instance instanceof WC_Shipping_UPS ) {
				continue;
			}

			if ( 'yes' === $ups_instance->get_option( 'destination_address_validation' ) ) {
				$address_validation_enabled = true;
				break;
			}
		}

		// Only clear the cache if debug mode is enabled or address validation is enabled for at least one UPS method.
		if ( ! $this->is_debug_mode_enabled() && ! $address_validation_enabled ) {
			return;
		}

		$this->clear_shipping_cache();

		$has_run = true;
	}

	/**
	 * Clear the shipping cache.
	 */
	public function clear_shipping_cache() {
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Check if the current page is the classic checkout page.
	 * This is the case when the checkout block is not present.
	 *
	 * @return bool
	 */
	public function is_classic_checkout_page(): bool {
		return is_checkout() && ! has_block( 'woocommerce/checkout' );
	}

	/**
	 * Check if the current page is the classic cart page.
	 * This is the case when the cart block is not present.
	 *
	 * @return bool
	 */
	public function is_classic_cart_page(): bool {
		return is_cart() && ! has_block( 'woocommerce/cart' );
	}

	/**
	 * After get rates for package callback.
	 *
	 * @param array              $package Package of cart items.
	 * @param WC_Shipping_Method $shipping_method Shipping method instance.
	 * @return void
	 */
	public function woocommerce_after_get_rates_for_package_callback( $package, $shipping_method ) {
		$this->maybe_display_notices();
	}

	/**
	 * If the conditions are met, display the notices.
	 *
	 * @return void
	 */
	public function maybe_display_notices() {
		if ( ! $this->is_classic_checkout_page() && ! $this->is_classic_cart_page() ) {
			return;
		}

		$this->notifier->print_notices();

		$this->notifier->clear_notices();
	}

	/**
	 * Process settings on save.
	 *
	 * @return void
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	public function process_admin_options() {
		parent::process_admin_options();

		$this->set_settings();
	}

	/**
	 * Helper method to split the country/state and set them.
	 *
	 * @param string $country_state Value of Origin country.
	 */
	public function split_country_state( string $country_state ) {
		if ( strstr( $country_state, ':' ) ) {
			$origin_country_state = explode( ':', $country_state );
			$this->origin_country = current( $origin_country_state );
			$this->origin_state   = end( $origin_country_state );
		} else {
			$this->origin_country = $country_state;
			$this->origin_state   = '';
		}
	}

	/**
	 * Environment check.
	 *
	 * @return void
	 */
	private function environment_check() {
		$error_message = '';

		// Check for API settings.
		if ( ! $this->are_api_settings_complete() ) {
			$error_message .= '<p>' . __( 'UPS is enabled, but you have not entered all of your UPS details!', 'woocommerce-shipping-ups' ) . '</p>';
		}

		// Check environment only on shipping instance page.
		if ( 0 < $this->instance_id ) {
			// If user has selected to pack into boxes, check if at least one
			// UPS packaging is chosen, or a custom box is defined.
			if ( 'box_packing' === $this->packing_method ) {
				if ( empty( $this->ups_packaging ) && empty( $this->boxes ) ) {
					$error_message .= '<p>' . __( 'UPS is enabled, and Parcel Packing Method is set to \'Pack into boxes\', but no UPS Packaging is selected and there are no custom boxes defined. Items will be packed individually.', 'woocommerce-shipping-ups' ) . '</p>';
				}
			}

			// Check for at least one service enabled.
			$ctr = 0;
			if ( isset( $this->custom_services ) && is_array( $this->custom_services ) ) {
				foreach ( $this->custom_services as $key => $values ) {
					if ( $values['enabled'] ) {
						++$ctr;
					}
				}
			}
			if ( 0 === $ctr ) {
				$error_message .= '<p>' . __( 'UPS is enabled, but there are no services enabled.', 'woocommerce-shipping-ups' ) . '</p>';
			}
		}

		if ( '' !== $error_message ) {
			echo '<div class="error">';
			echo wp_kses_post( $error_message );
			echo '</div>';
		}
	}

	/**
	 * Check if the API settings are complete.
	 *
	 * @return bool
	 */
	private function are_api_settings_complete(): bool {
		if ( 'rest' === $this->get_api_type() ) {
			return ! empty( $this->get_client_id() ) && ! empty( $this->get_client_secret() ) && ! empty( $this->shipper_number );
		}

		return ! empty( $this->get_user_id() ) && ! empty( $this->password ) && ! empty( $this->access_key ) && ! empty( $this->shipper_number );
	}

	/**
	 * Admin options.
	 *
	 * @return void
	 */
	public function admin_options() {
		// Check users environment supports this method.
		$this->environment_check();

		// Enqueue UPS Scripts.
		wp_enqueue_script( 'ups-admin-js' );

		// Show settings.
		parent::admin_options();
	}

	/**
	 * Get the default API type.
	 *
	 * If there are existing UPS method instances, default to XML.
	 *
	 * @return string
	 */
	private function get_default_api_type(): string {
		return ! empty( $this->get_option( 'user_id' ) ) ? 'xml' : 'rest';
	}

	/**
	 * HTML for origin country option.
	 *
	 * @used-by WC_Settings_API::generate_settings_html()
	 *
	 * @return string HTML string.
	 */
	public function generate_single_select_country_html(): string {
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="origin_country"><?php esc_html_e( 'Origin Country', 'woocommerce-shipping-ups' ); ?></label>
			</th>
			<td class="forminp">
				<select name="woocommerce_ups_origin_country_state"
						id="woocommerce_ups_origin_country_state"
						style="width: 250px;"
						data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'woocommerce' ); ?>"
						title="Country"
						class="chosen_select">
					<?php WC()->countries->country_dropdown_options( $this->origin_country, ! empty( $this->origin_state ) ? $this->origin_state : '*' ); ?>
				</select>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * HTML for service option.
	 *
	 * @used-by WC_Settings_API::generate_settings_html()
	 *
	 * @return string HTML string.
	 */
	public function generate_services_html(): string {
		ob_start();
		?>
		<tr valign="top" id="service_options">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Services', 'woocommerce-shipping-ups' ); ?></th>
			<td class="forminp">
				<table class="ups_services widefat">
					<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th><?php esc_html_e( 'Service Code', 'woocommerce-shipping-ups' ); ?></th>
							<th><?php esc_html_e( 'Name', 'woocommerce-shipping-ups' ); ?></th>
							<th><?php esc_html_e( 'Enabled', 'woocommerce-shipping-ups' ); ?></th>
							<th>
								<?php
								printf(
								/* translators: %s: currency symbol */
									esc_html__( 'Price Adjustment (%s)', 'woocommerce-shipping-ups' ),
									esc_html( get_woocommerce_currency_symbol() )
								);
								?>
							</th>
							<th><?php esc_html_e( 'Price Adjustment (%)', 'woocommerce-shipping-ups' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<?php if ( 'PL' !== $this->origin_country && ! in_array( $this->origin_country, $this->eu_array, true ) ) : ?>
							<tr>
								<th colspan="6">
									<small class="description"><?php echo wp_kses_post( __( '<strong>Domestic Rates</strong>: Next Day Air, 2nd Day Air, Ground, 3 Day Select, Next Day Air Saver, Next Day Air Early AM, 2nd Day Air AM', 'woocommerce-shipping-ups' ) ); ?></small><br />
									<small class="description"><?php echo wp_kses_post( __( '<strong>International Rates</strong>: Worldwide Express, Worldwide Expedited, Standard, Worldwide Express Plus, UPS Saver', 'woocommerce-shipping-ups' ) ); ?></small>
								</th>
							</tr>
						<?php endif ?>
					</tfoot>
					<tbody>
						<?php
						$sort                   = 0;
						$this->ordered_services = array();

						if ( 'PL' === $this->origin_country ) {
							$use_services = $this->polandservices;
						} elseif ( in_array( $this->origin_country, $this->eu_array, true ) ) {
							$use_services = $this->euservices;
						} else {
							$use_services = $this->services;
						}

						foreach ( $use_services as $code => $name ) {

							if ( isset( $this->custom_services[ $code ]['order'] ) ) {
								$sort = absint( $this->custom_services[ $code ]['order'] );
							}

							while ( isset( $this->ordered_services[ $sort ] ) ) {
								++$sort;
							}

							$this->ordered_services[ $sort ] = array( $code, $name );

							++$sort;
						}

						ksort( $this->ordered_services );

						foreach ( $this->ordered_services as $value ) {
							$code              = $value[0];
							$name              = $value[1];
							$input_name_prefix = 'ups_service[' . $code . ']';
							$input_data        = array(
								'order'              => array(
									'name'  => $input_name_prefix . '[order]',
									'value' => isset( $this->custom_services[ $code ]['order'] ) ? $this->custom_services[ $code ]['order'] : '',
								),
								'name'               => array(
									'name'        => $input_name_prefix . '[name]',
									'placeholder' => $name . ' (' . $this->title . ')',
									'value'       => isset( $this->custom_services[ $code ]['name'] ) ? $this->custom_services[ $code ]['name'] : '',
								),
								'enabled'            => array(
									'name'    => $input_name_prefix . '[enabled]',
									'checked' => ( ! isset( $this->custom_services[ $code ]['enabled'] ) || ! empty( $this->custom_services[ $code ]['enabled'] ) ),
								),
								'adjustment'         => array(
									'name'  => $input_name_prefix . '[adjustment]',
									'value' => isset( $this->custom_services[ $code ]['adjustment'] ) ? $this->custom_services[ $code ]['adjustment'] : '',
								),
								'adjustment_percent' => array(
									'name'  => $input_name_prefix . '[adjustment_percent]',
									'value' => isset( $this->custom_services[ $code ]['adjustment_percent'] ) ? $this->custom_services[ $code ]['adjustment_percent'] : '',
								),
							);
							?>

							<tr>
								<td class="sort">
									<input type="hidden"
											class="order"
											name="<?php echo esc_attr( $input_data['order']['name'] ); ?>"
											value="<?php echo esc_attr( $input_data['order']['value'] ); ?>" />
								</td>
								<td><strong><?php echo esc_html( $code ); ?></strong></td>
								<td>
									<input type="text"
											name="<?php echo esc_attr( $input_data['name']['name'] ); ?>"
											placeholder="<?php echo esc_attr( $input_data['name']['placeholder'] ); ?>"
											value="<?php echo esc_attr( $input_data['name']['value'] ); ?>"
											size="50" />
								</td>
								<td>
									<input type="checkbox"
											name="<?php echo esc_attr( $input_data['enabled']['name'] ); ?>" <?php checked( $input_data['enabled']['checked'], true ); ?> />
								</td>
								<td>
									<input type="text"
											name="<?php echo esc_attr( $input_data['adjustment']['name'] ); ?>"
											placeholder="N/A"
											value="<?php echo esc_attr( $input_data['adjustment']['value'] ); ?>"
											size="4" />
								</td>
								<td>
									<input type="text"
											name="<?php echo esc_attr( $input_data['adjustment_percent']['name'] ); ?>"
											placeholder="N/A"
											value="<?php echo esc_attr( $input_data['adjustment_percent']['value'] ); ?>"
											size="4" />
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * HTML for box packing option.
	 *
	 * @used-by WC_Settings_API::generate_settings_html()
	 *
	 * @return string HTML string.
	 */
	public function generate_box_packing_html(): string {
		ob_start();
		?>
		<tr valign="top" id="packing_options">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Custom Boxes', 'woocommerce-shipping-ups' ); ?></th>
			<td class="forminp">
				<table class="ups_boxes widefat">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" /></th>
							<th><?php esc_html_e( 'Outer Length', 'woocommerce-shipping-ups' ); ?></th>
							<th><?php esc_html_e( 'Outer Width', 'woocommerce-shipping-ups' ); ?></th>
							<th><?php esc_html_e( 'Outer Height', 'woocommerce-shipping-ups' ); ?></th>
							<th><?php esc_html_e( 'Inner Length', 'woocommerce-shipping-ups' ); ?></th>
							<th><?php esc_html_e( 'Inner Width', 'woocommerce-shipping-ups' ); ?></th>
							<th><?php esc_html_e( 'Inner Height', 'woocommerce-shipping-ups' ); ?></th>
							<th><?php esc_html_e( 'Weight of Box', 'woocommerce-shipping-ups' ); ?></th>
							<th><?php esc_html_e( 'Max Weight', 'woocommerce-shipping-ups' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="3">
								<a href="#"
									class="button plus insert"><?php esc_html_e( 'Add Box', 'woocommerce-shipping-ups' ); ?></a>
								<a href="#"
									class="button minus remove"><?php esc_html_e( 'Remove selected box(es)', 'woocommerce-shipping-ups' ); ?></a>
							</th>
							<th colspan="6">
								<small class="description"><?php esc_html_e( 'Items will be packed into these boxes based on item dimensions and volume. Outer dimensions will be passed to UPS, whereas inner dimensions will be used for packing. Items not fitting into boxes will be packed individually.', 'woocommerce-shipping-ups' ); ?></small>
							</th>
						</tr>
					</tfoot>
					<tbody id="rates">
						<?php
						if ( $this->boxes && ! empty( $this->boxes ) ) {
							foreach ( $this->boxes as $key => $box ) {
								?>
								<tr>
									<td class="check-column"><input type="checkbox" /></td>
									<td>
										<input type="text"
												size="5"
												name="boxes_outer_length[<?php echo esc_attr( $key ); ?>]"
												value="<?php echo esc_attr( $box['outer_length'] ); ?>" />
										<?php echo esc_html( $this->dim_unit ); ?>
									</td>
									<td>
										<input type="text"
												size="5"
												name="boxes_outer_width[<?php echo esc_attr( $key ); ?>]"
												value="<?php echo esc_attr( $box['outer_width'] ); ?>" />
										<?php echo esc_html( $this->dim_unit ); ?>
									</td>
									<td>
										<input type="text"
												size="5"
												name="boxes_outer_height[<?php echo esc_attr( $key ); ?>]"
												value="<?php echo esc_attr( $box['outer_height'] ); ?>" />
										<?php echo esc_html( $this->dim_unit ); ?>
									</td>
									<td>
										<input type="text"
												size="5"
												name="boxes_inner_length[<?php echo esc_attr( $key ); ?>]"
												value="<?php echo esc_attr( $box['inner_length'] ); ?>" />
										<?php echo esc_html( $this->dim_unit ); ?>
									</td>
									<td>
										<input type="text"
												size="5"
												name="boxes_inner_width[<?php echo esc_attr( $key ); ?>]"
												value="<?php echo esc_attr( $box['inner_width'] ); ?>" />
										<?php echo esc_html( $this->dim_unit ); ?>
									</td>
									<td>
										<input type="text"
												size="5"
												name="boxes_inner_height[<?php echo esc_attr( $key ); ?>]"
												value="<?php echo esc_attr( $box['inner_height'] ); ?>" />
										<?php echo esc_html( $this->dim_unit ); ?>
									</td>
									<td>
										<input type="text"
												size="5"
												name="boxes_box_weight[<?php echo esc_attr( $key ); ?>]"
												value="<?php echo esc_attr( $box['box_weight'] ); ?>" />
										<?php echo esc_html( $this->weight_unit ); ?>
									</td>
									<td>
										<input type="text"
												size="5"
												name="boxes_max_weight[<?php echo esc_attr( $key ); ?>]"
												value="<?php echo esc_attr( $box['max_weight'] ); ?>" />
										<?php echo esc_html( $this->weight_unit ); ?>
									</td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Validate origin country option.
	 *
	 * @param mixed $key Option's key.
	 *
	 * @used-by WC_Settings_API::get_field_value()
	 *
	 * @return string Validated value.
	 */
	public function validate_single_select_country_field( $key ): string {
		// The nonce check is already performed in WC_Settings_Shipping::instance_settings_screen(), so no need to check it again.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST['woocommerce_ups_origin_country_state'] ) ? wc_clean( wp_unslash( $_POST['woocommerce_ups_origin_country_state'] ) ) : '';
	}

	/**
	 * Validate box packing option.
	 *
	 * @param mixed $key Option's key.
	 *
	 * @used-by WC_Settings_API::get_field_value()
	 *
	 * @return array Validated value.
	 */
	public function validate_box_packing_field( $key ): array {
		$boxes = array();

		// The nonce check is already performed in WC_Settings_Shipping::instance_settings_screen(), so no need to check it again.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['boxes_outer_length'] ) ) {
			$boxes_outer_length = ! empty( $_POST['boxes_outer_length'] ) ? wc_clean( wp_unslash( $_POST['boxes_outer_length'] ) ) : array();
			$boxes_outer_width  = ! empty( $_POST['boxes_outer_width'] ) ? wc_clean( wp_unslash( $_POST['boxes_outer_width'] ) ) : array();
			$boxes_outer_height = ! empty( $_POST['boxes_outer_height'] ) ? wc_clean( wp_unslash( $_POST['boxes_outer_height'] ) ) : array();
			$boxes_inner_length = ! empty( $_POST['boxes_inner_length'] ) ? wc_clean( wp_unslash( $_POST['boxes_inner_length'] ) ) : array();
			$boxes_inner_width  = ! empty( $_POST['boxes_inner_width'] ) ? wc_clean( wp_unslash( $_POST['boxes_inner_width'] ) ) : array();
			$boxes_inner_height = ! empty( $_POST['boxes_inner_height'] ) ? wc_clean( wp_unslash( $_POST['boxes_inner_height'] ) ) : array();
			$boxes_box_weight   = ! empty( $_POST['boxes_box_weight'] ) ? wc_clean( wp_unslash( $_POST['boxes_box_weight'] ) ) : array();
			$boxes_max_weight   = ! empty( $_POST['boxes_max_weight'] ) ? wc_clean( wp_unslash( $_POST['boxes_max_weight'] ) ) : array();
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$boxes_outer_length_count = count( $boxes_outer_length );

			if ( $boxes_outer_length_count > 0 ) {
				for ( $i = 0; $i < $boxes_outer_length_count; $i++ ) {

					if ( $boxes_outer_length[ $i ] && $boxes_outer_width[ $i ] && $boxes_outer_height[ $i ] && $boxes_inner_length[ $i ] && $boxes_inner_width[ $i ] && $boxes_inner_height[ $i ] ) {

						$boxes[] = array(
							'outer_length' => floatval( $boxes_outer_length[ $i ] ),
							'outer_width'  => floatval( $boxes_outer_width[ $i ] ),
							'outer_height' => floatval( $boxes_outer_height[ $i ] ),
							'inner_length' => floatval( $boxes_inner_length[ $i ] ),
							'inner_width'  => floatval( $boxes_inner_width[ $i ] ),
							'inner_height' => floatval( $boxes_inner_height[ $i ] ),
							'box_weight'   => floatval( $boxes_box_weight[ $i ] ),
							'max_weight'   => floatval( $boxes_max_weight[ $i ] ),
						);
					}
				}
			}
		}

		return $boxes;
	}

	/**
	 * Validate services option.
	 *
	 * @param mixed $key Option's key.
	 *
	 * @used-by WC_Settings_API::get_field_value()
	 *
	 * @return array Validated value.
	 */
	public function validate_services_field( $key ): array {
		$services = array();

		// The nonce check is already performed in WC_Settings_Shipping::instance_settings_screen(), so no need to check it again.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted_services = ! empty( $_POST['ups_service'] ) ? wc_clean( wp_unslash( $_POST['ups_service'] ) ) : array();

		foreach ( $posted_services as $code => $settings ) {

			$services[ $code ] = array(
				'name'               => wc_clean( $settings['name'] ),
				'order'              => wc_clean( $settings['order'] ),
				'enabled'            => isset( $settings['enabled'] ),
				'adjustment'         => wc_clean( $settings['adjustment'] ),
				'adjustment_percent' => str_replace( '%', '', wc_clean( $settings['adjustment_percent'] ) ),
			);
		}

		return $services;
	}

	/**
	 * Clear UPS transients.
	 *
	 * @return void
	 */
	public function clear_transients() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --- Reason: This requires a direct query.
		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_ups_quote_%') OR `option_name` LIKE ('_transient_timeout_ups_quote_%')" );
	}

	/**
	 * Set form fields.
	 *
	 * @since   1.0.0
	 * @version 3.2.5
	 */
	public function init_form_fields() {

		/**
		 * Filter the instance form fields.
		 *
		 * @since 1.0.0
		 */
		$this->instance_form_fields = apply_filters(
			'woocommerce_shipping_' . $this->id . '_instance_form_fields',
			array(
				'core'                           => array(
					'title'       => __( 'Method & Origin Settings', 'woocommerce-shipping-ups' ),
					'type'        => 'title',
					'description' => '',
					'class'       => 'ups-section-title',
				),
				'title'                          => array(
					'title'       => __( 'Method Title', 'woocommerce-shipping-ups' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-shipping-ups' ),
					'default'     => __( 'UPS', 'woocommerce-shipping-ups' ),
					'desc_tip'    => true,
				),
				'tax_status'                     => array(
					'title'       => __( 'Tax Status', 'woocommerce-shipping-ups' ),
					'type'        => 'select',
					'description' => '',
					'default'     => 'taxable',
					'options'     => array(
						'taxable' => __( 'Taxable', 'woocommerce-shipping-ups' ),
						'none'    => __( 'None', 'woocommerce-shipping-ups' ),
					),
				),
				'origin_city'                    => array(
					'title'       => __( 'Origin City', 'woocommerce-shipping-ups' ),
					'type'        => 'text',
					'description' => __( 'Enter the city for the <strong>sender</strong>.', 'woocommerce-shipping-ups' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'origin_postcode'                => array(
					'title'       => __( 'Origin Postcode', 'woocommerce-shipping-ups' ),
					'type'        => 'text',
					'description' => __( 'Enter the zip/postcode for the <strong>sender</strong>.', 'woocommerce-shipping-ups' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'origin_country_state'           => array(
					'type' => 'single_select_country',
				),
				'services_packaging'             => array(
					'title'       => __( 'Services and Packaging', 'woocommerce-shipping-ups' ),
					'type'        => 'title',
					'description' => __( 'Please enable all of the different services you\'d like to offer customers.', 'woocommerce-shipping-ups' ) . ' <em>' . __( 'By enabling a service, it doesn\'t guarantee that it will be offered, as the plugin will only offer the available rates based on the package, the origin and the destination.', 'woocommerce-shipping-ups' ) . '</em>',
					'class'       => 'ups-section-title',
				),
				'services'                       => array(
					'type' => 'services',
				),
				'offer_rates'                    => array(
					'title'       => __( 'Offer Rates', 'woocommerce-shipping-ups' ),
					'type'        => 'select',
					'description' => '',
					'default'     => 'all',
					'options'     => array(
						'all'      => __( 'Offer the customer all returned rates', 'woocommerce-shipping-ups' ),
						'cheapest' => __( 'Offer the customer the cheapest rate only', 'woocommerce-shipping-ups' ),
					),
				),
				'simple_rate'                    => array(
					'title'       => __( 'UPS Simple Rate', 'woocommerce-shipping-ups' ),
					'label'       => __( 'Enable UPS Simple Rate', 'woocommerce-shipping-ups' ),
					'type'        => 'checkbox',
					'default'     => 'no',
					'description' => sprintf(
					/* translators: %1$s and %2$s are used for anchor tags, %3$s is used for a line break */
						__( 'Enable this if you want to use %1$sUPS Simple Rate%2$s. %3$sCompatible with UPS Next Day Air Saver®, UPS 2nd Day Air®, UPS 3 Day Select® and UPS® Ground.', 'woocommerce-shipping-ups' ),
						'<a href="https://www.ups.com/us/en/services/shipping/simple-rate.page" target="_blank">',
						'</a>',
						'<br>'
					),
				),
				'negotiated'                     => array(
					'title'       => __( 'Negotiated Rates', 'woocommerce-shipping-ups' ),
					'label'       => __( 'Enable negotiated rates', 'woocommerce-shipping-ups' ),
					'type'        => 'checkbox',
					'default'     => 'no',
					'description' => sprintf(
					/* translators: %1$s and %2$s are used for strong tags, %3$s and %4$s are used for anchor tags */
						__( 'Enable this %1$sonly%2$s if this shipping account has %3$snegotiated rates%4$s available.', 'woocommerce-shipping-ups' ),
						'<strong>',
						'</strong>',
						'<a href="https://www.ups.com/au/en/help-center/technology-support/worldship/negotiated-rates.page">',
						'</a>'
					),
				),
				'signature'                      => array(
					'title'       => __( 'Delivery Confirmation', 'woocommerce-shipping-ups' ),
					'type'        => 'select',
					'default'     => 'none',
					'description' => __( 'Optionally you may charge customers for signature on delivery. This will just add the specified amount above to the returned rates.', 'woocommerce-shipping-ups' ),
					'desc_tip'    => true,
					'options'     => array(
						'none'    => __( 'No Signature Required', 'woocommerce-shipping-ups' ),
						'regular' => __( 'Signature Required', 'woocommerce-shipping-ups' ),
						'adult'   => __( 'Adult Signature Required', 'woocommerce-shipping-ups' ),
					),
				),
				'packing_method'                 => array(
					'title'   => __( 'Parcel Packing Method', 'woocommerce-shipping-ups' ),
					'type'    => 'select',
					'default' => '',
					'class'   => 'packing_method',
					'options' => array(
						'per_item'    => __( 'Default: Pack items individually', 'woocommerce-shipping-ups' ),
						'box_packing' => __( 'Recommended: Pack into boxes with weights and dimensions', 'woocommerce-shipping-ups' ),
					),
				),
				'ups_packaging'                  => array(
					'title'       => __( 'UPS Packaging', 'woocommerce-shipping-ups' ),
					'type'        => 'multiselect',
					'description' => __( 'Select UPS standard packaging options to enable', 'woocommerce-shipping-ups' ),
					'default'     => array(),
					'css'         => 'width: 450px;',
					'class'       => 'ups_packaging chosen_select',
					'options'     => $this->packaging_select,
				),
				'boxes'                          => array(
					'type' => 'box_packing',
				),
				'advanced_title'                 => array(
					'title'       => __( 'Advanced Options', 'woocommerce-shipping-ups' ),
					'type'        => 'title',
					'description' => __( 'Only modify the following options if needed. They will most likely alter the regularly offered rate(s).', 'woocommerce-shipping-ups' ),
					'class'       => 'ups-section-title',
				),
				'origin_addressline'             => array(
					'title'       => __( 'Origin Address', 'woocommerce-shipping-ups' ),
					'type'        => 'text',
					'description' => __( 'Sometimes you may need to enter the address for the <strong>sender / origin</strong>.', 'woocommerce-shipping-ups' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'residential'                    => array(
					'title'       => __( 'Residential', 'woocommerce-shipping-ups' ),
					'label'       => __( 'Enable residential address flag', 'woocommerce-shipping-ups' ),
					'type'        => 'checkbox',
					'default'     => 'yes',
					'description' => __( 'Enable this to indicate to UPS that the receiver / customer is a residential address.', 'woocommerce-shipping-ups' ),
					'desc_tip'    => true,
				),
				'destination_address_validation' => array(
					'title'       => __( 'Destination Address Validation', 'woocommerce-shipping-ups' ),
					'label'       => __( 'Enable destination address validation', 'woocommerce-shipping-ups' ),
					'type'        => 'checkbox',
					'default'     => 'no',
					'description' => __( 'The Address Validation Street Level API can be used to check addresses against the United States Postal Service database of valid addresses in the U.S. and Puerto Rico.', 'woocommerce-shipping-ups' ),
					'desc_tip'    => true,
				),
				'insuredvalue'                   => array(
					'title'       => __( 'Insured Value', 'woocommerce-shipping-ups' ),
					'label'       => __( 'Request Insurance to be included in UPS rates', 'woocommerce-shipping-ups' ),
					'type'        => 'checkbox',
					'default'     => 'no',
					'description' => __( 'Enable insured value option to include insurance in UPS rates', 'woocommerce-shipping-ups' ),
					'desc_tip'    => true,
				),
				'fallback'                       => array(
					'title'       => __( 'Fallback', 'woocommerce-shipping-ups' ),
					'type'        => 'price',
					'description' => __( 'If UPS returns no matching rates, offer this amount for shipping so that the user can still checkout. Leave blank to disable. Enter a numeric value with no currency symbols.', 'woocommerce-shipping-ups' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'units'                          => array(
					'title'       => __( 'Weight/Dimension Units', 'woocommerce-shipping-ups' ),
					'type'        => 'select',
					'description' => __( 'If you see "This measurement system is not valid for the selected country" errors, switch this to metric units.', 'woocommerce-shipping-ups' ),
					'desc_tip'    => true,
					'default'     => 'imperial',
					'options'     => array(
						'imperial' => __( 'LB / IN', 'woocommerce-shipping-ups' ),
						'metric'   => __( 'KG / CM', 'woocommerce-shipping-ups' ),
						'auto'     => __( 'Automatic (based on shipping zone origin)', 'woocommerce-shipping-ups' ),
					),
				),
			)
		);

		/**
		 * Filter the UPS instance form fields.
		 *
		 * @since 1.0.0
		 */
		$this->form_fields = apply_filters(
			'woocommerce_shipping_' . $this->id . 'form_fields',
			array(
				'api'                          => array(
					'title'       => __( 'API Settings', 'woocommerce-shipping-ups' ),
					'type'        => 'title',
					'description' => sprintf(
					/* translators: %1$s and %2$s are the opening and closing <a> tags of the link to the UPS developer portal */
						__( 'You need to obtain UPS account credentials by registering on %1$svia their website%2$s.', 'woocommerce-shipping-ups' ),
						'<a href="https://www.ups.com/upsdeveloperkit">',
						'</a>'
					),
					'class'       => 'ups-section-title ups-api-title',
				),
				'api_type'                     => array(
					'title'       => __( 'UPS API type', 'woocommerce-shipping-ups' ),
					'type'        => 'select',
					'css'         => 'width: 250px;',
					'description' => __( 'Select whether to use the legacy XML API or the new REST API.', 'woocommerce-shipping-ups' ),
					'desc_tip'    => true,
					'class'       => 'chosen_select ups-api-setting',
					'default'     => $this->api_type ?? $this->get_default_api_type(),
					'options'     => array(
						'rest' => __( 'REST', 'woocommerce-shipping-ups' ),
						'xml'  => __( 'XML (legacy)', 'woocommerce-shipping-ups' ),
					),
				),
				'shipper_number'               => array(
					'title'       => __( 'UPS Account Number', 'woocommerce-shipping-ups' ),
					'type'        => 'text',
					'description' => __( 'Obtained from UPS after getting an account.', 'woocommerce-shipping-ups' ),
					'default'     => '',
					'class'       => 'ups-api-setting',
					'desc_tip'    => true,
				),
				'client_id'                    => array(
					'title'             => __( 'UPS Client ID', 'woocommerce-shipping-ups' ),
					'type'              => 'password',
					'description'       => __( 'Obtained from UPS after creating an App in the UPS developer portal.', 'woocommerce-shipping-ups' ),
					'default'           => '',
					'class'             => 'ups-api-setting',
					'desc_tip'          => true,
					'custom_attributes' => array( 'data-ups_api_type' => 'rest' ),
				),
				'client_secret'                => array(
					'title'             => __( 'UPS Client Secret', 'woocommerce-shipping-ups' ),
					'type'              => 'password',
					'description'       => __( 'Obtained from UPS after creating an App in the UPS developer portal.', 'woocommerce-shipping-ups' ),
					'default'           => '',
					'class'             => 'ups-api-setting',
					'desc_tip'          => true,
					'custom_attributes' => array( 'data-ups_api_type' => 'rest' ),
				),
				'oauth_status'                 => array(
					'title'             => __( 'REST API Status', 'woocommerce-shipping-ups' ),
					'type'              => 'oauth_status',
					'description'       => __( 'Displays the current UPS REST API authorization status.', 'woocommerce-shipping-ups' ),
					'class'             => 'ups-api-setting',
					'desc_tip'          => true,
					'custom_attributes' => array( 'data-ups_api_type' => 'rest' ),
				),
				'user_id'                      => array(
					'title'             => __( 'UPS User ID', 'woocommerce-shipping-ups' ),
					'type'              => 'text',
					'description'       => __( 'Obtained from UPS after getting an account.', 'woocommerce-shipping-ups' ),
					'default'           => '',
					'class'             => 'ups-api-setting',
					'desc_tip'          => true,
					'custom_attributes' => array( 'data-ups_api_type' => 'xml' ),
				),
				'password'                     => array(
					'title'             => __( 'UPS Password', 'woocommerce-shipping-ups' ),
					'type'              => 'password',
					'description'       => __( 'Obtained from UPS after getting an account.', 'woocommerce-shipping-ups' ),
					'default'           => '',
					'class'             => 'ups-api-setting',
					'desc_tip'          => true,
					'custom_attributes' => array( 'data-ups_api_type' => 'xml' ),
				),
				'access_key'                   => array(
					'title'             => __( 'UPS Access Key', 'woocommerce-shipping-ups' ),
					'type'              => 'text',
					'description'       => __( 'Obtained from UPS after getting an account.', 'woocommerce-shipping-ups' ),
					'default'           => '',
					'class'             => 'ups-api-setting',
					'desc_tip'          => true,
					'custom_attributes' => array( 'data-ups_api_type' => 'xml' ),
				),
				'customer_classification_code' => array(
					'title'       => __( 'Customer Classification', 'woocommerce-shipping-ups' ),
					'type'        => 'select',
					'css'         => 'width: 250px;',
					'description' => __( 'This option only valid if origin country is US.', 'woocommerce-shipping-ups' ),
					'desc_tip'    => true,
					'class'       => 'chosen_select',
					'default'     => '',
					'options'     => $this->get_customer_classifications(),
				),
				'box_packer_library'           => array(
					'title'       => __( 'Box Packer Library', 'woocommerce-shipping-ups' ),
					'type'        => 'select',
					'default'     => 'original',
					'class'       => 'box_packer_library',
					'options'     => array(
						'original' => __( 'Speed Packer', 'woocommerce-shipping-ups' ),
						'dvdoug'   => __( 'Accurate Packer', 'woocommerce-shipping-ups' ),
					),
					'description' => __( 'Speed Packer packs items by volume, Accurate Packer check each dimension allowing more accurate packing but might be slow when you sell items in large quantities.', 'woocommerce-shipping-ups' ),
				),
				'debug'                        => array(
					'title'       => __( 'Debug Mode', 'woocommerce-shipping-ups' ),
					'label'       => __( 'Enable debug mode', 'woocommerce-shipping-ups' ),
					'type'        => 'checkbox',
					'default'     => 'no',
					'description' => __( 'Enable debug mode to show debugging information on your cart/checkout.', 'woocommerce-shipping-ups' ),
					'desc_tip'    => true,
				),
			)
		);
	}

	/**
	 * HTML for oauth_status option.
	 *
	 * @param string $key  Option key.
	 * @param array  $data Option data.
	 *
	 * @used-by WC_Settings_API::generate_settings_html()
	 *
	 * @return string HTML for oauth_status option.
	 */
	public function generate_oauth_status_html( string $key, array $data ): string {

		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		include 'views/html-oauth-status.php';

		return ob_get_clean();
	}

	/**
	 * Calculate shipping cost.
	 *
	 * @param array $package Package to ship.
	 *
	 * @version 3.2.5
	 *
	 * @since   1.0.0
	 */
	public function calculate_shipping( $package = array() ) {
		$this->notifier->clear_notices();

		// Only return rates if the package has a destination including country.
		if ( '' === $package['destination']['country'] ) {
			$this->debug( __( 'UPS: Country not supplied. Rates not requested.', 'woocommerce-shipping-ups' ) );

			return;
		}

		// If no origin postcode set, throw an error and stop the calculation.
		if ( ! $this->origin_postcode ) {
			$this->debug( __( 'UPS: No Origin Postcode has been set. Please edit your UPS shipping method in your shipping zone(s) and add an Origin Postcode so rates can be calculated!', 'woocommerce-shipping-ups' ) );

			return;
		}

		$this->maybe_validate_destination_address( $package['destination'] );

		if ( ! $this->is_destination_address_valid() ) {

			$this->maybe_add_invalid_destination_address_notice();

			$this->maybe_display_notices();

			return;
		}

		// Set the package parameter of the ups_api class.
		$this->ups_api->set_package( $package );

		$rates            = array();
		$package_requests = $this->get_package_requests( $package );
		if ( ! empty( $package_requests ) && is_array( $package_requests ) ) {

			// Check if any services are enabled.
			if ( empty( $this->get_enabled_services() ) ) {
				$this->debug( __( 'UPS: No Services are enabled in admin panel.', 'woocommerce-shipping-ups' ) );
			} else {
				// Set the package_requests parameter of the ups_api class.
				$this->ups_api->set_package_requests( $package_requests );

				// Get rates.
				$rates = $this->ups_api->get_rates();
			}
		}

		// Add rates.
		if ( $rates ) {

			if ( 'all' === $this->offer_rates ) {
				uasort( $rates, array( $this, 'sort_rates' ) );
				foreach ( $rates as $rate ) {
					$this->add_rate( $rate );
				}
			} else {
				$cheapest_rate = '';

				foreach ( $rates as $rate ) {
					if ( ! $cheapest_rate || $cheapest_rate['cost'] > $rate['cost'] ) {
						$cheapest_rate = $rate;
					}
				}

				$this->add_rate( $cheapest_rate );
			}
		} elseif ( $this->fallback ) {
			$this->add_rate(
				array(
					'id'    => $this->id . '_fallback',
					'label' => $this->title,
					'cost'  => $this->fallback,
					'sort'  => 0,
				)
			);
			$this->debug( __( 'UPS: Using Fallback setting.', 'woocommerce-shipping-ups' ) );
		}

		$this->maybe_display_notices();
	}

	/**
	 * Maybe validate the destination address.
	 *
	 * @param array $destination_address Destination address.
	 *
	 * @return void
	 */
	private function maybe_validate_destination_address( array $destination_address ) {

		if ( ! $this->is_destination_address_validation_enabled() ) {
			return;
		}

		if ( ! $this->should_run_address_validation() ) {
			return;
		}

		if ( ! $this->is_address_complete( $destination_address ) ) {
			return;
		}

		if ( ! $this->country_supports_address_validation( $destination_address['country'] ) ) {
			return;
		}

		$this->clear_shipping_cache();

		$this->ups_api->validate_destination_address( $destination_address );
	}

	/**
	 * Check if we should run address validation.
	 *
	 * @return bool
	 */
	public function should_run_address_validation(): bool {
		static $should_run = null;

		if ( null !== $should_run ) {
			return $should_run;
		}

		$should_run = ( is_checkout() || ( $this->is_wc_checkout_url() && $this->is_store_api_request() ) );

		return $should_run;
	}

	/**
	 * Check if this is a WC store REST API request.
	 *
	 * @return bool
	 */
	public function is_store_api_request(): bool {
		if ( ! WC()->is_rest_api_request() && empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			return false;
		}

		$rest_route = $GLOBALS['wp']->query_vars['rest_route'];

		// Use regex to check any route that has "wc/store" with any of these text : "checkout", "cart", or "batch"
		// Example : wc/store/v1/batch.
		preg_match( '/wc\/store\/v[0-9]{1,}\/(batch|cart|checkout)/', $rest_route, $route_matches, PREG_OFFSET_CAPTURE );

		return ( ! empty( $route_matches ) );
	}

	/**
	 * Is the WC checkout url the HTTP referer?
	 *
	 * @return bool
	 */
	public function is_wc_checkout_url(): bool {
		return ( isset( $_SERVER['HTTP_REFERER'] ) && wc_get_checkout_url() === $_SERVER['HTTP_REFERER'] );
	}

	/**
	 * Rates sorter.
	 *
	 * @param mixed $a A.
	 * @param mixed $b B.
	 *
	 * @return int
	 */
	public function sort_rates( $a, $b ): int {
		if ( $a['sort'] === $b['sort'] ) {
			return 0;
		}

		return ( $a['sort'] < $b['sort'] ) ? -1 : 1;
	}

	/**
	 * Get XML package request.
	 *
	 * @param array $package Package to ship.
	 *
	 * @return array See self::box_shipping and self::per_item_shipping.
	 */
	private function get_package_requests( array $package ): array {
		switch ( $this->packing_method ) {
			case 'box_packing':
				$requests = $this->box_shipping( $package );
				break;
			case 'per_item':
			default:
				$requests = $this->per_item_shipping( $package );
				break;
		}

		return $requests;
	}

	/**
	 * Build XML package request using per items packing method.
	 *
	 * @param array $package Package to ship.
	 *
	 * @return array $requests Array of XML strings.
	 * @since   1.0.0
	 * @version 3.2.5
	 */
	private function per_item_shipping( array $package ): array {
		$requests = array();

		$ctr = 0;
		foreach ( $package['contents'] as $cart_item ) {
			++$ctr;

			/**
			 * The cart item data is an instance of WC_Product.
			 *
			 * @var WC_Product $product Product instance.
			 */
			$product = $cart_item['data'];

			if ( ! $product->needs_shipping() ) {
				$this->debug(
					sprintf(
					/* translators: %d: Product index */
						__( 'Product #%d is virtual. Skipping.', 'woocommerce-shipping-ups' ),
						$ctr
					)
				);
				continue;
			}

			if ( ! $product->get_weight() ) {
				$this->debug(
					sprintf(
					/* translators: %d: Product index */
						__( 'Product #%d is missing weight. Aborting.', 'woocommerce-shipping-ups' ),
						$ctr
					),
					'error'
				);

				return $requests;
			}

			$request = $this->ups_api->build_individually_packed_package_for_rate_request( $cart_item );

			for ( $i = 0; $i < $cart_item['quantity']; $i++ ) {
				$requests[] = $request;
			}
		}

		return $requests;
	}

	/**
	 * Checks whether this instance has package service options.
	 *
	 * @param string $destination_country Country being delivered to.
	 *
	 * @return bool  True if it has package service options.
	 * @since   3.2.5
	 * @version 3.2.5
	 */
	public function has_package_service_options( string $destination_country ): bool {
		if ( $this->insuredvalue ) {
			return true;
		}

		if ( $this->needs_delivery_confirmation() && 'package' === $this->delivery_confirmation_level( $destination_country ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks whether this instance needs delivery confirmation.
	 *
	 * @return bool True if this instance needs delivery confirmation.
	 * @version 3.2.5
	 *
	 * @since   3.2.5
	 */
	public function needs_delivery_confirmation(): bool {
		return in_array( $this->signature, array( 'regular', 'adult' ), true );
	}

	/**
	 * Checks if delivery confirmation should be at the shipment or package level.
	 * See https://github.com/woocommerce/woocommerce-shipping-ups/issues/99
	 *
	 * @param string $destination_country Country being delivered to.
	 *
	 * @return string shipment or package
	 */
	public function delivery_confirmation_level( string $destination_country ): string {
		if ( 'US' === $this->origin_country ) {
			if ( in_array( $destination_country, array( 'US', 'PR' ), true ) ) {
				return 'package';
			}
		}

		if ( 'CA' === $this->origin_country && 'CA' === $destination_country ) {
			return 'package';
		}

		if ( 'PR' === $this->origin_country ) {
			if ( in_array( $destination_country, array( 'US', 'PR' ), true ) ) {
				return 'package';
			}
		}

		return 'shipment';
	}

	/**
	 * Convert dimension.
	 *
	 * @param mixed  $dimension Dimension (length, width, or height).
	 * @param string $from_unit Base unit to convert dimension from. Defaults to the store's dimension unit.
	 * @param string $to_unit   Target unit to convert dimension to. Defaults to this instance's dimension unit.
	 */
	public function get_converted_dimension( $dimension, string $from_unit = '', string $to_unit = '' ) {
		if ( empty( $to_unit ) ) {
			$to_unit = $this->dim_unit;
		}

		return wc_get_dimension( floatval( $dimension ), strtolower( $to_unit ), strtolower( $from_unit ) );
	}

	/**
	 * Convert weight.
	 *
	 * @param float|int $weight    Weight.
	 * @param string    $from_unit Weight unit to convert from. Defaults to the store's weight unit.
	 * @param string    $to_unit   Weight unit to convert to. Defaults to this instance's weight unit.
	 */
	public function get_converted_weight( $weight, string $from_unit = '', string $to_unit = '' ) {
		if ( empty( $to_unit ) ) {
			$to_unit = strtolower( $this->weight_unit );
		}

		return wc_get_weight( $weight, strtolower( $to_unit ), strtolower( $from_unit ) );
	}

	/**
	 * Get formatted measurement.
	 *
	 * @param float|int|string $measurement Measurement.
	 *
	 * @return string Formatted measurement.
	 */
	public function get_formatted_measurement( $measurement ): string {
		if ( ! is_numeric( $measurement ) ) {
			$measurement = 0;
		}

		return number_format( $measurement, 4, '.', '' );
	}

	/**
	 * Build XML package request using box packing method.
	 *
	 * @param array $package Package to ship.
	 *
	 * @return array Array of XML strings.
	 * @since   1.0.0
	 * @version 3.2.5
	 */
	private function box_shipping( array $package ): array {

		$requests = array();

		$boxpack = ( new WC_Boxpack( 'in', 'lbs', $this->box_packer_library ) )->get_packer();

		// Add standard UPS boxes, if enabled, to the Boxpacker instance.
		$this->maybe_add_ups_packaging_boxpacker_boxes( $boxpack );

		// Add custom defined boxes, if defined, to the Boxpacker instance.
		$this->maybe_add_custom_defined_boxpacker_boxes( $boxpack );

		/**
		 * Add items to the Boxpacker instance.
		 *
		 * If a product that needs shipping is missing dimensions, abort the process.
		 */
		$ctr = 0;
		foreach ( $package['contents'] as $cart_item ) {
			++$ctr;

			/**
			 * The cart item data is a product instance.
			 *
			 * @var WC_Product $product Product instance.
			 */
			$product = $cart_item['data'];

			if ( ! $product->needs_shipping() ) {
				$this->debug(
					sprintf(
					/* translators: %d: Product index */
						__( 'Product #%d is virtual. Skipping.', 'woocommerce-shipping-ups' ),
						$ctr
					)
				);
				continue;
			}

			if ( ! $this->product_can_be_packed( $product ) ) {
				$this->debug(
					sprintf(
					/* translators: %d: Product index */
						__( 'UPS Parcel Packing Method is set to Pack into Boxes. Product #%d is missing dimensions. Aborting.', 'woocommerce-shipping-ups' ),
						$ctr
					),
					'error'
				);

				return $requests;
			}

			/**
			 * The BoxPacker boxes are already using this UPS instance's weight and dimension units.
			 * We need to convert the product's weight and dimensions to the same units before adding
			 * the item to the BoxPacker instance.
			 */
			$boxpack->add_item(
				$this->get_formatted_measurement( $this->get_converted_dimension( $product->get_length() ) ),
				$this->get_formatted_measurement( $this->get_converted_dimension( $product->get_width() ) ),
				$this->get_formatted_measurement( $this->get_converted_dimension( $product->get_height() ) ),
				$this->get_formatted_measurement( $this->get_converted_weight( $product->get_weight() ) ),
				$product->get_price(),
				array(),
				$cart_item['quantity']
			);
		}

		/**
		 * Allow boxpack to be overriden by devs.
		 *
		 * @var Abstract_Packer $boxpack
		 * @since 3.2.25
		 */
		$boxpack = apply_filters( 'woocommerce_shipping_ups_boxpack_before_pack', $boxpack );

		// Pack it.
		$boxpack->pack();

		// Get boxes packed by the Box Packer.
		$packed_boxes       = $boxpack->get_packages();
		$packed_boxes_count = count( $packed_boxes );

		$ctr = 0;
		foreach ( $packed_boxes as $packed_box ) {
			++$ctr;

			$this->debug( 'PACKAGE ' . $ctr, 'notice', (array) $packed_box );

			$requests[] = $this->ups_api->build_packed_box_package_for_rate_request( $packed_box, $packed_boxes_count );
		}

		return $requests;
	}

	/**
	 * Check if the product has dimensions and weight set.
	 * This is used to determine if the product can be packed into a box.
	 *
	 * @param WC_Product $product Product instance.
	 *
	 * @return bool
	 */
	public function product_can_be_packed( WC_Product $product ): bool {
		return (float) $product->get_length() && (float) $product->get_width() && (float) $product->get_height() && (float) $product->get_weight();
	}

	/**
	 * Get the UPS customer classifications.
	 *
	 * @return array
	 */
	public function get_customer_classifications(): array {
		return array(
			''   => __( 'Rate chart from shipper\'s country.', 'woocommerce-shipping-ups' ),
			'00' => __( 'Rates associated with shipper number', 'woocommerce-shipping-ups' ),
			'01' => __( 'Daily rates', 'woocommerce-shipping-ups' ),
			'04' => __( 'Retail rates', 'woocommerce-shipping-ups' ),
			'05' => __( 'Regional rates', 'woocommerce-shipping-ups' ),
			'06' => __( 'General list rates', 'woocommerce-shipping-ups' ),
			'07' => __( 'Alternative Zoning', 'woocommerce-shipping-ups' ),
			'08' => __( 'General List Rates II', 'woocommerce-shipping-ups' ),
			'09' => __( 'SMB Loyalty', 'woocommerce-shipping-ups' ),
			'10' => __( 'All Inclusive', 'woocommerce-shipping-ups' ),
			'11' => __( 'Value Bundle I', 'woocommerce-shipping-ups' ),
			'53' => __( 'Standard list rates', 'woocommerce-shipping-ups' ),
		);
	}

	/**
	 * Returns the corresponding simple rate code if the package qualifies, otherwise returns false.
	 *
	 * @param int   $length Package length in inches.
	 * @param int   $width  Package width in inches.
	 * @param int   $height Package height in inches.
	 * @param float $weight Package weight in pounds.
	 *
	 * @return false|string The simple rate code or false
	 */
	public function maybe_get_simple_rate_code( int $length, int $width, int $height, float $weight ) {
		// The max weight for Simple Rate is 50 pounds and the minimum for length, width, and height is 1 inch.
		if ( $weight > floatval( $this->simple_rate_packaging['XL']['weight'] ) || $length < 1 || $width < 1 || $height < 1 ) {
			return false;
		}

		$cubic_inches = $length * $width * $height;

		foreach ( $this->simple_rate_packaging as $code => $data ) {
			if ( $cubic_inches >= $data['cubic_inches_min'] && $cubic_inches <= $data['cubic_inches_max'] ) {
				return $code;
			}
		}

		return false;
	}

	/**
	 * Get enabled services.
	 *
	 * @return array
	 */
	public function get_enabled_services(): array {
		$custom_services  = $this->get_custom_services();
		$enabled_services = array();

		if ( empty( $custom_services ) || ! is_array( $custom_services ) ) {
			return $enabled_services;
		}

		foreach ( $this->custom_services as $code => $values ) {
			if ( ! empty( $values['enabled'] ) ) {
				$enabled_services[ $code ] = $values;
			}
		}

		return $enabled_services;
	}

	/**
	 * Get enabled service codes.
	 *
	 * @return array|string[]
	 */
	public function get_enabled_service_codes(): array {
		$enabled_services = $this->get_enabled_services();

		if ( empty( $enabled_services ) ) {
			return array();
		}

		$enabled_service_codes = array();
		foreach ( $enabled_services as $code => $values ) {
			// Cast to string for strict comparison.
			$enabled_service_codes[] = (string) $code;
		}

		return $enabled_service_codes;
	}

	/**
	 * Check if at least one Simple Rate compatible service is enabled in the settings
	 *
	 * @return bool
	 */
	public function simple_rate_services_enabled(): bool {
		foreach ( $this->simple_rate_services as $service_code ) {
			/**
			 * UPS 3 Day Select and UPS Next Day Air Saver are not available for
			 * UPS Simple Rate shipments destined to Alaska and Hawaii. So skip
			 * those services for those destinations.
			 */
			if ( in_array( WC()->customer->get_shipping_state(), array( 'HI', 'AK' ), true ) && in_array( $service_code, array( '12', '13' ), true ) ) {
				continue;
			}

			if ( ! empty( $this->custom_services[ $service_code ]['enabled'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get SurePost service codes.
	 *
	 * @return array
	 */
	public function get_surepost_service_codes(): array {
		return $this->surepost_service_codes;
	}

	/**
	 * Returns the enabled SurePost service codes.
	 *
	 * @return array
	 */
	public function get_enabled_surepost_service_codes(): array {
		return array_intersect( $this->get_surepost_service_codes(), $this->get_enabled_service_codes() );
	}

	/**
	 * Check if we are shipping within the United States
	 *
	 * @return bool
	 */
	public function is_domestic_us_shipping(): bool {
		return ( 'US' === WC()->customer->get_shipping_country() && 'US' === $this->origin_country );
	}

	/**
	 * Get the UPS account user ID.
	 *
	 * @return mixed
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Get the UPS account password.
	 *
	 * @return mixed
	 */
	public function get_password() {
		return $this->password;
	}

	/**
	 * Get the UPS account access key.
	 *
	 * @return mixed
	 */
	public function get_access_key() {
		return $this->access_key;
	}

	/**
	 * Get the UPS account shipper number.
	 *
	 * @return mixed
	 */
	public function get_shipper_number() {
		return $this->shipper_number;
	}

	/**
	 * Get the UPS customer classification code.
	 *
	 * @return mixed
	 */
	public function get_customer_classification_code() {
		return $this->classification_code;
	}

	/**
	 * Get the selected UPS API type.
	 *
	 * @return string
	 */
	public function get_api_type(): string {
		return $this->api_type;
	}

	/**
	 * Get the REST API client ID.
	 *
	 * @return string
	 */
	public function get_client_id(): string {
		return $this->client_id;
	}

	/**
	 * Get the REST API client secret.
	 *
	 * @return string
	 */
	public function get_client_secret(): string {
		return $this->client_secret;
	}

	/**
	 * Get the UPS OAuth instance.
	 *
	 * @return REST_API_OAuth
	 */
	public function get_ups_oauth(): REST_API_OAuth {
		return $this->ups_oauth;
	}

	/**
	 * Is UPS Simple Rate enabled?
	 *
	 * @return bool
	 */
	public function is_simple_rate_enabled(): bool {
		return $this->simple_rate;
	}

	/**
	 * Is negotiated rates enabled?
	 *
	 * @return bool
	 */
	public function is_negotiated_rates_enabled(): bool {
		return $this->negotiated;
	}

	/**
	 * Get the dimension unit.
	 *
	 * @return string
	 */
	public function get_dimension_unit(): string {
		return $this->dim_unit;
	}

	/**
	 * Get the weight unit.
	 *
	 * @return string
	 */
	public function get_weight_unit(): string {
		return $this->weight_unit;
	}

	/**
	 * Should we flag the destination address as residential?
	 *
	 * @return bool
	 */
	public function is_residential(): bool {
		return $this->residential;
	}

	/**
	 * Get the service code <> name mappings.
	 *
	 * @return array|string[]
	 */
	public function get_services(): array {
		return $this->services;
	}

	/**
	 * Get the UPS services.
	 *
	 * @return mixed
	 */
	public function get_custom_services() {
		return $this->custom_services;
	}

	/**
	 * Should the shipment be insured?
	 *
	 * @return bool
	 */
	public function is_insured_value_enabled(): bool {
		return $this->insuredvalue;
	}

	/**
	 * Whether to require a signature or not and if so, what type.
	 *
	 * @return mixed
	 */
	public function get_signature() {
		return $this->signature;
	}

	/**
	 * Get the origin address line.
	 *
	 * @return string
	 */
	public function get_origin_addressline(): string {
		return $this->origin_addressline;
	}

	/**
	 * Get the origin city.
	 *
	 * @return string
	 */
	public function get_origin_city(): string {
		return $this->origin_city;
	}

	/**
	 * Get the origin state.
	 *
	 * @return string
	 */
	public function get_origin_state(): string {
		return $this->origin_state;
	}

	/**
	 * Get the origin country.
	 *
	 * @return string
	 */
	public function get_origin_country(): string {
		return $this->origin_country;
	}

	/**
	 * Get the origin postcode.
	 *
	 * @return string
	 */
	public function get_origin_postcode(): string {
		return $this->origin_postcode;
	}

	/**
	 * Is debug mode enabled?
	 *
	 * @return bool
	 */
	public function is_debug_mode_enabled(): bool {
		return $this->debug;
	}

	/**
	 * Is destination address validation enabled?
	 *
	 * @return bool
	 */
	public function is_destination_address_validation_enabled(): bool {
		return $this->destination_address_validation;
	}

	/**
	 * Does the passed country support address validation?
	 *
	 * @param string $country The country code.
	 *
	 * @return bool
	 */
	public function country_supports_address_validation( $country ): bool {
		return in_array( $country, array( 'US', 'PR' ), true );
	}

	/**
	 * Set whether the destination address is valid.
	 *
	 * @param bool $is_valid Is the destination address valid.
	 *
	 * @return void
	 */
	public function set_is_valid_destination_address( bool $is_valid ) {
		$this->is_valid_destination_address = $is_valid;
	}

	/**
	 * Is the destination address valid?
	 *
	 * @return bool
	 */
	public function is_destination_address_valid(): bool {
		return $this->is_valid_destination_address;
	}

	/**
	 * Add an invalid address notice if the right conditions are met.
	 *
	 * @return void
	 */
	private function maybe_add_invalid_destination_address_notice() {

		/**
		 * If this is the classic checkout page and $_POST is empty, then we don't want to add the notice.
		 *
		 * The reason is because of this line in WC Core which will show the cart error template instead
		 * of allowing the user to proceed to checkout:
		 * wp-content/plugins/woocommerce/includes/shortcodes/class-wc-shortcode-checkout.php:346
		 *
		 * The nonce check is performed in the WC_Shortcode_Cart::output() method and WC_AJAX::update_order_review(), so
		 *  we don't need to check it here. Also, we are only checking if the $_POST variable is empty.
		 */
		if ( empty( $_POST ) && $this->is_classic_checkout_page() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$this->add_invalid_destination_address_notice();
	}

	/**
	 * Check if the address is complete.
	 *
	 * @param array $address The address to check.
	 *
	 * @return bool
	 */
	public function is_address_complete( array $address ): bool {
		$required_address_keys = array(
			'address_1',
			'city',
			'state',
			'postcode',
			'country',
		);

		foreach ( $required_address_keys as $key ) {
			if ( empty( $address[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * If enabled, add UPS standard packaging box sizes to the boxpacker instance.
	 *
	 * @param Abstract_Packer $boxpack The boxpacker instance.
	 *
	 * @return void
	 */
	public function maybe_add_ups_packaging_boxpacker_boxes( Abstract_Packer $boxpack ) {

		if ( empty( $this->ups_packaging ) || ! is_array( $this->ups_packaging ) ) {
			return;
		}

		$dimension_parts = array(
			'length',
			'width',
			'height',
		);

		foreach ( $this->ups_packaging as $box_code ) {
			$packaging = $this->packaging[ $box_code ];

			// Check if all dimensions are set. If not, skip this box.
			$dimensions = array();
			foreach ( $dimension_parts as $dimension_part ) {
				if ( ! isset( $packaging[ $dimension_part ] ) ) {
					continue 2;
				}

				// Convert the dimension to the UPS instance dimention unit, as the UPS packaging is defined in inches.
				$dimensions[ $dimension_part ] = $this->get_converted_dimension( $packaging[ $dimension_part ], 'in' );
			}

			// Create a new box.
			$box = $boxpack->add_box( $dimensions['length'], $dimensions['width'], $dimensions['height'] );

			// Set the inner dimensions.
			$box->set_inner_dimensions( $dimensions['length'], $dimensions['width'], $dimensions['height'] );

			// Set the box id, if available.
			if ( $packaging['name'] ) {
				$box->set_id( $packaging['name'] );
			}

			// Set the box max weight, if available.
			if ( $packaging['weight'] ) {
				// Convert the weight to the UPS instance weight unit, as the UPS packaging is defined in pounds.
				$box->set_max_weight( $this->get_converted_weight( $packaging['weight'], 'lbs' ) );
			}
		}
	}

	/**
	 * If any exist, add custom defined box sizes to the boxpacker instance.
	 *
	 * @param Abstract_Packer $boxpack The boxpacker instance.
	 *
	 * @return void
	 */
	public function maybe_add_custom_defined_boxpacker_boxes( Abstract_Packer $boxpack ) {

		if ( empty( $this->boxes ) || ! is_array( $this->boxes ) ) {
			return;
		}

		$dimension_parts = array(
			'outer_length',
			'outer_width',
			'outer_height',
			'inner_length',
			'inner_width',
			'inner_height',
		);

		foreach ( $this->boxes as $custom_box ) {

			// Check if all dimensions are set. If not, skip this box.
			$dimensions = array();
			foreach ( $dimension_parts as $dimension_part ) {
				if ( ! isset( $custom_box[ $dimension_part ] ) ) {
					continue 2;
				}

				$dimensions[ $dimension_part ] = $custom_box[ $dimension_part ];
			}

			// Get the box weight.
			$box_weight = $custom_box['box_weight'] ?? 0;

			// Create a new box.
			$box = $boxpack->add_box( $dimensions['outer_length'], $dimensions['outer_width'], $dimensions['outer_height'], $box_weight );

			// Set the inner dimensions.
			$box->set_inner_dimensions( $dimensions['inner_length'], $dimensions['inner_width'], $dimensions['inner_height'] );

			// Set the box id, if available.
			$box->set_id( $dimensions['outer_length'] . 'x' . $dimensions['outer_width'] . 'x' . $dimensions['outer_height'] );

			// Set the box max weight, if available.
			if ( $custom_box['max_weight'] ) {
				$box->set_max_weight( $custom_box['max_weight'] );
			}
		}
	}

	/**
	 * Add a WooCommerce notice to let the user know the destination address is invalid.
	 *
	 * @return void
	 */
	private function add_invalid_destination_address_notice() {
		$notice_message = $this->get_invalid_destination_address_notice_message();

		$this->add_customer_notice( $notice_message, 'error', array(), $this->ups_api->get_address_validator()::$notice_group );
	}

	/**
	 * Get the invalid destination address notice message.
	 *
	 * @return string
	 */
	public function get_invalid_destination_address_notice_message(): string {
		$suggested_address = $this->ups_api->get_address_validator()->get_first_suggested_address();

		if ( is_array( $suggested_address ) && ! empty( $suggested_address ) ) {

			// Save full address to JSON string for use in the apply suggested address button.
			$suggested_address_json = wp_json_encode( $suggested_address );

			// Remove empty values.
			$filtered_suggested_address = array_filter( $suggested_address );

			$address_markup = '<strong>' . esc_html( implode( ', ', $filtered_suggested_address ) ) . '</strong>';

			$button_markup = '<button class="button ups_apply_suggested_address" data-suggested_address="' . esc_attr( $suggested_address_json ) . '">' . esc_html__( 'Apply suggested address', 'woocommerce-shipping-ups' ) . '</button>';

			$notice_message = sprintf(
			/* translators: 1: suggested address */
				__( 'UPS: Cannot validate the entered shipping address. Rates not requested. Did you mean %1$s? %2$s', 'woocommerce-shipping-ups' ),
				$address_markup,
				$button_markup
			);
		} else {
			$notice_message = esc_html__( 'UPS: Cannot validate the entered shipping address. Rates not requested.', 'woocommerce-shipping-ups' );
		}

		return $notice_message;
	}

	/**
	 * Maybe display the XML API deprecation notice and disable the XML API settings.
	 *
	 * @return void
	 */
	private function maybe_disable_xml_api() {
		if ( 'xml' === $this->get_api_type() && $this->is_xml_api_configured() ) {
			$this->show_xml_api_deprecated_notice();
		} else {
			$this->api_type = 'rest';

			if ( ! $this->is_ups_settings_page() ) {
				return;
			}

			$this->update_option( 'api_type', 'rest' );

			$this->form_fields['api_type']['type']   = 'hidden';
			$this->form_fields['user_id']['type']    = 'hidden';
			$this->form_fields['password']['type']   = 'hidden';
			$this->form_fields['access_key']['type'] = 'hidden';
		}
	}

	/**
	 * Check if the XML API is configured.
	 *
	 * @return bool
	 */
	public function is_xml_api_configured(): bool {
		return $this->get_user_id() && $this->get_password() && $this->get_access_key();
	}

	/**
	 * Show the XML API deprecation notice.
	 *
	 * @return void
	 */
	public function show_xml_api_deprecated_notice() {
		static $has_shown_notification = false;

		if ( $has_shown_notification ) {
			return;
		}

		add_action(
			'admin_notices',
			function () {
				echo '<div class="error notice"><p>' .
					sprintf(
					// translators: %1$s a link opening tag, %2$s link closing tag.
						esc_html__( 'NOTICE! Effective June 3rd, 2024, the UPS XML API will be deprecated. Immediate action is required to transition to the REST API for uninterrupted service. To learn how to upgrade your integration from the XML API to the REST API, please visit %1$sWoo UPS Shipping Method documentation%2$s.', 'woocommerce-shipping-ups' ),
						'<a href="https://woocommerce.com/document/ups-shipping-method/#section-3" target="_blank">',
						'</a>'
					) . '</p></div>';
			}
		);

		$has_shown_notification = true;
	}

	/**
	 * Check if the user is on the UPS settings page.
	 *
	 * @return bool
	 */
	public function is_ups_settings_page(): bool {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended --- security handled by WooCommerce
		return isset( $_GET['page'], $_GET['section'] ) && 'wc-settings' === $_GET['page'] && 'ups' === $_GET['section'];
	}

	/**
	 * Helper method to get the number of usps method instances.
	 *
	 * @return int The number of usps method instances
	 */
	public function instance_count(): int {
		global $wpdb;

		// phpcs:ignore --- Need to use WPDB::get_var() to count the existing USPS in the shipping zone
		return absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id = 'ups'" ) );
	}

	/**
	 * Helper method to check if there are existing usps method instances.
	 *
	 * @return bool
	 */
	public function instances_exist(): bool {
		return $this->instance_count() > 0;
	}
}
