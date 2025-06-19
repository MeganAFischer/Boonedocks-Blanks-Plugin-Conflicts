jQuery(document).ready(function ($) {
  $('.complete-auth-button').on('click', function (e) {
				e.preventDefault();

				let orderId = $( this ).data( 'order-id' );
				let button  = $( this );

				// Disable the button and add a loading spinner
				button.prop( 'disabled', true ).html( '<span></span> Completing...' );

				// Ajax request to mark order as complete
    $.ajax({
						url: completeauth.ajax_url,
						type: 'POST',
						data: {
							action: 'complete_auth_action',
							order_id: orderId,
							nonce: completeauth.nonce
						},
						success: function (response) {
							const message = response.data && response.data.message ? response.data.message : 'An error occurred.';

							if (response.success) {
								console.log( "Complete Auth Success" );
								$( '<br /><div style="text-align: left;" class="notice notice-success"><p>' + message + '</p></div>' )
								.insertAfter( button )
								.delay( 8000 )
								.fadeOut();
								location.reload(); // Reload the page to reflect changes
							} else {
								console.log( "Complete Auth Failed" );
								$( '<br /><div style="text-align: left;" class="notice notice-error"><p>' + message + '</p></div>' )
								.insertAfter( button )
								.delay( 8000 )
								.fadeOut();
							}
							button.prop( 'disabled', false ).html( '<span></span> Complete Auth' );
						},
						error: function (jqXHR, textStatus, errorThrown) {
							console.error( 'AJAX Error:', textStatus, errorThrown );
							alert( 'There was an error processing the request.' );
						}
    });
  });
});
