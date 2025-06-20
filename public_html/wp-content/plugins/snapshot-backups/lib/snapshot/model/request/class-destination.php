<?php // phpcs:ignore
/**
 * Snapshot models: Destination backup requests model
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Model\Request;

use WPMUDEV\Snapshot4\Model;

/**
 * Destination requests model class
 */
class Destination extends Model\Request {
	const DEFAULT_ERROR = 'snapshot_destination_service_unreachable';

	/**
	 * Destination request endpoint
	 *
	 * @var string
	 */
	protected $endpoint = 'tpd_creds';

	/**
	 * Returns action string for logger
	 *
	 * @return string
	 */
	protected function get_action_string() {
		return __( 'configure destination', 'snapshot' );
	}

	/**
	 * Test connection bucket and potentially store it system-side.
	 *
	 * @param array $data Config data.
	 *
	 * @return array|mixed|object array of sent info if success.
	 */
	public function test_connection_final( $data ) {
		$method = 'post';
		$path   = $this->get_api_url();

		if ( isset( $data['obfuscated'] ) && 'yes' === $data['obfuscated'] ) {
			$change_key = false;
			$access_key = isset( $data['tpd_accesskey'] ) ? $data['tpd_accesskey'] : null;
			$secret_key = isset( $data['tpd_secretkey'] ) ? $data['tpd_secretkey'] : null;

			if ( null === $access_key || '' === $access_key ) {
				$access_key = $data['tpd_acctoken_gdrive'];
				$change_key = true;
			}

			$decoded_key    = snapshot_decrypt_data( $access_key );
			$decoded_secret = snapshot_decrypt_data( $secret_key );

			if ( $change_key ) {
				$data['tpd_acctoken_gdrive'] = $decoded_key;
			} else {
				$data['tpd_accesskey'] = $decoded_key;
			}

			$data['tpd_secretkey'] = $decoded_secret;

			unset( $data['obfuscated'] );
		}

		$response = $this->request( $path, $data, $method );

		return $response;
	}

	/**
	 * Request list of remote destinations.
	 *
	 * @return array|mixed|object array of sent info if success.
	 */
	public function get_destinations() {
		return $this->request( $this->get_api_url(), array(), 'get' );
	}

	/**
	 * Delete remote destination.
	 *
	 * @param string $tpd_id Destination ID.
	 */
	public function delete_destination( $tpd_id ) {
		$method = 'delete';
		$path   = $this->get_api_url() . '/' . $tpd_id;
		$data   = array();

		$response = $this->request( $path, $data, $method );

		return $response;
	}

	/**
	 * Update destination.
	 *
	 * @param string $tpd_id        Destination ID.
	 * @param string $tpd_accesskey Access Key.
	 * @param string $tpd_secretkey Secret Key.
	 * @param string $tpd_region    Region.
	 * @param string $tpd_path      Path, created by the selected bucket plus any additional chosen dir inside the bucket.
	 * @param string $tpd_name      Name to be assigned to the stored destination, chosen by the user.
	 * @param int    $tpd_limit     Number of backups to be kept in the destination before rotating.
	 * @param int    $tpd_type      Type of provider (aws, backblaze, etc).
	 * @param string $tpd_endpoint  Provider's endpoint or more specifically complete URL.
	 * @param array  $meta          Meta for FTP destinations.
	 * @param string $drive_id      OneDrive root Drive.id.
	 * @param string $item_id       OneDrive root DriveItem.id.
	 *
	 * @return mixed array of sent info if success.
	 */
	public function update_destination(
		$tpd_id,
		$tpd_accesskey,
		$tpd_secretkey,
		$tpd_region,
		$tpd_path,
		$tpd_name,
		$tpd_limit,
		$tpd_type,
		$tpd_endpoint = '',
		$meta = array(),
		$drive_id = null,
		$item_id = null
	) {
		$method = 'put';
		$path   = $this->get_api_url() . '/' . $tpd_id;

		$data = array(
			'tpd_accesskey' => $tpd_accesskey,
			'tpd_secretkey' => $tpd_secretkey,
			'tpd_region'    => $tpd_region,
			'tpd_path'      => $tpd_path,
			'tpd_name'      => $tpd_name,
			'tpd_limit'     => $tpd_limit,
			'tpd_type'      => $tpd_type,
			'tpd_save'      => 1,
		);

		if ( 'dropbox' === $tpd_type ) {
			$data['tpd_acctoken_gdrive'] = $data['tpd_accesskey'];
			unset( $data['tpd_accesskey'] );
		}

		if ( ! empty( $tpd_endpoint ) ) {
			$data['tpd_endpoint'] = $tpd_endpoint;
		}

		if ( 'linode' === $tpd_type ) {
			$data['ftp_host'] = 'linode';
			$data['tpd_type'] = 's3_other';
		}

		if ( isset( $meta['ftp_timeout'] ) ) {
			$data['ftp_timeout'] = $meta['ftp_timeout'];
			$data['ftp_mode']    = $meta['ftp_mode'];
		}

		$response = $this->request( $path, $data, $method );

		return $response;
	}

	/**
	 * Update destination.
	 *
	 * @param string $tpd_id  Destination ID.
	 * @param array  $data    Array of data to update the destination
	 *
	 * @return array|mixed|object array of sent info if success.
	 */
	public function activate_destination( $tpd_id, $data ) {
		$method = 'put';
		$path   = $this->get_api_url() . '/' . $tpd_id;

		$args = array(
			'aws_storage' => $data['aws_storage'],
		);

		if ( isset( $data['tpd_type'] ) && 'linode' === $data['tpd_type'] ) {
			$args['tpd_type'] = 's3_other';
			$args['ftp_host'] = 'linode';
		}

		$response = $this->request( $path, $args, $method );

		return $response;
	}

	/**
	 * Delete ALL destinations.
	 *
	 * @return array|mixed|object API response.
	 */
	public function delete_all_destinations() {
		$method         = 'delete';
		$this->endpoint = 'tpd_credsls';
		$path           = $this->get_api_url();

		$data = array();

		$response = $this->request( $path, $data, $method );

		return $response;
	}
}