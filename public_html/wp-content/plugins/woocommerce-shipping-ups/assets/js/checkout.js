/**
 * Frontend JS
 *
 * @package WooCommerce_Shipping_UPS
 */

( function () {

	// We need our localized data to be able to run this script.
	if ( ! wc_ups_checkout_params ) {
		return;
	}

	// Handle clicking the suggested address.
	document.addEventListener(
		'click',
		function ( e ) {

			if ( ! e.target.classList.contains( 'ups_apply_suggested_address' ) ) {
				return;
			}

			e.preventDefault();

			const button               = e.target;
			const suggestedAddressJSON = button.getAttribute( 'data-suggested_address' );

			if ( ! suggestedAddressJSON ) {
				return;
			}

			// Change the button text to indicate that the address is being applied.
			button.innerHTML = wc_ups_checkout_params.strings.button_apply_address;

			// Handle classic checkout.
			if ( '1' !== wc_ups_checkout_params.is_wc_block_checkout ) {

				const suggestedAddressObject = JSON.parse( suggestedAddressJSON );

				// Loop through suggested address object and apply the values to the corresponding inputs.
				Object.entries( suggestedAddressObject ).forEach(
					function ( [ key, value ] ) {
						const isShipToDifferentAddressChecked = document.getElementById( 'ship-to-different-address-checkbox' ).checked;
						const inputSelector = isShipToDifferentAddressChecked ? 'shipping_' + key : key;
						const input = document.querySelector( '[id$="' + inputSelector + '"]' );

						if ( input ) {
							input.value = value;

							// If the target is a select2 field, trigger the change event.
							if ( input.classList.contains( 'select2-hidden-accessible' ) ) {
								const event = new Event( 'change', { bubbles: true } );
								input.dispatchEvent( event );
							}
						}
					}
				);

				return;
			}

			// Handle block checkout.
			window.wc.blocksCheckout.extensionCartUpdate(
				{
					namespace: wc_ups_checkout_params.store_api_namespace,
					data: {
						action: 'apply_suggested_shipping_address',
						suggested_address: suggestedAddressJSON,
						use_shipping_as_billing: window.wp.data.select( window.wc.wcBlocksData.CHECKOUT_STORE_KEY ).getUseShippingAsBilling()
					},
				}
			);

		}
	);
} )();