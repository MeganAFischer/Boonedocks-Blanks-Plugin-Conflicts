<?php
/**
 * Plugin extension of WC_Payment_Gateway
 *
 * @package Fortis for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

require_once 'WC_Gateway_Fortis_Admin_Actions.php';
require_once 'FortisApi.php';

/**
 * Fortis Payment Gateway - Fortispay3
 *
 * Provides a Fortis Fortispay3 Payment Gateway.
 *
 * @class       woocommerce_fortis
 * @package     WooCommerce
 */
class WC_Gateway_Fortis extends WC_Payment_Gateway {


	public const WC_CLASS   = 'class';
	public const PROCESSING = 'processing';

	public const ID = 'fortis';

	public const TITLE       = 'title';
	public const DESCRIPTION = 'description';
	public const BUTTON_TEXT = 'button_text';
	public const COMPLETED   = 'completed';
	public const FAILED      = 'failed';
	public const PENDING     = 'pending';
	public const REFUNDED    = 'refunded';
	public const ONHOLD      = 'on-hold';
	public const LOGGING     = 'logging';
	public const ERROR       = 'error';

	public const MESSAGE = 'message';

	public const ORDER_META_REFERENCE = 'order_meta_reference';
	public const ENABLE               = 'enabled';
	/**
	 * WC Logger
	 *
	 * @var WC_Logger
	 */
	public static $wc_logger;
	/**
	 * Class that executes API calls to Fortis
	 *
	 * @var \FortisApi
	 */
	public $fortis_api;
	/**
	 * General message
	 *
	 * @var mixed
	 */
	public $msg;
	/**
	 * Plugin url
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public $version = '1.0.6';
	/**
	 * Plugin id
	 *
	 * @var string
	 */
	public $id = 'fortis';
	/**
	 * Enable logging
	 *
	 * @var bool
	 */
	public $logging = false;
	/**
	 * Redirect url
	 *
	 * @var string
	 */
	protected $redirect_url;
	/**
	 * ACH webhook url
	 *
	 * @var string
	 */
	protected $ach_notify_url;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->fortis_api = new FortisApi( 'fortis' );

		// Load the settings.
		$this->init_form_fields();
		$this->supports = array(
			'products',
			'refunds',
		);

		$this->init_settings();
		if ( 'sandbox' === $this->settings[ FortisApi::ENVIRONMENT ] &&
			isset( $this->settings[ FortisApi::ENVIRONMENT ] ) ) {
			$this->form_fields = WC_Gateway_Fortis_Admin_Actions::add_testmode_admin_settings_notice(
				$this->form_fields
			);
		}

		if ( null === self::$wc_logger ) {
			self::$wc_logger = wc_get_logger();
		}

		if ( isset( $this->settings[ self::LOGGING ] ) && 'yes' === $this->settings[ self::LOGGING ] ) {
			$this->logging = true;
		}

		$this->method_title       = __( 'Fortis', 'fortis-for-woocommerce' );
		$this->method_description = __(
			'Works by sending the customer to Fortis to complete their payment.',
			'fortis-for-woocommerce'
		);
		$this->icon               = $this->get_plugin_url() . '/assets/images/CreditCard.png';
		$this->has_fields         = true;

		// Define user set variables.
		$this->title             = isset( $this->settings[ self::TITLE ] ) ? $this->settings['title'] : '';
		$this->order_button_text = $this->settings[ self::BUTTON_TEXT ] ?? '';
		$this->description       = $this->settings[ self::DESCRIPTION ] ?? '';
		$tokenization            = $this->settings[ FortisApi::TOKENIZATION ] ?? '';

		if ( 'yes' === $tokenization ) {
			$this->supports[] = 'tokenization';
		}

		$this->msg[ self::MESSAGE ]  = '';
		$this->msg[ self::WC_CLASS ] = '';

		$this->redirect_url   = add_query_arg( 'wc-api', 'WC_Gateway_Fortis_Redirect', home_url( '/' ) );
		$this->ach_notify_url = add_query_arg( 'wc-api', 'WC_Gateway_Fortis_Notify', home_url( '/' ) );

