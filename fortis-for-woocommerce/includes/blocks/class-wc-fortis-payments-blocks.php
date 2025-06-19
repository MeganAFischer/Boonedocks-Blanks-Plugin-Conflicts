<?php
/**
 * Plugin extension of WC_Payment_Gateway
 * Support for WC Blocks
 *
 * @package Fortis for WooCommerce
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Fortis Payments Blocks integration
 *
 * @since 1.0.3
 */
final class WC_Gateway_Fortis_Blocks_Support extends AbstractPaymentMethodType {


	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'fortis';
	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Fortis
	 */
	private $gateway;

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_Fortis_settings', array() );
		$this->gateway  = new WC_Gateway_Fortis();
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'fortis-tech',
			'https://js.sandbox.fortis.tech/commercejs-v1.0.0.min.js',
			array(),
			'1.0.6',
			array(
				'in_footer' => false,
			)
		);

		wp_enqueue_script( 'fortis-tech' );

		$script_path       = '/assets/js/frontend/blocks.js';
		$script_asset_path = WC_Fortis_Payments::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => '1.2.0',
			);

		$script_url = WC_Fortis_Payments::plugin_url() . $script_path;

		wp_register_script(
			'wc-Fortis-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'wc-Fortis-payments-blocks',
				'woocommerce-gateway-Fortis',
				WC_Fortis_Payments::plugin_abspath() . 'languages/'
			);
		}

		return array( 'wc-Fortis-payments-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		if ( null === WC()->cart ) {
			return array();
		}

		if ( 0 === WC()->cart->get_cart_contents_count() ) {
			return array();
		}

		$fortis_api = $this->gateway->fortis_api;
		$wcsession  = WC()->session;

		$customer    = $wcsession->get( 'customer' );
		$customer_id = $customer['id'];
		global $woocommerce;

		$order_id = $wcsession->get( 'store_api_draft_order' );
		if ( ! $order_id || ( $order_id !== $wcsession->get( 'order_id' ) ) ) {
			$wcsession->__unset( 'client_token' );
			$wcsession->set( 'order_id', $order_id );
		}

		$total      = (int) ( $woocommerce->cart->total * 100 );
		$tax_amount = (int) ( $woocommerce->cart->tax_total * 100 );

		$url          = esc_url( add_query_arg( 'wc-api', 'WC_Gateway_Fortis_Redirect', home_url( '/' ) ) );
		$save_account = $fortis_api->framework->vault_enabled() && $customer_id > 0;

		if ( $wcsession->get( 'total', $total ) && (int) $wcsession->get( 'total' ) !== $total + $tax_amount ) {
			$wcsession->__unset( 'client_token' );
		}

		if ( ! $wcsession->get( 'client_token' ) ) {
			$client_token = $fortis_api->get_client_token( $total, $tax_amount, $save_account );
			$wcsession->set( 'client_token', $client_token );
			$wcsession->set( 'total', $total + $tax_amount );
			$wcsession->set(
				'$transaction_api_id',
				substr(
					md5( uniqid( wp_rand(), true ) ),
					0,
					32
				)
			);
		}

		$wcsession->set( 'isBlocks', true );
		$client_token       = $wcsession->get( 'client_token' ) ?? '';
		$transaction_api_id = $wcsession->get( 'transaction_api_id' );

		if ( ! $client_token ) {
			$allowed_tags = array(
				'div' => array(),
				'br'  => array(),
			);

			echo wp_kses(
				'<div>Invalid settings for Fortis Gateway.
 <br/> Please check your configuration.</div>',
				$allowed_tags
			);

			return array();
		}

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

		$floatinglabels            = 'yes' === $this->settings[ FortisApi::FLOATINGLABELS ] ? 'true' : 'false';
		$view                      = 'yes' === $this->settings[ FortisApi::ACH ] ? 'default' : 'card-single-field';
		$show_validation_animation = 'yes' === $this->settings[ FortisApi::SHOWVALIDATIONANIMATION ] ? 'true' : 'false';
		$hide_agreement_checkbox   = 'yes' === $this->settings[ FortisApi::HIDEAGREEMENTCHECKBOX ] ? 'true' : 'false';

		$address  = $customer['address'];
		$city     = $customer['city'];
		$postcode = $customer['postcode'];
		$country  = $customer['country'];
		$state    = $customer['state'];

		return array(
			'title'                   => $this->get_setting( 'title' ),
			'description'             => $this->get_setting( 'description' ),
			'supports'                => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
			'client_token'            => $client_token,
			'order_id'                => "$order_id",
			'postcode'                => $postcode,
			'url'                     => $url,
			'floatingLabels'          => $floatinglabels,
			'view'                    => $view,
			'showValidationAnimation' => $show_validation_animation,
			'hideAgreementCheckbox'   => $hide_agreement_checkbox,
			'address'                 => $address,
			'city'                    => $city,
			'country'                 => $country,
			'state'                   => $state,
			'fortis'                  => $this->settings,
			'saveAccount'             => $save_account,
			'transaction_api_id'      => $transaction_api_id,
			'ajax_url'                => admin_url( 'admin-ajax.php' ),
		);
	}
}
