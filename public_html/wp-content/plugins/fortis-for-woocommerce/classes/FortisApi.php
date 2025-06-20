<?php
/**
 * Fortis API implementation for WC_Payment_Gateway
 *
 * @package Fortis for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'FortisFrameworkApi.php';

/**
 * Fortis Payment Gateway - Fortis API
 *
 * Provides an API to Fortis
 *
 * @class       woocommerce_fortis
 * @package     WooCommerce
 */
class FortisApi {

	public const FORTIS_URL_SANDBOX    = 'https://api.sandbox.fortis.tech';
	public const FORTIS_URL_PRODUCTION = 'https://api.fortis.tech';
	public const CONTENT_TYPE          = 'Content-Type: application/json';

	public const WHITE        = '#ffffff';
	public const BLACK        = '#000000';
	public const COLOURBUTTON = '#0700ff';
	public const COLOURLINK   = '#0000ff';

	public const TEST_MODE = 'test_mode';
	public const VAULT     = 'vault';

	public const TOKENIZATION                  = 'Tokenization';
	public const ACH                           = 'ach';
	public const CC                            = 'cc';
	public const LEVEL3                        = 'level3';
	public const APPLEPAY                      = 'applepay';
	public const GOOGLEPAY                     = 'googlepay';
	public const ENVIRONMENT                   = 'environment';
	public const PRODUCTION_HOST_DOMAIN        = 'production_host_domain';
	public const PRODUCTION_USER_ID            = 'production_user_id';
	public const PRODUCTION_USER_API_KEY       = 'production_user_api_key';
	public const PRODUCTION_PRODUCT_ID_CC      = 'production_product_id_cc';
	public const PRODUCTION_PRODUCT_ID_ACH     = 'production_product_id_ach';
	public const PRODUCTION_LOCATION_ID        = 'production_location_id';
	public const SANDBOX_HOST_DOMAIN           = 'sandbox_host_domain';
	public const SANDBOX_USER_ID               = 'sandbox_user_id';
	public const SANDBOX_USER_API_KEY          = 'sandbox_user_api_key';
	public const SANDBOX_PRODUCT_ID_CC         = 'sandbox_product_id_cc';
	public const SANDBOX_PRODUCT_ID_ACH        = 'sandbox_product_id_ach';
	public const SANDBOX_LOCATION_ID           = 'sandbox_location_id';
	public const TRANSACTION_TYPE              = 'transaction_type';
	public const ACTION                        = 'action';
	public const THEME                         = 'theme';
	public const FLOATINGLABELS                = 'floatingLabels';
	public const SHOWVALIDATIONANIMATION       = 'showValidationAnimation';
	public const HIDEAGREEMENTCHECKBOX         = 'showValidationAnimation';
	public const COLORBUTTONSELECTEDTEXT       = 'colorButtonSelectedText';
	public const COLORBUTTONSELECTEDBACKGROUND = 'colorButtonSelectedBackground';
	public const COLORBUTTONACTIONBACKGROUND   = 'colorButtonActionBackground';
	public const COLORBUTTONACTIONTEXT         = 'colorButtonActionText';
	public const COLORBUTTONBACKGROUND         = 'colorButtonBackground';
	public const COLORBUTTONTEXT               = 'colorButtonText';
	public const COLORFIELDBACKGROUND          = 'colorFieldBackground';
	public const COLORFIELDBORDER              = 'colorFieldBorder';
	public const COLORTEXT                     = 'colorText';
	public const COLORLINK                     = 'colorLink';
	public const FONTSIZE                      = 'fontSize';
	public const MARGINSPACING                 = 'marginSpacing';
	public const BORDERRADIUS                  = 'borderRadius';