		$this->addActions();
	}

	/**
	 * Display messages on cart page
	 *
	 * @return void
	 */
	public static function show_cart_messages() {
		$nonce  = $_REQUEST['_wpnonce'] ?? '';
		$verify = wp_verify_nonce( $nonce, 'show_cart_messages' );
		if ( $verify ) {
			http_response_code( 417 );
			exit();
		}
		if ( isset( $_GET['order_id'] ) ) {
			$order_id       = filter_var( wp_unslash( $_GET['order_id'] ), FILTER_SANITIZE_NUMBER_INT );
			$order          = wc_get_order( $order_id );
			$order_messages = $order->get_meta( 'fortis_error' );
			$order_message  = is_array( $order_messages ) ? $order_messages[ count(
				$order_messages
			) - 1 ] : $order_messages;

			$allowed_tags = array(
				'h3' => array(
					'style' => array(),
				),
			);

			echo wp_kses( '<h3 style="color: red;">' . esc_html( $order_message ) . '</h3>', $allowed_tags );
		}
	}

	/**
	 * Get order notes for order
	 *
	 * @param int $order_id Order id.
	 *
	 * @return array|object|null
	 */
	protected static function getOrderNotes( $order_id ) {
		global $wpdb;

		// Define a unique cache key based on the order ID
		$cache_key = 'order_notes_' . $order_id;

		$order_notes = wp_cache_get( $cache_key );

		if ( $order_notes === false ) {
			$order_notes = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT comment_content FROM {$wpdb->comments} WHERE comment_post_ID = %d
AND comment_type = 'order_note'",
					$order_id
				)
			);

			wp_cache_set( $cache_key, $order_notes );
		}

		return $order_notes;
	}

	/**
	 * Processes redirect from pay portal
	 *
	 * @throws \WC_Data_Exception WC_Data_Exception.
	 */
	public function check_fortis_response() {
		$nonce  = $_REQUEST['_wpnonce'] ?? '';
		$action = 'check_fortis_response';
		$verify = wp_verify_nonce( $nonce, $action );
		if ( $verify ) {
			http_response_code( 417 );
			exit();
		}
		$sanitized_post_data = array_map( 'sanitize_text_field', $_POST );
		if ( $this->logging ) {
			self::$wc_logger->add(
				'fortisfortispay',
				'Redirect POST: ' . wp_json_encode( $sanitized_post_data )
			);
		}

		$status             = '';
		$wcsession          = WC()->session;
		$order_id           = $wcsession->get( 'order_id' );
		$transaction_api_id = $wcsession->get( 'transaction_api_id' );
		$order              = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$customer_id = $order->get_customer_id();

		global $woocommerce;
		$transaction_amount = $woocommerce->cart->total * 100;
		$tax_amount         = $woocommerce->cart->tax_total * 100;

		$is_blocks = true === $wcsession->get( 'isBlocks' );

		if ( $wcsession->get( 'post' ) ) {
			$blockpost = $wcsession->get( 'post' );
			if (
				$is_blocks
				&& '' !== $order->get_payment_method()
				&& isset( $blockpost['issavedtoken'] )
				&& '1' === $blockpost['issavedtoken']
				&& null !== $blockpost['wc-fortis-payment-token']
			) {
				$token_id = $blockpost['wc-fortis-payment-token'];
				$token    = WC_Payment_Tokens::get( $token_id );
				if ( 'checking' === $token->get_meta( 'card_type' ) || 'savings' === $token->get_meta( 'card_type' ) ) {
					$this->fortis_api->payment_method = 'ach';
				} else {
					$this->fortis_api->payment_method = 'token';
				}
				$status = $this->fortis_api->do_tokenised_transaction(
					$transaction_amount,
					$token_id,
					$tax_amount,
					$order_id
				);
				if ( 1 === (int) $status && 'ach' === $this->fortis_api->payment_method ) {
					$status = 5;
				}
			} elseif ( isset( $sanitized_post_data['fortis_useSavedAccount'] ) &&
						'on' === $sanitized_post_data['fortis_useSavedAccount'] ) {
				$token_id = $sanitized_post_data['CC'];
				$token    = WC_Payment_Tokens::get( $token_id );
				if ( 'checking' === $token->get_meta( 'card_type' ) ||
					'savings' === $token->get_meta( 'card_type' ) ) {
					$this->fortis_api->payment_method = 'ach';
				} else {
					$this->fortis_api->payment_method = 'token';
				}
				$status = $this->fortis_api->do_tokenised_transaction(
					$transaction_amount,
					$token_id,
					$tax_amount,
					$order_id
				);
				if ( 1 === (int) $status && 'ach' === $this->fortis_api->payment_method ) {
					$status = 5;
				}
			}
		}

		if ( empty( $this->fortis_api->payment_method ) ) {
			$this->fortis_api->payment_method = '';
		}
		if ( 'ach' !== $this->fortis_api->payment_method && 'token' !== $this->fortis_api->payment_method ) {
			$status = $this->fortis_api->process_transaction(
				$sanitized_post_data,
				$transaction_amount,
				$customer_id,
				$tax_amount,
				$order_id
			);
		}

		if ( 'ach' === $this->fortis_api->payment_method ) {
			$this->fortis_api->create_ach_postback( $this->ach_notify_url );
		}

		if ( 1 === $status && $this->fortis_api->framework->get_level3_enabled()
			&& 'ach' !== $this->fortis_api->payment_method ) {
			$cart       = WC()->cart;
			$line_items = array();
			foreach ( $cart->cart_contents as $cart_item ) {
				$commodity_code = '';
				if ( ! empty( $cart_item['data']->get_data()['attributes']['commodity_code'] ) ) {
					$commodity_code = $cart_item['data']->get_data()['attributes']['commodity_code']['data']['value'];
				}
				$unit_code = '';
				if ( ! empty( $cart_item['data']->get_data()['attributes']['unit_code'] ) ) {
					$unit_code = $cart_item['data']->get_data()['attributes']['unit_code']['data']['value'];
				}
				$line_items[] = array(
					'description'    => $cart_item['data']->get_data()['name'],
					'product_code'   => $cart_item['data']->get_data()['id'],
					'commodity_code' => $commodity_code,
					'quantity'       => $cart_item['quantity'],
					'unit_code'      => $unit_code,
					'unit_cost'      => $cart_item['data']->get_data()['price'],
				);
			}
			$result = $this->fortis_api->add_level3( $line_items );
			$order->add_order_note( $result );
		}

		$transaction_id = $this->fortis_api->transaction->id;

		$order->update_meta_data( 'action', $this->fortis_api->action );
		$order->update_meta_data( 'transaction_id', $transaction_id );
		$order->update_meta_data( 'payment_method', $this->fortis_api->payment_method );
		$order->update_meta_data( 'transaction_api_id', $transaction_api_id );

		$order_id = null;
		$order->set_transaction_id( $transaction_id );
		$this->processOrderFinal( $status, $order, $transaction_id, '' );
	}


	/**
	 * Get the plugin URL
	 *
	 * @since 1.0.0
	 */
	public function get_plugin_url() {
		if ( isset( $this->plugin_url ) ) {
			return $this->plugin_url;
		}

		if ( is_ssl() ) {
			$this->plugin_url = str_replace(
				'http://',
				'https://',
				WP_PLUGIN_URL
			) . '/' . plugin_basename( dirname( __DIR__, 1 ) );
		} else {
			$this->plugin_url = WP_PLUGIN_URL . '/' . plugin_basename( dirname( __DIR__, 1 ) );
		}

		return $this->plugin_url;
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$form_fields = array(
			self::ENABLE                             => array(
				'title'       => __( 'Enable/Disable', 'fortis-for-woocommerce' ),
				'label'       => __( 'Enable Fortis Payment Gateway', 'fortis-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __(
					'This controls whether or not this gateway is enabled within WooCommerce.',
					'fortis-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'no',
			),

			FortisApi::ENVIRONMENT                   => array(
				'title'       => __( 'Environment', 'fortis-for-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'production' => 'Production',
					'sandbox'    => 'Developer (Test) Mode',

				),
				'description' => __(
					'Select the transaction environment.
                     Selecting Developer (Test) Mode will allow you to run Test Transactions.',
					'fortis-for-woocommerce'
				),
				'default'     => 'production',
				'desc_tip'    => true,
			),

			FortisApi::PRODUCTION_USER_ID            => array(
				'title'       => __( 'User ID', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'Please enter your User ID.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'production_input',
				'desc_tip'    => true,
			),

			FortisApi::PRODUCTION_USER_API_KEY       => array(
				'title'       => __( 'User API Key', 'fortis-for-woocommerce' ),
				'type'        => 'password',
				'description' => __(
					'Please enter your User API Key.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'production_input',
				'desc_tip'    => true,
			),

			FortisApi::PRODUCTION_LOCATION_ID        => array(
				'title'       => __( 'Location ID', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'Please enter your Location ID.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'production_input',
				'desc_tip'    => true,
			),

			FortisApi::PRODUCTION_PRODUCT_ID_CC      => array(
				'title'       => __( 'Product ID (CC)', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'Please enter your CC Product ID.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'production_input',
				'desc_tip'    => true,
			),
			FortisApi::PRODUCTION_PRODUCT_ID_ACH     => array(
				'title'       => __( 'Product ID (ACH)', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'Please enter your ACH Product ID.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'production_input',
				'desc_tip'    => true,
			),

			FortisApi::SANDBOX_USER_ID               => array(
				'title'       => __( 'Sandbox User ID', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'Please enter your User ID.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'sandbox_input',
				'desc_tip'    => true,
			),

			FortisApi::SANDBOX_USER_API_KEY          => array(
				'title'       => __( 'Sandbox User API Key', 'fortis-for-woocommerce' ),
				'type'        => 'password',
				'description' => __(
					'Please enter your User API Key.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'sandbox_input',
				'desc_tip'    => true,
			),

			FortisApi::SANDBOX_LOCATION_ID           => array(
				'title'       => __( 'Sandbox Location ID', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'Please enter your Sandbox Location ID.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'sandbox_input',
				'desc_tip'    => true,
			),

			FortisApi::SANDBOX_PRODUCT_ID_CC         => array(
				'title'       => __( 'Sandbox Product ID (CC)', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'Please enter your Sandbox CC Product ID.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'sandbox_input',
				'desc_tip'    => true,
			),

			FortisApi::SANDBOX_PRODUCT_ID_ACH        => array(
				'title'       => __( 'Sandbox Product ID (ACH)', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'Please enter your Sandbox ACH Product ID.',
					'fortis-for-woocommerce'
				),
				'default'     => '',
				'class'       => 'sandbox_input',
				'desc_tip'    => true,
			),

			FortisApi::TRANSACTION_TYPE              => array(
				'title'       => __( 'Transaction Type', 'fortis-for-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'sale'      => 'Sale (Authorize and Capture)',
					'auth-only' => 'Authorize Only',
				),
				'description' => __( 'Select your Transaction Type.', 'fortis-for-woocommerce' ),
				'default'     => 'sale',
				'desc_tip'    => true,
			),
			self::LOGGING                            => array(
				'title'       => __( 'Enable Logging', 'fortis-for-woocommerce' ),
				'label'       => ' ',
				'type'        => 'checkbox',
				'description' => __( 'Enable WooCommerce Logging', 'fortis-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			FortisApi::TOKENIZATION                  => array(
				'title'       => 'Enable tokenization',
				'type'        => 'checkbox',
				'description' => __(
					'Provides the ability for users to store their credit card details.',
					'fortis-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			FortisApi::LEVEL3                        => array(
				'title'       => 'Enable level 3',
				'type'        => 'checkbox',
				'description' => __(
					'Provides the ability for users to enable addition of level 3 data to transaction.',
					'fortis-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			FortisApi::CC                            => array(
				'title'       => 'Enable CC transactions',
				'type'        => 'checkbox',
				'description' => __(
					'Provides the ability for users to have cc transactions.',
					'fortis-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			FortisApi::ACH                           => array(
				'title'       => 'Enable ACH transactions',
				'type'        => 'checkbox',
				'description' => __(
					'Provides the ability for users to have ach transactions.',
					'fortis-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			FortisApi::APPLEPAY                      => array(
				'title'       => 'Enable Apple Pay',
				'type'        => 'checkbox',
				'description' => __(
					'Provides the ability for users to have Apple Pay transactions.',
					'fortis-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			FortisApi::GOOGLEPAY                     => array(
				'title'       => 'Enable Google Pay',
				'type'        => 'checkbox',
				'description' => __(
					'Provides the ability for users to have Google Pay transactions.',
					'fortis-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			self::TITLE                              => array(
				'title'       => __( 'Title', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'This controls the title which the user sees during checkout.',
					'fortis-for-woocommerce'
				),
				'desc_tip'    => false,
				'default'     => __( 'Fortis', 'fortis-for-woocommerce' ),
			),
			self::DESCRIPTION                        => array(
				'title'       => __( 'Description', 'fortis-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __(
					'This controls the description which the user sees during checkout.',
					'fortis-for-woocommerce'
				),
				'default'     => 'Pay via Fortis',
			),
			self::BUTTON_TEXT                        => array(
				'title'       => __( 'Order Button Text', 'fortis-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'Changes the text that appears on the Fortis Make Payment button',
					'fortis-for-woocommerce'
				),
				'default'     => 'Make a Payment',
			),
			FortisApi::THEME                         => array(
				'title'       => __( 'Theme', 'fortis-for-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'default' => 'Default',
					'dark'    => 'Dark',
				),
				'desc_tip'    => false,
				'description' => 'Sets the theme for the Fortis Payment portal',
				'default'     => 'default',
			),
			FortisApi::FLOATINGLABELS                => array(
				'title'   => __( 'Floating Labels', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			FortisApi::SHOWVALIDATIONANIMATION       => array(
				'title'   => __( 'Show Validation Animation', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			FortisApi::HIDEAGREEMENTCHECKBOX         => array(
				'title'   => __( 'Hide Agreement Checkbox', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			FortisApi::COLORBUTTONSELECTEDTEXT       => array(
				'title'   => __( 'Color Button Selected Text', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::WHITE,
			),
			FortisApi::COLORBUTTONSELECTEDBACKGROUND => array(
				'title'   => __( 'Color Button Selected Background', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::BLACK,
			),
			FortisApi::COLORBUTTONACTIONBACKGROUND   => array(
				'title'   => __( 'Color Button Action Background', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::COLOURBUTTON,
			),
			FortisApi::COLORBUTTONACTIONTEXT         => array(
				'title'   => __( 'Color Button Action Text', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::WHITE,
			),
			FortisApi::COLORBUTTONBACKGROUND         => array(
				'title'   => __( 'Color Button Background', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::COLOURBUTTON,
			),
			FortisApi::COLORBUTTONTEXT               => array(
				'title'   => __( 'Color Button Text', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::BLACK,
			),
			FortisApi::COLORFIELDBACKGROUND          => array(
				'title'   => __( 'Color Field Background', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::WHITE,
			),
			FortisApi::COLORFIELDBORDER              => array(
				'title'   => __( 'Color Field Border', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::BLACK,
			),
			FortisApi::COLORTEXT                     => array(
				'title'   => __( 'Color Text', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::BLACK,
			),
			FortisApi::COLORLINK                     => array(
				'title'   => __( 'color Link', 'fortis-for-woocommerce' ),
				'label'   => ' ',
				'type'    => 'Color',
				'default' => $this->fortis_api::COLOURLINK,
			),
			FortisApi::FONTSIZE                      => array(
				'title'   => __( 'Font Size', 'fortis-for-woocommerce' ),
				'type'    => 'text',
				'default' => '16px',
			),
			FortisApi::MARGINSPACING                 => array(
				'title'   => __( 'Margin Spacing', 'fortis-for-woocommerce' ),
				'type'    => 'text',
				'default' => '0.5rem',
			),
			FortisApi::BORDERRADIUS                  => array(
				'title'   => __( 'Border Radius', 'fortis-for-woocommerce' ),
				'type'    => 'text',
				'default' => '10px',
			),

		);

		/**
		 * Apply filter to form fields
		 *
		 * @since 1.0.0
		 */
		$this->form_fields = apply_filters( 'fnb_fortis_settings', $form_fields ) ?? $form_fields;
	}

	/**
	 * Message for declined transactions
	 *
	 * @param string $result_description Fortis Result Description.
	 */
	public function declined_msg( $result_description ) {
		echo '<p class="woocommerce-thankyou-order-failed">'
			. esc_html( $result_description )
			. '</p>';
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title'
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		?>
		<h3>
			<?php
			esc_html_e( 'Fortis Payment Gateway Settings', 'fortis-for-woocommerce' );
			?>
		</h3>
		<p>

		<table class="form-table" aria-describedby="fortis">
			<th scope="col"></th>
			<?php
			$this->generate_settings_html(); // Generate the HTML For the settings form.
			?>
			<script type="text/javascript">
				jQuery('#woocommerce_<?php echo esc_html( self::ID ); ?>_environment').on('change', function () {
					var sandbox = jQuery('.sandbox_input').closest('tr');
					var production = jQuery('.production_input').closest('tr');
					var environment = this.value;

					if (environment === 'sandbox') {
						sandbox.show();
						production.hide();
					} else {
						sandbox.hide();
						production.show();
					}
				}).change();

				jQuery(function ($) {
					$('#mainform').submit(function (e) {
						let allowSubmit = false;
						if ($('#woocommerce_<?php echo esc_html( self::ID ); ?>_environment').val() === 'production') {
							allowSubmit = true;
							$('.production_input').each(function () {
								if ($(this).val() == '' && $(this).attr('id').includes('user')) allowSubmit = false;
							});
							if (!allowSubmit) {
								alert(
									'Warning! In order to enter Live Production Mode you must enter values for' +
									' both User ID, User API Key.'
								);
							}
							return allowSubmit;
						} else if ($('#woocommerce_<?php echo esc_html( self::ID ); ?>_environment').val() === 'sandbox') {
							allowSubmit = true;
							$('.sandbox_input').each(function () {
								if ($(this).val() == '' && $(this).attr('id').includes('user')) allowSubmit = false;
							});
							if (!allowSubmit) {
								alert(
									'Warning! In order to enter Developer Mode you must enter values for' +
									' both User ID, User API Key.'
								);
							}
							return allowSubmit;
						}

					});
				});
			</script>

		</table>
		<!--/.form-table-->
		<?php
	}

	/**
	 * Enable vaulting and card selection for Fortis
	 *
	 * @throws Exception Vaulting error.
	 * @since 1.0.0
	 */
	public function payment_fields() {
		if ( is_cart() ) {
			$this->testModeNotice();
		} elseif ( is_checkout() ) {
			$this->testModeNotice();
			$this->generate_fortis_form();
		} else {
			$allowed_tags = array(
				'h2'  => array(
					'style' => array(),
				),
				'div' => array(),
				'br'  => array(),
				'hr'  => array(),
			);

			echo wp_kses(
				'<div style="text-align:center;">
                    <h2 style="margin: 0">Card Vault</h2>
                    <br/>This transaction is for card vaulting purposes only, you will not be charged.<br>
                    Click "add payment method" after completing this transaction to save the card.
                    <hr/>
                  </div>',
				$allowed_tags
			);
			$this->generate_fortis_add_payment_method_form(); // used to create token for account.
		}
	}

	/**
	 * Display a notice if in test mode
	 *
	 * @return void
	 */
	public function testModeNotice() {
		if ( isset( $this->settings[ FortisApi::ENVIRONMENT ] ) &&
			'sandbox' === $this->settings[ FortisApi::ENVIRONMENT ] ) {
			$allowed_tags = array(
				'div'    => array(
					'style' => array(),
				),
				'strong' => array(),
				'br'     => array(),
				'hr'     => array(),
			);

			echo wp_kses(
				'<div style="text-align:center;">
                    <strong>TEST MODE is currently enabled.</strong>
                    <br/>Transactions processed in Test Mode will not process real money.
                    <hr/>
                  </div>',
				$allowed_tags
			);
		}
	}

	/**
	 * Can the order be refunded?
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		$ach_refund_statuses = array( 'Pending Originating', 'Pending Originated', self::PROCESSING, self::REFUNDED );

		$refundable = true;
		if ( $order->get_meta( 'action' ) === 'auth-only' ||
			( $order->get_meta( 'payment_method' ) === 'ach' &&
				! in_array( $order->get_meta( 'order_status' ) ?? '', $ach_refund_statuses, true ) )
		) {
			$refundable = false;
		}

		return $refundable;
	}

	/**
	 * Process Refund
	 *
	 * @param int        $order_id Order id.
	 * @param float|null $amount Refund amount.
	 * @param string     $reason Reason for refund.
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order                            = wc_get_order( $order_id );
		$tax_amount                       = (int) ( $order->get_total_tax_refunded() * 100 );
		$transaction_amount               = (int) ( $amount * 100 );
		$transaction_id                   = $order->get_meta( 'transaction_id', true );
		$this->fortis_api->payment_method = $order->get_meta( 'payment_method', true );

		$status = $this->fortis_api->refund( $transaction_id, $transaction_amount, $tax_amount, $order_id );

		return 1 === (int) $status;
	}

	/**
	 * Generate the Fortis form
	 *
	 * @return void
	 */
	public function generate_fortis_form() {
		$wcsession = WC()->session;

		$customer    = $wcsession->get( 'customer' );
		$customer_id = $customer['id'];

		global $woocommerce;
		$total      = (int) ( $woocommerce->cart->total * 100 );
		$tax_amount = (int) ( $woocommerce->cart->tax_total * 100 );

		$save_account = $this->fortis_api->framework->vault_enabled() && $customer_id > 0;

		$wcsession->__unset( 'client_token' );

		if ( ! $wcsession->get( 'client_token' ) ) {
			$client_token = $this->fortis_api->get_client_token( $total, $tax_amount, $save_account );
			$wcsession->set( 'client_token', $client_token );
			$wcsession->set( 'total', $total );
			$wcsession->set(
				'$transaction_api_id',
				substr(
					md5( uniqid( wp_rand(), true ) ),
					0,
					32
				)
			);
		}
		$client_token       = '';
		$client_token       = $wcsession->get( 'client_token', $client_token );
		$transaction_api_id = $wcsession->get( 'transaction_api_id', $client_token );

		if ( ! $client_token ) {
			$this->invalidSettingsDisplay();

			return;
		}

		$url                       = esc_url( $this->redirect_url );
		$floatingLabels            = 'yes' === $this->settings[ FortisApi::FLOATINGLABELS ] ? 'true' : 'false';
		$view                      = 'yes' === $this->settings[ FortisApi::ACH ] ? 'default' : 'card-single-field';
		$show_validation_animation = 'yes' === $this->settings[ FortisApi::SHOWVALIDATIONANIMATION ] ? 'true' : 'false';
		$hide_agreement_checkbox   = 'yes' === $this->settings[ FortisApi::HIDEAGREEMENTCHECKBOX ] ? 'true' : 'false';
		$save_account_button       = '';
		$vault_select              = '';

		$verify = wp_verify_nonce(
			parse_str( $_POST['post_data'], $fields )
			?? $fields['woocommerce-process-checkout-nonce'] ?? '',
			'checkout'
		);
		if ( $verify ) {
			http_response_code( 417 );
			exit();
		}

		if ( $save_account ) {
			$save_account_button  = '<div id="save-account-container" class="column">';
			$save_account_button .= '<input type="checkbox" id=\'fortis_SaveAccount\' name=\'fortis_SaveAccount\'>';
			$save_account_button .= '<span class="checkbox"> Remember my credit card number</span><hr></div>';
			$token_ds             = new WC_Payment_Token_Data_Store();
			try {
				$tokens = $token_ds->get_tokens(
					array(
						'user_id' => $customer_id,
					)
				);
			} catch ( Throwable $ex ) {
				$tokens = array();
			}

			$vault_options = '';
			$found         = false;
			foreach ( $tokens as $token ) {
				$found        = true;
				$token_detail = WC_Payment_Tokens::get( $token->token_id );
				if ( $token_detail ) {
					$last4               = $token_detail->get_meta( 'last4' );
					$account_holder_name = $token_detail->get_meta( 'account_holder_name' );
					$first6              = $token_detail->get_meta( 'first6' );
					$card_type           = $token_detail->get_meta( 'card_type' );
					$vault_options      .= "<option value='{$token->token_id}'>";
					$vault_options      .= "$card_type - $account_holder_name ($first6...$last4)</option>";
				}
			}
			if ( $found ) {
				$vault_select  = '<div class="column"><div class="field"><select id="CC" name="CC" class="input">';
				$vault_select .= $vault_options;
				$vault_select .= '</select></div></div>';
				$vault_select .= '<input type="hidden" id="fortis_useSavedAccount" name="fortis_useSavedAccount" >';
				$vault_select .= '<input type="button" name="fortis_useSaved" id="fortis_useSaved"
                 value="Use Saved Credit Card" class="button" >';
			}
		}

		$fortisContainer = "<div id=\"fortis_detail_form_container\"><form id=\"fortis_detail_form\" role=\"form\"
                         action=\"$url\" method=\"post\" name=\"payment_form\">
                            <input type=\"hidden\" id=\"fortis_order_id\" name=\"fortis_order_id\">
                            <input type=\"hidden\" id=\"fortis_result\" name=\"fortis_result\">
                            $vault_select
                            $save_account_button
                        </form>
                        <div id=\"fortispayment\"></div></div><div id=\"dataClientToken\" data-id=\"$client_token\"></div>";

		wp_enqueue_script(
			'custom-checkout',
			plugins_url( '../assets-non-blocks/js/custom-checkout.js', __FILE__ ),
			array( 'jquery' ),
			'1.0',
			true
		);

		$digitalWallets = array();
		if ( $this->fortis_api->framework->get_googlepay_enabled() ) {
			array_push( $digitalWallets, 'GooglePay' );
		}
		if ( $this->fortis_api->framework->get_applepay_enabled() ) {
			array_push( $digitalWallets, 'ApplePay' );
		}

        $iframeConfig = $this->getIFrameConfig();

		wp_localize_script(
			'custom-checkout',
			'data',
			array(
				'clientToken'               => $client_token,
				'iframeConfig'              => $iframeConfig,
				'floatingLabels'            => $floatingLabels,
				'show_validation_animation' => $show_validation_animation,
				'hide_agreement_checkbox'   => $hide_agreement_checkbox,
				'digitalWallets'            => $digitalWallets,
				'transaction_api_id'        => $transaction_api_id,
				'view'                      => $view,
			)
		);

		$allowed_tags = array(
			'form'   => array(
				'id'     => array(),
				'role'   => array(),
				'action' => array(),
				'method' => array(),
				'name'   => array(),
			),
			'input'  => array(
				'type'    => array(),
				'id'      => array(),
				'name'    => array(),
				'value'   => array(),
				'class'   => array(),
				'onclick' => array(),
			),
			'div'    => array(
				'id'      => array(),
				'class'   => array(),
				'style'   => array(),
				'data-id' => array(),
			),
			'select' => array(
				'id'    => array(),
				'name'  => array(),
				'class' => array(),
			),
			'option' => array(
				'value' => array(),
			),
			'style'  => array(),
			'br'     => array(),
			'span'   => array(
				'class' => array(),
			),
			'hr'     => array(),
		);
		echo wp_kses( $fortisContainer, $allowed_tags );
	}

    /**
     * @return array
     */
    public function getIFrameConfig(): array
    {
        return [
          'theme' => $this->settings[FortisApi::THEME],
          'environment' => $this->settings[FortisApi::ENVIRONMENT],
          'colorButtonSelectedBackground' => $this->settings[FortisApi::COLORBUTTONSELECTEDBACKGROUND],
          'colorButtonSelectedText' => $this->settings[FortisApi::COLORBUTTONSELECTEDTEXT],
          'colorButtonActionBackground' => $this->settings[FortisApi::COLORBUTTONACTIONBACKGROUND],
          'colorButtonActionText' => $this->settings[FortisApi::COLORBUTTONACTIONTEXT],
          'colorButtonBackground' => $this->settings[FortisApi::COLORBUTTONBACKGROUND],
          'colorButtonText' => $this->settings[FortisApi::COLORBUTTONTEXT],
          'colorFieldBackground' => $this->settings[FortisApi::COLORFIELDBACKGROUND],
          'colorFieldBorder' => $this->settings[FortisApi::COLORFIELDBORDER],
          'colorText' => $this->settings[FortisApi::COLORTEXT],
          'colorLink' => $this->settings[FortisApi::COLORLINK],
          'fontSize' => $this->settings[FortisApi::FONTSIZE],
          'marginSpacing' => $this->settings[FortisApi::MARGINSPACING],
          'borderRadius' => $this->settings[FortisApi::BORDERRADIUS]
        ];
    }

	/**
	 * Gets Woo Checkout  Data
	 *
	 * @return array
	 */
	public function getBillingData() {
		$verify = wp_verify_nonce(
			parse_str( $_POST['fields'], $fields )
			?? $fields['woocommerce-process-checkout-nonce'] ?? '',
			$_REQUEST['action'] ?? ''
		);
		if ( $verify ) {
			http_response_code( 417 );
			exit();
		}
		if ( ! empty( $_POST['fields'] ) ) {
			parse_str( filter_var( wp_unslash( $_POST['fields'] ), FILTER_UNSAFE_RAW ), $post_data );

			$orderID = WC()->session->get( 'order_id' );

			$address    = sanitize_text_field( $post_data['billing_address_1'] . ' ' . $post_data['billing_address_2'] );
			$city       = sanitize_text_field( $post_data['billing_city'] );
			$postalCode = sanitize_text_field( $post_data['billing_postcode'], FILTER_VALIDATE_INT );
			$country    = sanitize_text_field( $post_data['billing_country'] );
			$state      = sanitize_text_field( $post_data['billing_state'] );

			return array(
				'address'    => $address,
				'city'       => $city,
				'postalCode' => $postalCode,
				'country'    => $country,
				'state'      => $state,
				'orderID'    => (string) $orderID,
			);
		} else {
			return array();
		}
	}

	/**
	 * Add payment method via account screen. This should be extended by gateway plugins.
	 *
	 * @return array
	 * @since 3.2.0 Included here from 3.2.0, but supported from 3.0.0.
	 */
	public function add_payment_method() {
		$wcsession   = WC()->session;
		$customer_id = $wcsession->get( 'customer' )['id'];

		$nonce  = $_REQUEST['_wpnonce'] ?? '';
		$verify = wp_verify_nonce( $nonce, - 1 );
		if ( $verify ) {
			http_response_code( 417 );
			exit();
		}
		$result        = json_decode( wp_unslash( array_map( 'sanitize_text_field', $_POST )['fortis_result'] ) );
		$saved_account = $result->data->saved_account ?? null;
		$token_id      = $saved_account->id ?? null;

		if ( null !== $token_id ) {
			$this->fortis_api->framework->vault_card( $token_id, $saved_account, $customer_id );

			return array(
				'result'   => 'success',
				'redirect' => wc_get_endpoint_url( 'payment-methods' ),
			);
		} else {
			return array(
				'result' => 'failure',
			);
		}
	}

	/**
	 * Generates invalid settings display HTML
	 *
	 * @return void
	 */
	public function invalidSettingsDisplay() {
		$allowed_tags = array_replace_recursive(
			wp_kses_allowed_html( 'post' ),
			array(
				'div'   => array(
					'id' => true,
				),
				'br'    => array(),
				'style' => array(),
			)
		);

		$message = 'Invalid settings for Fortis Gateway. <br/> Please check your configuration.';
		$css     = '<style>.place-order {display:none;}</style>';

		echo wp_kses( $message . $css, $allowed_tags );
	}

	/**
	 * Generate form for Fortis add payment method
	 *
	 * @return void
	 */
	public function generate_fortis_add_payment_method_form() {
		$client_token = $this->fortis_api->get_payment_method_token();

		if ( ! $client_token ) {
			$this->invalidSettingsDisplay();

			return;
		}

		$floatingLabels            = 'yes' === $this->settings[ FortisApi::FLOATINGLABELS ] ? 'true' : 'false';
		$show_validation_animation = 'yes' === $this->settings[ FortisApi::SHOWVALIDATIONANIMATION ] ? 'true' : 'false';
		$hide_agreement_checkbox   = 'yes' === $this->settings[ FortisApi::HIDEAGREEMENTCHECKBOX ] ? 'true' : 'false';

		wp_enqueue_style(
			'fortis-vaulting',
			plugins_url( '../assets-non-blocks/css/fortis-vaulting.css', __FILE__ ),
			array(),
			'1.0',
			'all'
		);

		$font_size          = esc_attr( $this->settings[ FortisApi::FONTSIZE ] );
		$color_field_border = esc_attr( $this->settings[ FortisApi::COLORFIELDBORDER ] );
		$border_radius      = esc_attr( $this->settings[ FortisApi::BORDERRADIUS ] );
		$background_color   = esc_attr( $this->settings[ FortisApi::COLORBUTTONACTIONBACKGROUND ] );
		$margin_spacing     = esc_attr( $this->settings[ FortisApi::MARGINSPACING ] );
		$color_text         = esc_attr( $this->settings[ FortisApi::COLORBUTTONACTIONTEXT ] );

		$formCSS = "
		    #add_payment_method #place_order {
		        font-size: {$font_size};
		        border-block-color: {$color_field_border} !important;
		        border-radius: {$border_radius} !important;
		        background-color: {$background_color} !important;
		        margin: {$margin_spacing} auto !important;
		        color: {$color_text} !important;
		    }
		";

		wp_add_inline_style( 'fortis-vaulting', $formCSS );

		wp_enqueue_script(
			'card-vault',
			plugins_url( '../assets-non-blocks/js/card-vault.js', __FILE__ ),
			array( 'jquery' ),
			'1.0',
			true
		);

		wp_localize_script(
			'card-vault',
			'data',
			array(
				'clientToken'               => $client_token,
				'fortisSettings'            => $this->settings,
				'floatingLabels'            => $floatingLabels,
				'show_validation_animation' => $show_validation_animation,
				'hide_agreement_checkbox'   => $hide_agreement_checkbox,
			)
		);

		$html = '<input type=\'hidden\' id=\'fortis_result\' name=\'fortis_result\' >
                        <input type=\'hidden\' id=\'fortis_addPaymentMethod\' name=\'fortis_addPaymentMethod\'
                         value=\'on\'>
                        <div id=\'fortispayment\'></div>';

		$allowed_tags = array(
			'input' => array(
				'type'    => array(),
				'id'      => array(),
				'name'    => array(),
				'value'   => array(),
				'class'   => array(),
				'onclick' => array(),
			),
			'div'   => array(
				'id'    => array(),
				'class' => array(),
				'style' => array(),
			),
		);

		echo wp_kses( $html, $allowed_tags );
	}

	/**
	 * Review payment processing
	 *
	 * @return void
	 * @throws \WC_Data_Exception WC_Data_Exception.
	 */
	public function process_review_payment() {
		$nonce  = $_REQUEST['_wpnonce'] ?? '';
		$verify = wp_verify_nonce( $nonce, 'process_review_payment' );
		if ( $verify ) {
			http_response_code( 417 );
			exit();
		}
		if ( ! empty( $_POST['order_id'] ) ) {
			$this->process_payment( sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) );
		}
	}

	/**
	 * Get icon
	 *
	 * Add SVG icon to checkout
	 */
	public function get_icon() {
		$icon = '<div style=\"display:flex;justify-content:flex-end;width:100%;align-items:flex-start"><img src="' .
				esc_url( WC_HTTPS::force_https_url( $this->icon ) ) .
				'" alt="' . esc_attr( $this->get_title() )
				. '" style="width: auto !important; height: 25px !important;
 max-width: 100%; border: none !important;"></div>';

		/**
		 * Gateway icon filters applied
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array
	 * @throws \WC_Data_Exception WC_Data_Exception.
	 * @since 1.0.0
	 */
	public function process_payment( $order_id ) {
		$nonce  = $_REQUEST['woocommerce-process-checkout-nonce'] ?? '';
		$verify = wp_verify_nonce( $nonce, 'process_review_payment' );
		if ( $verify ) {
			http_response_code( 417 );
			exit();
		}
		$order     = new WC_Order( $order_id );
		$wcsession = WC()->session;
		$wcsession->set( 'order_id', $order_id );
		$wcsession->set( 'post', array_map( 'sanitize_text_field', $_POST ) );
		$order->set_payment_method( self::ID );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Show Message.
	 *
	 * Display message depending on order results.
	 *
	 * @param string $content Content.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function show_message( $content ) {
		return '<div class="' . esc_attr( $this->msg[ self::WC_CLASS ] ) . '">' .
				esc_url( $this->msg[ self::MESSAGE ] ) . '</div>' . esc_url( $content );
	}

	/**
	 * Get the Fortis id
	 *
	 * @return int|null
	 */
	public function get_order_id_order_pay() {
		global $wp;

		// Get the order ID.
		$order_id = absint( $wp->query_vars['order-pay'] );

		if ( empty( $order_id ) || 0 === $order_id ) {
			return null;
		}

		// Exit.
		return $order_id;
	}

	/**
	 * Receipt page.
	 *
	 * Display text and a button to direct the customer to Fortis.
	 *
	 * @param int $order_id Order id.
	 *
	 * @since 1.0.0
	 */
	public function receipt_page( $order_id ) {
		wp_safe_redirect( esc_url( $this->redirect_url ) );
		exit();
	}

	/**
	 * Add WooCommerce notice
	 *
	 * @param string $message Message.
	 * @param string $notice_type Type of notice.
	 * @param string $order_id Order id.
	 *
	 * @since 1.0.0
	 */
	public function add_notice( $message, $notice_type = 'success', $order_id = '' ) {
		$message     = sanitize_text_field( $message );
		$notice_type = sanitize_text_field( $notice_type );
		$order_id    = sanitize_text_field( $order_id );

		global $woocommerce;

		if ( '' !== $order_id ) {
			add_post_meta( $order_id, 'fortis_error', $message );
		}

		self::$wc_logger->add( 'fortisfortispay', 'In add notice: ' . wp_json_encode( $message ) );

		$wc_session = WC()->session;
		if ( $wc_session ) {
			$wc_session->set( 'fortispay3_error_message', $message );
			$notices = $wc_session->get( 'wc_notices' );
			if ( self::$wc_logger ) {
				self::$wc_logger->add( 'fortisfortispay', 'Session notices: ' . wp_json_encode( $notices ) );
				self::$wc_logger->add( 'fortisfortispay', 'Session : ' . wp_json_encode( $wc_session ) );
			}
		} elseif ( self::$wc_logger ) {
			self::$wc_logger->add( 'fortisfortispay', 'Session not set ' );
		}

		// If function should we use?
		if ( function_exists( 'wc_add_notice' ) ) {
			// Use the new version of the add_error method.
			wc_add_notice( $message, $notice_type );
		} else {
			// Use the old version.
			$woocommerce->add_error( $message );
		}
	}

	/**
	 * Final processing of order
	 *
	 * @param int       $status Payment status.
	 * @param \WC_Order $order Order being processed.
	 * @param string    $transaction_id Fortis transaction id.
	 * @param string    $result_desc Fortis result description.
	 */
	protected function processOrderFinal( $status, $order, $transaction_id, $result_desc ) {
		$wcsession = WC()->session;
		$wcsession->__unset( 'client_token' );

		switch ( $status ) {
			case 1:
				$this->processOrderFinalSuccess( $order );
				$wcsession->__unset( 'order_id' );
				exit();
			case 2:
				$this->add_notice( 'The transaction was declined', self::ERROR, $order->get_id() );
				$this->processOrderFinalFail( $order, $result_desc );
				$order->update_status( 'Declined' );
				exit;
			case 3:
				$order->payment_complete( $transaction_id );
				$order->update_status( 'Error' );
				$this->add_notice( 'The transaction had an error', self::ERROR, $order->get_id() );
				$this->processOrderFinalFail( $order, $result_desc );
				exit;
			case 4:
				$this->add_notice( 'The transaction was cancelled', self::ERROR, $order->get_id() );
				$this->processOrderFinalCancel( $order );
				exit;
			case 5:
				$this->add_notice( 'The transaction is pending', self::PENDING, $order->get_id() );
				$this->processOrderFinalPending( $order );
				$wcsession->__unset( 'order_id' );
				exit;
			default:
				$redirect_link = $order->get_cancel_order_url();
				$this->redirectAfterOrder( $redirect_link );
				exit;
		}
	}

	/**
	 * Redirects after order
	 *
	 * @param string $redirect_link Redirect link.
	 */
	protected function redirectAfterOrder( $redirect_link ) {
		$redirect_link = str_replace( '&amp;', '&', $redirect_link );
		wp_safe_redirect( $redirect_link );
	}

	/**
	 * Process successful payment
	 *
	 * @param \WC_Order $order Order being processed.
	 */
	protected function processOrderFinalSuccess( $order ) {
		WC()->cart->empty_cart();
		if ( $order->get_status() === 'checkout-draft' ) {
			$order->set_payment_method( self::ID );
			$order->update_status( self::PENDING );
		}
		if ( 'auth-only' === $order->get_meta( 'action' ) ) {
			$order->update_status( self::ONHOLD );
		} else {
			$order->update_status( self::PROCESSING );
		}

		$redirect_link = $this->get_return_url( $order );
		$this->custom_print_notices();
		$this->redirectAfterOrder( $redirect_link );
	}

	/**
	 * Process pending payment
	 *
	 * @param \WC_Order $order Order being processed.
	 */
	protected function processOrderFinalPending( $order ) {
		WC()->cart->empty_cart();
		$order->update_status( self::ONHOLD );

		$redirect_link = $this->get_return_url( $order );
		$this->custom_print_notices();
		$this->redirectAfterOrder( $redirect_link );
	}

	/**
	 * Process cancelled payment
	 *
	 * @param \WC_Order $order Order being processed.
	 */
	protected function processOrderFinalCancel( $order ) {
		if ( ! $order->has_status( self::FAILED ) ) {
			$order->add_order_note(
				'Response via Notify, User cancelled transaction'
			);
			$order->update_status( self::FAILED );
		}
		$order->update_status( self::FAILED );
		$redirect_link = $order->get_cancel_order_url();
		$this->custom_print_notices();
		$this->redirectAfterOrder( $redirect_link );
	}

	/**
	 * Process failed payment
	 *
	 * @param \WC_Order $order Order being processed.
	 * @param string    $result_desc Fortis result description.
	 */
	protected function processOrderFinalFail( $order, $result_desc ) {
		if ( ! $order->has_status( self::FAILED ) ) {
			$order->add_order_note( 'Response via Notify, RESULT_DESC: ' . sanitize_text_field( $result_desc ) );
		}
		$order->update_status( self::FAILED );
		$redirect_link = $order->get_cancel_order_url();
		$this->custom_print_notices();
		$this->redirectAfterOrder( $redirect_link );
	}

	/**
	 * Custom function added to overcome notice failures in recent versions
	 *
	 * @param bool $should_return Return or not.
	 *
	 * @return string|void
	 */
	protected function custom_print_notices( bool $should_return = false ) {
		if ( ! did_action( 'woocommerce_init' ) ) {
			wc_doing_it_wrong(
				__FUNCTION__,
				__( 'This function should not be called before woocommerce_init.', 'fortis-for-woocommerce' ),
				'2.3'
			);
		}

		$all_notices = WC()->session->get( 'wc_notices', array() );
		/**
		 * Apply WC filters
		 *
		 * @since 1.0.0
		 */
		$notice_types = apply_filters( 'woocommerce_notice_types', array( 'error', 'success', 'notice' ) );

		// Buffer output.
		ob_start();

		foreach ( $notice_types as $notice_type ) {
			if ( wc_notice_count( $notice_type ) > 0 ) {
				$messages = array();

				foreach ( $all_notices[ $notice_type ] as $notice ) {
					$messages[] = esc_url( $notice['notice'] ?? $notice );
				}

				wc_get_template(
					"notices/{$notice_type}.php",
					array(
						'messages' => array_filter( $messages ),
						// @deprecated 3.9.0
						'notices'  => array_filter( $all_notices[ $notice_type ] ),
					)
				);
			}
		}

		if ( $should_return ) {
			return wc_kses_notice( ob_get_clean() );
		}

		echo wp_kses_post( wc_kses_notice( ob_get_clean() ) );
	}

	/**
	 * Process POST return from Fortis
	 *
	 * @return void
	 * @throws \WC_Data_Exception WC_Data_Exception.
	 */
	public function check_fortis_notify_response(): void {
		$nonce  = $_REQUEST['_wpnonce'] ?? '';
		$action = 'check_fortis_notify_response';
		$verify = wp_verify_nonce( $nonce, $action );
		if ( $verify ) {
			http_response_code( 417 );
			exit();
		}
		// Log notify response for debugging purposes.
		if ( $this->logging ) {
			self::$wc_logger->add(
				'fortis_ach_notify',
				'Notify POST: ' . wp_json_encode( array_map( 'sanitize_text_field', $_POST ) )
			);
			self::$wc_logger->add(
				'fortis_ach_notify',
				'Notify GET: ' . wp_json_encode( array_map( 'sanitize_text_field', $_GET ) )
			);
		}

		if ( ! isset( $_POST['data'] ) ) {
			http_response_code( 417 );
			exit();
		}

		$fortis_data    = json_decode( wp_unslash( sanitize_text_field( wp_unslash( $_POST['data'] ) ) ) );
		$order_id       = $fortis_data->description;
		$order          = wc_get_order( $order_id );
		$transaction_id = $fortis_data->id;
		$ach_refund_ids = $order->get_meta( 'ach_refund_ids' );
		if ( '' !== $ach_refund_ids ) {
			$ach_refund_ids = json_decode( $ach_refund_ids );
		} else {
			$ach_refund_ids = array();
		}
		$is_refund = false;
		if ( in_array( $transaction_id, $ach_refund_ids, true ) ) {
			$is_refund = true;
		}

		if ( 'checkout-draft' === $order->get_status() ) {
			$order->set_payment_method( self::ID );
		}

		if ( ! $is_refund ) {
			switch ( $fortis_data->status_id ) {
				case 131: // Pending Origination.
					// It's not possible to update an order status to a non-valid status, so set meta.
					$order->update_meta_data( 'order_status', 'Pending Origination' );
					break;
				case 132: // Originating.
					$order->update_meta_data( 'order_status', 'Pending Originating' );
					break;
				case 133: // Originated.
					$order->update_meta_data( 'order_status', 'Pending Originated' );
					break;
				case 134: // Settled.
					$order->update_meta_data( 'order_status', self::PROCESSING );
					$order->update_status( self::PROCESSING );
					break;
				default:
					break;
			}
		} else {
			switch ( $fortis_data->status_id ) {
				case 131: // Pending Origination.
					// It's not possible to update an order status to a non-valid status, so set meta.
					$order->update_meta_data( 'order_status', 'Pending Refund Origination' );
					break;
				case 132: // Originating.
					$order->update_meta_data( 'order_status', 'Pending Refund Originating' );
					break;
				case 133: // Originated.
					$order->update_meta_data( 'order_status', 'Pending Refund  Originated' );
					break;
				case 134: // Settled.
					$order->update_meta_data( 'order_status', self::REFUNDED );
					break;
				default:
					break;
			}
		}
		$order->save();

		http_response_code( 200 );
	}

	/**
	 * Helper for constructor
	 */
	protected function addActions() {
		add_action(
			'woocommerce_api_wc_gateway_fortis_ach_notify',
			array(
				$this,
				'check_fortis_notify_response',
			)
		);

		add_action(
			'woocommerce_api_wc_gateway_fortis_redirect',
			array(
				$this,
				'check_fortis_response',
			)
		);

		add_action(
			'woocommerce_api_wc_gateway_fortis_notify',
			array(
				$this,
				'check_fortis_notify_response',
			)
		);

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				&$this,
				'process_admin_options',
			)
		);

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				&$this,
				'custom_gateway_settings_changed',
			)
		);

		add_action(
			'woocommerce_receipt_fortis',
			array(
				$this,
				'receipt_page',
			)
		);

		add_action( 'wp_ajax_order_pay_payment', array( $this, 'process_review_payment' ) );
		add_action( 'wp_ajax_nopriv_order_pay_payment', array( $this, 'process_review_payment' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_custom_script' ) );
	}

	function custom_gateway_settings_changed() {
		$options  = get_option( 'woocommerce_fortis_settings' );
		$applepay = $options[ FortisApi::APPLEPAY ];

		if ( $applepay == 'yes' ) {
			$current_folder = plugin_basename( __DIR__ );
			$file           = 'apple-developer-merchantid-domain-association';
			$source         = WP_PLUGIN_DIR . '/' . $current_folder . '/' . $file;
			$target         = WP_PLUGIN_DIR . '/../../.well-known/' . $file;
			if ( file_exists( $source ) && ! file_exists( $target ) ) {
				copy( $source, $target );
			}
		}
	}

	/**
	 * Enqueue custom script
	 *
	 * @return void
	 */
	public function enqueue_custom_script() {
		wp_enqueue_script(
			'commercejs',
			'https://js.sandbox.fortis.tech/commercejs-v1.0.0.min.js',
			array(),
			'1.0',
			true
		);

		if ( is_checkout() ) {
			$font_size                      = esc_attr( $this->settings[ FortisApi::FONTSIZE ] );
			$color_field_border             = esc_attr( $this->settings[ FortisApi::COLORFIELDBORDER ] );
			$color_field_background         = esc_attr( $this->settings[ FortisApi::COLORFIELDBACKGROUND ] );
			$margin_spacing                 = esc_attr( $this->settings[ FortisApi::MARGINSPACING ] );
			$color_text                     = esc_attr( $this->settings[ FortisApi::COLORTEXT ] );
			$border_radius                  = esc_attr( $this->settings[ FortisApi::BORDERRADIUS ] );
			$color_button_action_background = esc_attr( $this->settings[ FortisApi::COLORBUTTONACTIONBACKGROUND ] );
			$color_button_action_text       = esc_attr( $this->settings[ FortisApi::COLORBUTTONACTIONTEXT ] );

			$formCSS = "
		        #fortis_detail_form .input {
		            font-size: {$font_size};
		            border-color: {$color_field_border} !important;
		            background-color: {$color_field_background} !important;
		            margin: {$margin_spacing} auto !important;
		            color: {$color_text} !important;
		            border-radius: {$border_radius} !important;
		        }

		        .checkbox {
		            font-size: {$font_size};
		            margin: {$margin_spacing} !important;
		            color: {$color_text} !important;
		        }

		        #fortis_detail_form .button {
		            font-size: {$font_size};
		            border-block-color: {$color_field_border} !important;
		            border-radius: {$border_radius} !important;
		            background-color: {$color_button_action_background} !important;
		            margin: {$margin_spacing} auto !important;
		            color: {$color_button_action_text} !important;
		        }
		    ";

			wp_add_inline_style( 'fortis-checkout', $formCSS );
		}

		wp_register_script(
			'custom-checkout',
			plugins_url( '../assets-non-blocks/js/custom-checkout.js', __FILE__ ),
			array( 'jquery' ),
			'1.0',
			true
		);

		wp_register_script(
			'card-vault',
			plugins_url( '../assets-non-blocks/js/card-vault.js', __FILE__ ),
			array( 'jquery' ),
			'1.0',
			true
		);

		wp_enqueue_style(
			'fortis-checkout',
			plugins_url( '../assets-non-blocks/css/fortis-checkout.css', __FILE__ ),
			array(),
			'1.0',
			'all'
		);
	}

	/**
	 * Check to see if this order Needs to be paid
	 *
	 * @param mixed     $a Not used.
	 * @param \WC_Order $order Order being processed.
	 * @param array     $valid_statuses Valid statuses.
	 *
	 * @return bool
	 */
	public static function order_needs_payment( $a, $order, $valid_statuses ) {
		$payment_method = $order->get_meta( 'payment_method' );
		$status         = $order->get_status();

		if ( $order->get_total() <= 0.00 ) {
			return false;
		}

		if ( 'ach' === $payment_method && self::PENDING === $status ) {
			return false;
		}

		return in_array( $order->get_status(), $valid_statuses, true );
	}

	/**
	 * Adds message if refund has already been created.
	 *
	 * @param \WC_Order $order Order being processed.
	 *
	 * @return void
	 */
	public static function add_ach_refund_message( WC_Order $order ) {
		$ach_refund_initiated = '' !== $order->get_meta( 'ach_refund_initiated' );
		if ( $ach_refund_initiated ) {
			$ach_refund_amount = (int) $order->get_meta( 'ach_refund_amount' ) / 100.0;
			$ach_refund_amount = number_format( $ach_refund_amount, 2 );
			echo "<h2 style='color:red;'>
Note: A Fortis ACH refund for this order has already been initiated, for the amount " .
				esc_html( $ach_refund_amount ) . '</h2>';
		}
	}
}
