var elements = new Commerce.elements( data.clientToken );

elements.on(
	'ready',
	function (result) {
	}
);

elements.on(
	'done',
	function (result) {
		jQuery( '#add_payment_method #place_order' ).show();
		jQuery( '#add_payment_method #place_order' ).html( 'Click to save payment method' );

		document.getElementById( 'fortis_result' ).value = JSON.stringify( result );
	}
);

elements.on(
	'error',
	function (event) {
		document.getElementById( 'fortis_result' ).value = JSON.stringify( event );
	}
);

elements.create(
	{
		container: '#fortispayment', //Required
		theme: data.fortisSettings['theme'],
		environment: data.fortisSettings['environment'],
		floatingLabels: data.floatingLabels,
		showValidationAnimation: data.show_validation_animation,
		language: 'en-us',
		showReceipt: false,
		showSubmitButton: true,
		hideAgreementCheckbox: data.hide_agreement_checkbox,
		defaultCountry: 'US',
		appearance: {
			colorButtonSelectedBackground:
			data.fortisSettings['colorButtonSelectedBackground'],
			colorButtonSelectedText:
			data.fortisSettings['colorButtonSelectedText'],
			colorButtonActionBackground:
			data.fortisSettings['colorButtonActionBackground'],
			colorButtonActionText: data.fortisSettings['colorButtonActionText'],
			colorButtonBackground: data.fortisSettings['colorButtonBackground'],
			colorButtonText: data.fortisSettings['colorButtonText'],
			colorFieldBackground: data.fortisSettings['colorFieldBackground'],
			colorFieldBorder: data.fortisSettings['colorFieldBorder'],
			colorText: data.fortisSettings['colorText'],
			colorLink: data.fortisSettings['colorLink'],
			fontSize: data.fortisSettings['fontSize'],
			marginSpacing: data.fortisSettings['marginSpacing'],
			borderRadius: data.fortisSettings['borderRadius'],
		},
		fields: {
			billing: [
			{ name: 'address', value: '', required: true },
			{ name: 'city', value: '', required: true },
			{ name: 'postal_code', value: '', required: true },
			{ name: 'country', value: '', required: true },
			{ name: 'state', value: '', required: true }
			]
		}
	}
);