	/**
	 * User ID
	 *
	 * @var string User Id.
	 */
	public $user_id;
	/**
	 * Fortis API key
	 *
	 * @var string API key.
	 */
	public $user_api_key;
	/**
	 * Fortis Location Id
	 *
	 * @var string Location Id.
	 */
	public $location_id;
	/**
	 * Product Id for cards
	 *
	 * @var string Card Id.
	 */
	public $product_id_cc;
	/**
	 * Product Id for ACH
	 *
	 * @var string ACH Id.
	 */
	public $product_id_ach;
	/**
	 * Transaction type
	 *
	 * @var string Action.
	 */
	public $action;
	/**
	 * Id for framework 'fortis'
	 *
	 * @var string id.
	 */
	public $id;
	/**
	 * Decoded API response
	 *
	 * @var mixed result.
	 */
	public $result;
	/**
	 * Transaction type
	 *
	 * @var string Transaction Type.
	 */
	public $transaction_type;
	/**
	 * Payment method
	 *
	 * @var string Payment method.
	 */
	public $payment_method;
	/**
	 * Fortis Framework Api
	 *
	 * @var \FortisframeworkApi Api.
	 */
	public $framework;
	/**
	 * Access token
	 *
	 * @var string token.
	 */
	public $token;
	/**
	 * Merchant details
	 *
	 * @var object
	 */
	public $merchant_details;
	/**
	 * Transaction object.
	 *
	 * @var object transaction.
	 */
	public $transaction;
	/**
	 * Fortis Developer Id
	 *
	 * @var string Developer Id.
	 */
	private $developer_id;
	/**
	 * URL to Fortis API
	 *
	 * @var string url.
	 */
	private $fortis_url;

	/**
	 * Framework Id
	 *
	 * @param string $id fortis.
	 */
	public function __construct( $id ) {
		if ( '' !== $id ) {
			$this->id           = $id;
			$this->framework    = new FortisFrameworkApi( $id );
			$this->user_id      = $this->framework->get_user_id();
			$this->user_api_key = $this->framework->get_user_api_key();
			$this->location_id  = $this->framework->get_location_id();
			if ( $this->framework->get_ach_enabled() ) {
				$this->product_id_ach = $this->framework->get_product_id_ach();
			}
			if ( $this->framework->get_cc_enabled() ) {
				$this->product_id_cc = $this->framework->get_product_id_cc();
			}
			$this->action = $this->framework->get_action();
			if ( $this->framework->get_environment() === 'production' ) {
				$this->developer_id = $this->framework::DEVELOPER_ID_PRODUCTION;
				$this->fortis_url   = self::FORTIS_URL_PRODUCTION;
			} else {
				$this->developer_id = $this->framework::DEVELOPER_ID_SANDBOX;
				$this->fortis_url   = self::FORTIS_URL_SANDBOX;
			}
		}
	}

	/**
	 * Add level 3 data to transaction
	 *
	 * @param array $line_items Order line items.
	 *
	 * @return string
	 */
	public function add_level3( $line_items ) {
		$account_type   = $this->transaction->account_type;
		$transaction_id = $this->transaction->id;

		$intent_data                              = array();
		$intent_data['level3_data']               = array();
		$intent_data['level3_data']['line_items'] = array();

		$result = '';

		if ( 'visa' === $account_type ) {
			$intent_data['level3_data']['tax_amount'] = $this->transaction->tax;

			foreach ( $line_items as $line_item ) {
				$intent_data['level3_data']['line_items'][] = array(
					'description'    => (string) $line_item['description'],
					'commodity_code' => (string) $line_item['commodity_code'],
					'product_code'   => (string) $line_item['product_code'],
					'unit_code'      => (string) $line_item['unit_code'],
					'unit_cost'      => (int) $line_item['unit_cost'],
				);
			}

			$result = $this->post( $intent_data, '/v1/transactions/' . $transaction_id . '/level3/visa' );
		}

		if ( 'mc' === $account_type ) {
			$intent_data['level3_data']['tax_amount'] = $this->transaction->tax;
			foreach ( $line_items as $line_item ) {
				$intent_data['level3_data']['line_items'][] = array(
					'description'  => (string) $line_item['description'],
					'product_code' => (string) $line_item['product_code'],
					'unit_code'    => (string) $line_item['unit_code'],
					'unit_cost'    => (int) $line_item['unit_cost'],
				);

				$result = $this->post(
					$intent_data,
					'/v1/transactions/' . $transaction_id . '/level3/master-card'
				);
			}
		}

		return $result;
	}

