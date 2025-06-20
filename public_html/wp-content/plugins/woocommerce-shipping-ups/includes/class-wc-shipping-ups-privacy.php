<?php
/**
 * Class WC_Shipping_UPS_Privacy
 *
 * @package WooCommerce\Shipping\UPS
 */

if ( ! class_exists( 'WC_Abstract_Privacy' ) ) {
	return;
}

/**
 * UPS Shipping privacy class.
 */
class WC_Shipping_UPS_Privacy extends WC_Abstract_Privacy {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( __( 'UPS', 'woocommerce-shipping-ups' ) );
	}

	/**
	 * Gets the message of the privacy to display.
	 */
	public function get_privacy_message(): string {
		return wpautop(
			sprintf(
			/* translators: %s: URL to the UPS privacy policy. */
				__( 'By using this extension, you may be storing personal data or sharing data with an external service. <a href="%s" target="_blank">Learn more about how this works, including what you may want to include in your privacy policy.</a>', 'woocommerce-shipping-ups' ),
				'https://docs.woocommerce.com/document/privacy-shipping/#woocommerce-shipping-ups'
			)
		);
	}
}

new WC_Shipping_UPS_Privacy();
