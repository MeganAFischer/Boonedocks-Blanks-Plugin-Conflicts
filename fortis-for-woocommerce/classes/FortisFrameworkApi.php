<?php
/**
 * Fortis API implementation for WC_Payment_Gateway
 *
 * @package Fortis for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Fortis Payment Gateway - Fortispay3
 *
 * Supports a Fortis Fortispay3 Payment Gateway.
 *
 * @class       woocommerce_fortis
 * @package     WooCommerce
 */
class FortisframeworkApi extends WC_Payment_Gateway {
	public const DEVELOPER_ID_SANDBOX    = 'woo24107';
	public const DEVELOPER_ID_PRODUCTION = 'woo25112';

	/**
	 * Gateway id
	 *
	 * @var string Gateway id.
	 */
	public $id;

	/**
	 * Constructor
	 *
	 * @param string $id Fortis.
	 */
	public function __construct( $id ) {
		$this->id = $id;
		$this->init_settings();
	}

	/**
	 * Get fortis level3 enabled setting
	 *
	 * @return bool
	 */
	public function get_level3_enabled() {
		return 'yes' === ( $this->settings[ FortisApi::LEVEL3 ] ?? '' );
	}


	/**
	 * Get fortis setting for ach enabled
	 *
	 * @return bool
	 */
	public function get_ach_enabled() {
		return 'yes' === ( $this->settings[ FortisApi::ACH ] ?? '' );
	}

	/**
	 * Get fortis setting for cc enabled
	 *
	 * @return bool
	 */
	public function get_cc_enabled() {
		return 'yes' === ( $this->settings[ FortisApi::CC ] ?? '' );
	}


	/**
	 * Get fortis environment setting
	 *
	 * @return string
	 */
	public function get_environment() {
		return $this->settings[ FortisApi::ENVIRONMENT ] ?? '';
	}

	/**
	 * Get fortis vault enabled setting
	 *
	 * @return bool
	 */
	public function vault_enabled() {
		return 'yes' === ( $this->settings[ FortisApi::TOKENIZATION ] ?? '' );
	}


	/**
	 * Get WC Payment Token from Id
	 *
	 * @param int $token_id Token Id.
	 *
	 * @return string|null
	 */
	public function get_token_by_id( $token_id ) {
		$token_detail = WC_Payment_Tokens::get( $token_id );
		if ( $token_detail ) {
			$token = $token_detail->get_data();

			return $token['token'];
		} else {
			return null;
		}
	}

	/**
	 * Store card detail in Payment Token Store
	 *
	 * @param string $token_id Token Id.
	 * @param mixed  $saved_account Saved account.
	 * @param int    $customer_id Customer Id.
	 *
	 * @return void
	 */
	public function vault_card( $token_id, $saved_account, $customer_id ) {
		$fortis_api = new FortisApi( 'fortis' );
		$token_ds   = new WC_Payment_Token_Data_Store();
		$token      = $token_ds->get_users_default_token( $customer_id );
		if ( $token ) {
			$tokens = WC_Payment_Tokens::get_customer_tokens( $customer_id, $this->id );
			if ( $tokens ) {
				foreach ( $tokens as $token ) {
					if (
						$token->get_meta( 'first6' ) === $saved_account->first_six || $token->get_meta(
							'last4'
						) === $saved_account->last_four
					) {
						$token_delete_id = $token->get_id();
						$fortis_api->token_cc_delete( $token->get_token() );
						WC_Payment_Tokens::delete( $token_delete_id );
					}
				}
			}
		}

		$token = new WC_Payment_Token_CC();

		$token->set_token( $token_id );
		$token->add_meta_data( 'first6', $saved_account->first_six, true );
		$token->add_meta_data( 'account_holder_name', $saved_account->account_holder_name, true );
		$token->set_gateway_id( $this->id );
		$token->set_card_type( strtolower( $saved_account->account_type ) );
		$token->set_last4( $saved_account->last_four );
		if ( isset( $saved_account->exp_date ) ) {
			$token->set_expiry_month( substr( $saved_account->exp_date, 0, 2 ) );
			$token->set_expiry_year( '20' . substr( $saved_account->exp_date, 2, 2 ) );
		} else {
			$token->set_expiry_month( gmdate( 'm', strtotime( ' + 30 days' ) ) );
			$token->set_expiry_year( gmdate( 'Y', strtotime( ' + 30 days' ) ) );
		}
		$token->set_user_id( $customer_id );
		$token->set_default( true );

		$token->save();
	}

