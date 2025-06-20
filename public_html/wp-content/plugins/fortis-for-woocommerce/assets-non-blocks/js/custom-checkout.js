/**
 * Generates custom checkout functionality
 *
 * @package Fortis for WooCommerce
 */

const fortisIFrameContainer = jQuery('#fortisIFrameContainer');
let iFrameReady = false;
function wooCommerceValidation () {
  jQuery('.woocommerce-notices-wrapper').html('');
  let valid = true;
  jQuery.ajax(
    {
      async: false,
      type: 'post',
      data: jQuery('.checkout').serialize(),
      url: '?wc-ajax=checkout',
      success: function (response) {
        if (response['result'] === 'failure') {
          valid = false;
          jQuery('.woocommerce-notices-wrapper:last').append(response['messages']);
          window.scrollTo(0, findPosition(jQuery('.woocommerce-notices-wrapper')[0]));
        }
      }
    }
  );
  return valid;
}

function relocateOrderButton (isFortis) {
  const paymentMethod = document.querySelector('input[name="payment_method"]:checked');

  const orderBtn = document.getElementById('place_order');

  if (isFortis) {
    paymentMethod.closest('.wc_payment_method').appendChild(orderBtn);
    orderBtn.style.width = '100%';
  } else {
    document.getElementsByClassName('place-order')[0].appendChild(orderBtn);
    orderBtn.style.width = '';
  }
}

function findPosition (obj) {
  let currenttop = 0;
  if (obj.offsetParent) {
    do {
      currenttop += obj.offsetTop;
    } while ((obj = obj.offsetParent));
    return [currenttop];
  }
}

jQuery( document ).ajaxComplete(
	function (event, xhr, settings) {
		// Check if the AJAX call is related to WooCommerce update order review.
		let checkoutUpdated = settings.url.indexOf( 'update_order_review' );

		if (iFrameReady && checkoutUpdated !== -1) {
			jQuery( "#fortispayment" ).hide();
			jQuery( "#place_order" ).show();
      if (document.querySelector('#payment_method_fortis:checked')) {
        relocateOrderButton(true);
      }
      applyPlaceOrderListener();
		} else if (checkoutUpdated !== -1) {
      applyPlaceOrderListener()
      // Call the function when the payment method is changed.
      document.addEventListener(
        'change',
        function (event) {
          const target = event.target;
          if (target && target.matches('input[id="payment_method_fortis"]')) {
            relocateOrderButton(true);
          } else if (target && target.matches('input[name="payment_method"]')) {
            relocateOrderButton(false);
          }
        }
        );
      if (document.querySelector('#payment_method_fortis:checked')) {
        relocateOrderButton(true);
      }
    }
  }
);

function applyPlaceOrderListener() {
  jQuery('#place_order').click(
    function (e) {
      if (document.querySelector('#payment_method_fortis:checked')) {

        e.preventDefault();
        if (wooCommerceValidation() === true) {
          jQuery('#place_order').hide();
          fortisIFrameContainer.append('<div>Loading...</div>');
          jQuery.ajax(
            {
              url: '/wp-admin/admin-ajax.php',
              async: false,
              type: 'post',
              data: {
                'action': 'get_billing_data',
                'fields': jQuery('.checkout').serialize()
              },
              success: function (response) {
                const billingData = JSON.parse(response);
                generateFortisIFrame(billingData);
              },
            }
          );
        }
      }
    }
  );
}

function generateFortisIFrame (billingData) {
  document.getElementById('fortis_detail_form').style.display = 'block';
  // Get latest passed clientToken from AJAX
  const dataClientTokenDiv = document.getElementById('dataClientToken');
  // If passed clientToken exists, update the localized data object
  if (dataClientTokenDiv && dataClientTokenDiv.dataset && dataClientTokenDiv.dataset.id) {
    const dataId = dataClientTokenDiv.dataset.id;
    data.clientToken = dataId;
  }

  var elements = new Commerce.elements(data.clientToken);

  elements.on(
    'ready',
    function (result) {
      iFrameReady = true;
      document.getElementById('billing_postcode')?.addEventListener(
        'change',
        (event) => {
          jQuery('body').trigger('update_checkout');
        }
      );
    }
  );

  elements.on(
    'done',
    function (result) {
      document.getElementById('fortis_result').value = JSON.stringify(result);
      document.getElementById('fortis_detail_form').style.display = 'none';
      document.getElementById('fortis_detail_form').submit();
    }
  );

  elements.on(
    'error',
    function (event) {
      document.getElementById('fortis_result').value = JSON.stringify(event);
    }
  );

  elements.create(
    {
      container: '#fortispayment', //Required
      theme: data.iframeConfig['theme'],
      environment: data.iframeConfig['environment'],
      floatingLabels: data.floatingLabels,
      showValidationAnimation: data.show_validation_animation,
      language: 'en-us',
      showReceipt: false,
      showSubmitButton: true,
      hideAgreementCheckbox: data.hide_agreement_checkbox,
      defaultCountry: 'US',
      view: data.view,
      digitalWallets: data.digitalWallets,
      appearance: {
        colorButtonSelectedBackground:
          data.iframeConfig['colorButtonSelectedBackground'],
        colorButtonSelectedText:
          data.iframeConfig['colorButtonSelectedText'],
        colorButtonActionBackground:
          data.iframeConfig['colorButtonActionBackground'],
        colorButtonActionText: data.iframeConfig['colorButtonActionText'],
        colorButtonBackground: data.iframeConfig['colorButtonBackground'],
        colorButtonText: data.iframeConfig['colorButtonText'],
        colorFieldBackground: data.iframeConfig['colorFieldBackground'],
        colorFieldBorder: data.iframeConfig['colorFieldBorder'],
        colorText: data.iframeConfig['colorText'],
        colorLink: data.iframeConfig['colorLink'],
        fontSize: data.iframeConfig['fontSize'],
        marginSpacing: data.iframeConfig['marginSpacing'],
        borderRadius: data.iframeConfig['borderRadius'],
      },
      fields: {
        additional: [
          { name: 'description', required: true, value: billingData.orderID, hidden: true },
          { name: 'transaction_api_id', hidden: true, value: data.transaction_api_id },
        ],
        billing: [
          { name: 'address', value: billingData.address, required: true, hidden: true },
          { name: 'city', value: billingData.city, required: true, hidden: true },
          { name: 'postal_code', value: billingData.postalCode, required: true, hidden: true },
          { name: 'country', value: billingData.country, required: true, hidden: true },
          { name: 'state', value: billingData.state, required: true, hidden: true }
        ]
      }
    }
  );

  const fortisVaultingBtn = document.getElementById('fortis_useSaved');

  if (fortisVaultingBtn) {
    fortisVaultingBtn.addEventListener(
      'click',
      function (e) {
        saveNow(e.target);
      }
    );
  }

  document.getElementById('fortis_detail_form_container').style.display = 'block';

  jQuery('#fortispayment').show();
  jQuery('#place_order').hide();
  jQuery('.wc_payment_methods li').hide();
  jQuery('.payment_method_fortis').show();
}

function saveNow (button) {
  button.disabled = true;
  if (wooCommerceValidation() === true) {
    document.getElementById('fortis_useSavedAccount').value = 'on';
    document.getElementById('fortis_detail_form').submit();
  } else {
    button.disabled = false;
  }
}