	/**
	 * Get token for transaction
	 *
	 * @param int|float $total Order total amount.
	 * @param int|float $tax_amount Order tax amount.
	 * @param bool      $save_account Should save account.
	 *
	 * @return string
	 */
	public function get_client_token(
		$total,
		$tax_amount,
		$save_account
	) {
		$intent_data = array(
			'action'       => $this->action,
			'amount'       => (int) $total,
			'location_id'  => $this->location_id,
			'save_account' => $save_account,

		);

		if ( $tax_amount > 0 ) {
			$intent_data['tax_amount'] = (int) $tax_amount;
		}
		$intent_data['methods'] = array();

		if ( isset( $this->product_id_cc ) && $this->product_id_cc ) {
			$intent_data['methods'][] = array(
				'type'                   => 'cc',
				'product_transaction_id' => $this->product_id_cc,
			);
		}
		if ( isset( $this->product_id_ach ) && $this->product_id_ach ) {
			$intent_data['methods'][] = array(
				'type'                   => 'ach',
				'product_transaction_id' => $this->product_id_ach,
			);
		}

		$response = json_decode( $this->post( $intent_data, '/v1/elements/transaction/intention' ) );

		if ( ! isset( $response->data->client_token ) ) {
			return '';
		} else {
			return $response->data->client_token;
		}
	}

	/**
	 * Get token for transaction
	 *
	 * @return string
	 */
	public function get_payment_method_token() {
		$intent_data = array(
			'action'       => 'avs-only',
			'location_id'  => $this->location_id,
			'save_account' => true,
		);

		$intent_data['methods'] = array();

		if ( $this->product_id_cc ) {
			$intent_data['methods'][] = array(
				'type'                   => 'cc',
				'product_transaction_id' => $this->product_id_cc,
			);
		}
		if ( isset( $this->product_id_ach ) ) {
			$intent_data['methods'][] = array(
				'type'                   => 'ach',
				'product_transaction_id' => $this->product_id_ach,
			);
		}

		$response = json_decode( $this->post( $intent_data, '/v1/elements/transaction/intention' ) );
		if ( null === $response->data->client_token ) {
			return '';
		} else {
			return $response->data->client_token;
		}
	}

	/**
	 * Get Fortis transaction detail
	 *
	 * @param string $transaction_id Transaction Id.
	 *
	 * @return string
	 */
	public function get_transaction( $transaction_id ) {
		return $this->get( array(), "/v1/transactions/$transaction_id" );
	}

	/**
	 * Process the transaction
	 *
	 * @param array      $post POST variables.
	 * @param float|int  $transaction_amount Transaction amount.
	 * @param int        $customer_id Customer Id.
	 * @param float|int  $tax_amount Transaction tax amount.
	 * @param int|string $order_id Order id.
	 *
	 * @return int|string
	 */
	public function process_transaction(
		$post,
		$transaction_amount,
		$customer_id,
		$tax_amount,
		$order_id
	) {
		$this->result           = json_decode( wp_unslash( sanitize_text_field( $post['fortis_result'] ) ) );
		$this->transaction      = $this->result->data ?? null;
		$saved_account          = $this->transaction->saved_account ?? null;
		$use_saved_account      = isset( $post['fortis_useSavedAccount'] ) && 'on' === $post['fortis_useSavedAccount'];
		$save_account           = isset( $post['fortis_SaveAccount'] ) && 'on' === $post['fortis_SaveAccount'];
		$token_id               = $saved_account->id ?? null;
		$this->transaction_type = $this->get_transaction_type( $this->transaction );

		if ( isset( $this->transaction ) ) {
			$this->payment_method = $this->transaction->payment_method;
		}

		if ( $use_saved_account && isset( $post['CC'] ) ) {
			$token_id = $post['CC'];
		}

		$status = 2;
		if ( isset( $this->transaction->id ) || isset( $token_id ) ) {
			if ( 'error' === $this->transaction_type ) {
				$status = 3;
			} elseif ( $use_saved_account ) {
				$status = $this->do_tokenised_transaction( $transaction_amount, $token_id, $tax_amount, $order_id );
			} elseif ( $this->transaction ) {
				if ( $this->framework->vault_enabled() && $save_account && null !== $token_id ) {
					$this->framework->vault_card( $token_id, $saved_account, $customer_id );
				}
				if ( 'sale' === $this->transaction_type || 'auth-only' === $this->transaction_type ) {
					$status = $this->check_status( $this->transaction->status_code );
				}
			}
		}

		if ( 'ach' === $this->payment_method && 1 === $status ) {
			$status = 5;
		}

		return $status;
	}

