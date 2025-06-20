<?php // phpcs:ignore
/**
 * Snapshot models: Dropbox destination requests model
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Model\Request\Destination;

use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper\Api;

/**
 * Destination requests model class
 */
class Dropbox extends Model\Request\Destination {

	/**
	 * Sends auth code to retrieve access tokens for Dropbox destination.
	 *
	 * @param array $data Config data.
	 *
	 * @return array|mixed|object tokens for that dropbox account if auth flow was successful.
	 */
	public function generate_tokens( $data ) {
		$method         = 'post';
		$this->endpoint = 'generatetoken';
		$path           = $this->get_api_url();

		$response = $this->request( $path, $data, $method );

		return $response;
	}

	/**
	 * Creates Oauth link.
	 *
	 * @var array $data Additional data to be inject for reauthorization
	 *
	 * @return string Dropbox oauth link.
	 */
	public static function create_oauth_link( array $args = array() ) {
		$site_hash = hash_hmac( 'sha256', untrailingslashit( network_site_url() ), Api::get_api_key() );

		$data = array(
			'page'                   => 'snapshot-destinations',
			'snapshot_action'        => 'dropbox-auth',
			'snapshot_site_id'       => Api::get_site_id(),
			'snapshot_site_hash'     => $site_hash,
			'snapshot_dropbox_nonce' => wp_create_nonce( 'snapshot_dropbox_connection' ),
		);

		if ( ! empty( $args ) && isset( $args['tpd_id'] ) ) {
			$data = array_merge( $data, $args );
		}

		$redirect_after_login = add_query_arg(
			$data,
			network_admin_url( 'admin.php' )
		);

		$params = array(
			'client_id'         => defined( 'SNAPSHOT_DROPBOX_APP_ID' ) ? SNAPSHOT_DROPBOX_APP_ID : 'atco25zpknnbtly',
			'token_access_type' => 'offline',
			'response_type'     => 'code',
			'state'             => rawurlencode( $redirect_after_login ),
			'redirect_uri'      => defined( 'SNAPSHOT_DROPBOX_REDIRECT_URI' ) ? rawurlencode( SNAPSHOT_DROPBOX_REDIRECT_URI ) : '#',
			'force_reapprove'   => 'true',
		);

		$auth_url = add_query_arg( $params, 'https://www.dropbox.com/oauth2/authorize' );

		return filter_var( $auth_url, FILTER_SANITIZE_URL );
	}
}