	/**
	 * Get fortis action sale or auth-only from settings
	 *
	 * @return string $action
	 */
	public function get_action() {
		return $this->settings[ FortisApi::TRANSACTION_TYPE ] ?? '';
	}

	/**
	 * Get User Fortis ID based on the currently configured environment
	 *
	 * @return string $userId
	 */
	public function get_user_id() {
		$environment = $this->get_environment();
		$name        = 'user_id';
		$value       = $this->settings[ $environment . '_' . $name ] ?? '';

		if ( ! isset( $value ) || ! in_array( strlen( trim( $value ) ), array( 24, 36 ), true ) ) {
			// Must be 24 or 36 characters in length.
			return '';
		}

		return $value;
	}

	/**
	 * Get Fortis User API Key based on the currently configured environment
	 *
	 * @return string $userApiKey
	 */
	public function get_user_api_key() {
		$environment = $this->get_environment();
		$name        = 'user_api_key';
		$value       = $this->settings[ $environment . '_' . $name ] ?? '';

		if ( ! isset( $value ) || ! in_array( strlen( trim( $value ) ), array( 24, 36 ), true ) ) {
			// Must be 24 or 36 characters in length.
			return '';
		}

		return $value;
	}

	/**
	 * Get Fortis User Product ID based on the currently configured environment
	 *
	 * @return string $productId
	 */
	public function get_product_id_cc() {
		$environment = $this->get_environment();
		$name        = 'product_id_cc';
		$value       = $this->settings[ $environment . '_' . $name ];

		if ( ! isset( $value ) || ! in_array( strlen( trim( $value ) ), array( 24, 36 ), true ) ) {
			// Must be 24 or 36 characters in length.
			return '';
		}

		return $value;
	}

	/**
	 * Get Fortis User Product ID based on the currently configured environmet
	 *
	 * @return string $productId
	 */
	public function get_product_id_ach() {
		$environment = $this->get_environment();
		$name        = 'product_id_ach';
		$value       = $this->settings[ $environment . '_' . $name ] ?? '';

		if ( ! isset( $value ) || ! in_array( strlen( trim( $value ) ), array( 24, 36 ), true ) ) {
			// Must be 24 or 36 characters in length.
			return '';
		}

		return $value;
	}

	/**
	 * Get Fortis User Product ID based on the currently configured environmet
	 *
	 * @return string $locationId
	 */
	public function get_location_id() {
		$environment = $this->get_environment();
		$name        = 'location_id';
		$value       = $this->settings[ $environment . '_' . $name ] ?? '';

		if ( ! isset( $value ) || ! in_array( strlen( trim( $value ) ), array( 24, 36 ), true ) ) {
			// Must be 24 or 36 characters in length.
			return '';
		}

		return $value;
	}

	/**
	 * Get fortis setting for Apple pay enabled
	 *
	 * @return bool
	 */
	public function get_applepay_enabled() {
		return 'yes' === ( $this->settings[ FortisApi::APPLEPAY ] ?? '' );
	}

	/**
	 * Get fortis setting for Google pay enabled
	 *
	 * @return bool
	 */
	public function get_googlepay_enabled() {
		return 'yes' === ( $this->settings[ FortisApi::GOOGLEPAY ] ?? '' );
	}

	/**
	 * Get fortis setting for Google/Apple pay merchant origin
	 *
	 * @return string
	 */

	/**
	 * Get Fortis enabled flag
	 *
	 * @return string $getEnabled
	 */
	public function get_enabled() {
		$name = 'enabled';

		return $this->settings[ $name ] ?? '';
	}


	/**
	 * Log an error condition
	 *
	 * @param string $title Error title.
	 * @param mixed  $data Error information.
	 *
	 * @return void
	 */
	public static function log_error( $title, $data ) {
		// TODO - implement this?
	}

	/**
	 * Log an info condition
	 *
	 * @param string $title Info title.
	 * @param mixed  $data Info information.
	 *
	 * @return void
	 */
	public static function log_info( $title, $data ) {
		// TODO - implement this?
	}

	/**
	 * Get the detail for an exception
	 *
	 * @param \Exception $exception Exception.
	 *
	 * @return string
	 */
	public static function get_exception_details( $exception ) {
		return $exception->getMessage() . ' in ' . $exception->getFile() . ' at line ' . $exception->getLine();
	}
}