	/**
	 * Get transaction type
	 *
	 * @param object $transaction Fortis transaction.
	 *
	 * @return string|null
	 */
	public function get_transaction_type( $transaction ) {
		$this->transaction = $transaction;
		$this->action      = $this->transaction->{'@action'} ?? '';

		return $this->transaction->type ?? $this->action;
	}

	/**
	 * Make tokenised transaction to endpoint
	 *
	 * @param int|float  $transaction_amount Amount of transaction.
	 * @param int        $token_id WC_Payment_Token Id.
	 * @param int|float  $tax_amount Transaction tax.
	 * @param int|string $order_id Order Id.
	 *
	 * @return string
	 */
	public function do_tokenised_transaction( $transaction_amount, $token_id, $tax_amount, $order_id ) {
		$token = $this->framework->get_token_by_id( $token_id );

		$intent_data = array(
			'subtotal_amount'    => (int) $transaction_amount - (int) $tax_amount,
			'tax'                => (int) $tax_amount,
			'transaction_amount' => (int) $transaction_amount,
			'token_id'           => $token,
			'description'        => (string) $order_id,
			'transaction_api_id' => substr( md5( uniqid( wp_rand(), true ) ), 0, 32 ),
		);

		if ( $this->product_id_cc && 'ach' !== $this->payment_method ) {
			$intent_data['product_transaction_id'] = $this->product_id_cc;
		}

		if ( isset( $this->product_id_ach ) && 'ach' === $this->payment_method ) {
			$intent_data['product_transaction_id'] = $this->product_id_ach;
		}

		if ( ! $this->action ) {
			$this->action = $this->framework->get_action();
		}

		if ( 'sale' === $this->action ) {
			$this->transaction_type = 'sale';
			if ( 'ach' === $this->payment_method ) {
				$transaction_result   = $this->post( $intent_data, '/v1/transactions/ach/debit/token' );
				$this->payment_method = 'ach';
			} else {
				$transaction_result   = $this->post( $intent_data, '/v1/transactions/cc/sale/token' );
				$this->payment_method = 'token';
			}
		} else {
			$this->transaction_type = 'auth-only';
			if ( 'ach' === $this->payment_method ) {
				$transaction_result   = $this->post( $intent_data, '/v1/transactions/ach/auth-only/token' );
				$this->payment_method = 'ach';
			} else {
				$transaction_result   = $this->post( $intent_data, '/v1/transactions/cc/auth-only/token' );
				$this->payment_method = 'token';
			}
		}

		$this->result      = json_decode( wp_unslash( $transaction_result ) );
		$this->transaction = $this->result->data ?? null;
		FortisFrameworkApi::log_info( 'doTokenisedTransaction - transaction', array( $this->transaction ) );
		if ( $this->transaction->id ) {
			$status = $this->check_status( $this->transaction->status_code );
		} else {
			$status = '2';
		}

		return $status;
	}

	/**
	 * Do refund
	 *
	 * @param string     $transaction_id Transaction Id.
	 * @param float|int  $transaction_amount Transaction amount.
	 * @param float|int  $tax_amount Transaction tax amount.
	 * @param int|string $order_id Order Id.
	 *
	 * @return int|string
	 */
	public function refund(
		$transaction_id,
		$transaction_amount,
		$tax_amount,
		$order_id
	) {
		$status = '3';

		if ( '' !== $transaction_id ) {
			$intent_data = array(
				'previous_transaction_id' => $transaction_id,
				'transaction_amount'      => $transaction_amount,
				'description'             => (string) $order_id,
				'tax'                     => $tax_amount,
			);

			if ( 'ach' === $this->payment_method ) {
				$this->result = $this->post( $intent_data, '/v1/transactions/ach/refund/prev-trxn' );
			} else {
				$this->result = $this->post( $intent_data, '/v1/transactions/cc/refund/prev-trxn' );
			}

			$this->transaction = json_decode( $this->result );
			if ( 'ach' === $this->payment_method ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					update_post_meta( $order->get_id(), 'ach_refund_initiated', true );
					$order->update_meta_data( 'ach_refund_initiated', true );
					$ach_refund_amount = $order->get_meta( 'ach_refund_amount' ) ?? 0;
					$order->update_meta_data(
						'ach_refund_amount',
						(int) $ach_refund_amount + (int) $transaction_amount
					);
					update_post_meta(
						$order->get_id(),
						'ach_refund_amount',
						(int) $ach_refund_amount + (int) $transaction_amount
					);
					$ach_refund_ids = $order->get_meta( 'ach_refund_ids' );
					if ( '' === $ach_refund_ids ) {
						$ach_refund_ids = array();
					} else {
						$ach_refund_ids = json_decode( $ach_refund_ids );
					}
					$ach_refund_ids[] = $this->transaction->data->id;
					$order->update_meta_data( 'ach_refund_ids', wp_json_encode( $ach_refund_ids ) );
					update_post_meta( $order->get_id(), 'ach_refund_ids', wp_json_encode( $ach_refund_ids ) );
				}
			}

			if ( 'transaction' === strtolower( $this->transaction->type ) ) {
				$status = $this->check_status( $this->transaction->data->status_code );
			}
		}

		return $status;
	}

	/**
	 * Do Completion of Auth
	 *
	 * @param string     $transaction_id Transaction Id.
	 * @param float|int  $transaction_amount Transaction amount.
	 * @param int|string $order_id Order Id.
	 *
	 * @return int|string
	 */
	public function completeAuth(
		$transaction_id,
		$transaction_amount,
		$order_id
	): int {
		$intent_data = array(
			'transaction_amount' => $transaction_amount,
			'order_number'       => (string) $order_id,
		);
		$result      = $this->patch( $intent_data, '/v1/transactions/' . $transaction_id . '/auth-complete' );
		$result      = json_decode( $result );
		$transaction = $result->data ?? null;
		$status      = 3;
		if ( isset( $transaction->status_code ) ) {
			$status = $this->check_status( $transaction->status_code );
		}

		return $status;
	}

	/**
	 * Create the ACH postback webhook
	 *
	 * @param string $url Url.
	 *
	 * @return string
	 */
	public function create_ach_postback( $url ) {
		$intent_data = array(
			'is_active'              => 'true',
			'on_create'              => 'true',
			'on_update'              => 'true',
			'on_delete'              => 'true',
			'location_id'            => $this->location_id,
			'product_transaction_id' => (string) $this->transaction->product_transaction_id,
			'url'                    => (string) $url,
			'number_of_attempts'     => 1,
		);

		return $this->create_transaction_postback( $intent_data );
	}

	/**
	 * Do actual create call
	 *
	 * @param array $intent_data Intent data.
	 *
	 * @return string
	 */
	public function create_transaction_postback( $intent_data ) {
		return $this->post( $intent_data, '/v1/webhooks/transaction' );
	}

	/**
	 * Do CC token update
	 *
	 * @param array  $intent_data Intent data.
	 * @param string $token_id Id of token.
	 *
	 * @return string
	 */
	public function token_cc_update( $intent_data, $token_id ) {
		return $this->patch( $intent_data, "/v1/tokens/$token_id/cc" );
	}

	/**
	 * Delete CC token
	 *
	 * @param string $token_id Id of token.
	 *
	 * @return string
	 */
	public function token_cc_delete( $token_id ) {
		return $this->delete( array(), "/v1/tokens/$token_id" );
	}

	/**
	 * List CC tokens
	 *
	 * @param array $intent_data Intent data.
	 * @param array $filter Filter conditions.
	 *
	 * @return string
	 */
	public function token_cc_list( $intent_data, $filter ) {
		$filter_string = '';
		if ( $filter ) {
			$filter_string = '/endpoint?filter=' . wp_json_encode( $filter );
		}

		return $this->get( $intent_data, '/v1/tokens' . $filter_string );
	}

	/**
	 * Translate the status code
	 *
	 * @param int $status_code Returned status code.
	 *
	 * @return int
	 */
	public function check_status( $status_code ) {
		switch ( $status_code ) {
			case 101: // Sale.
				$status = 1;
				break;
			case 102: // Authonly.
				$status = 1;
				break;
			case 111: // Refund cc Refunded.
				$status = 1;
				break;
			case 201: // Voided.
				$status = 2;
				break;
			case 301: // Declined.
				$status = 2;
				break;
			case 331: // Charged back.
				$status = 2;
				break;
			case 131: // Pending Origination.
				$status = 1;
				break;
			case 132: // Originating.
				$status = 1;
				break;
			case 133: // Originated.
				$status = 1;
				break;
			case 134: // Settled.
				$status = 1;
				break;

			default:
				$status = 3;
				break;
		}

		return $status;
	}

	/**
	 * Make API call
	 *
	 * @param string $send_type "POST","DELETE',"GET","PATCH".
	 * @param array  $intent_data Intent data.
	 * @param string $end_point Endpoint.
	 *
	 * @return string
	 */
	private function call_api( $send_type, $intent_data, $end_point ) {
		$url = $this->fortis_url . $end_point;

		$args = array(
			'method'      => $send_type,
			'timeout'     => 30,
			'redirection' => 10,
			'httpversion' => '1.1',
			'headers'     => array(
				'Content-Type' => 'application/json',
				'user-id'      => $this->user_id,
				'user-api-key' => $this->user_api_key,
				'developer-id' => $this->developer_id,
			),
			'body'        => wp_json_encode( $intent_data ),
		);

		$cnt            = 0;
		$intent_created = false;
		$http_error     = null;
		$response       = null;

		while ( ! $intent_created && $cnt < 5 ) {
			$response      = wp_remote_request( $url, $args );
			$response_code = wp_remote_retrieve_response_code( $response );

			$http_error = '';
			if ( 200 !== $response_code && 201 !== $response_code ) {
				try {
					if ( is_array( $response ) ) {
						$res        = json_decode( $response['body'] );
						$http_error = "$res->title: $res->detail";
					} elseif ( get_class( $response ) === 'WP_Error' ) {
						$http_error = $response->errors['http_request_failed'][0];
					}
				} catch ( \Exception $e ) {
					$http_error = $e->getMessage();
				}
				++$cnt;
				continue;
			}

			$intent_created = true;
		}

		if ( ! $intent_created ) {
			FortisFrameworkApi::log_error( 'FortisApi->callAPI', $http_error );

			return $http_error;
		}

		return $response['body'];
	}

	/**
	 * Post helper
	 *
	 * @param array  $intent_data Intent data.
	 * @param string $end_point Endpoint.
	 *
	 * @return string
	 */
	private function post( $intent_data, $end_point ) {
		return $this->call_api( 'POST', $intent_data, $end_point );
	}

	/**
	 * Get helper
	 *
	 * @param array  $intent_data Intent data.
	 * @param string $end_point Endpoint.
	 *
	 * @return string
	 */
	private function get( $intent_data, $end_point ) {
		return $this->call_api( 'GET', $intent_data, $end_point );
	}

	/**
	 * Patch helper
	 *
	 * @param array  $intent_data Intent data.
	 * @param string $end_point Endpoint.
	 *
	 * @return string
	 */
	private function patch( $intent_data, $end_point ) {
		return $this->call_api( 'PATCH', $intent_data, $end_point );
	}

	/**
	 * Put helper
	 *
	 * @param array  $intent_data Intent data.
	 * @param string $end_point Endpoint.
	 *
	 * @return string
	 */
	private function put( $intent_data, $end_point ) {
		return $this->call_api( 'PUT', $intent_data, $end_point );
	}

	/**
	 * Delete helper
	 *
	 * @param array  $intent_data Intent data.
	 * @param string $end_point Endpoint.
	 *
	 * @return string
	 */
	private function delete( $intent_data, $end_point ) {
		return $this->call_api( 'DELETE', $intent_data, $end_point );
	}
}
