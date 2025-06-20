<?php // phpcs:ignore
/**
 * Third party exports helper class.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper;

class Exports {
	/**
	 * Individual snaphsot backup.
	 *
	 * @var mixed
	 */
	protected $snapshot;

	/**
	 * List of export failure reasons.
	 *
	 * @var array
	 */
	private $export_errors = array(
		'export_failed_MissingSnaps',
		'export_failed_IOError',
		'export_failed_InternalServerError',
		'export_failed_TimeOut',
		'export_failed_ExtractErrors',
		'export_failed_UnknownError',
		'export_failed',
	);

	/**
	 * Third party exports constructor.
	 *
	 * @param mixed $snapshot Individual snapshot backup.
	 *
	 * @return void
	 */
	public function __construct( $snapshot ) {
		$this->snapshot = $snapshot;
	}

	/**
	 * Get snapshot ID.
	 *
	 * @return string
	 */
	public function get_snapshot_id(): string {
		return $this->snapshot['snapshot_id'];
	}

	/**
	 * get_snapshot_date
	 *
	 * @return string
	 */
	public function get_snapshot_date(): string {
		return $this->snapshot['created_at'];
	}

	/**
	 * Check if snapshot has any export.
	 *
	 * @return bool
	 */
	public function has_export(): bool {
		$all_exports = $this->get_exports();
		return ! empty( $all_exports ) && count( $all_exports ) > 0;
	}

	/**
	 * Get all exports.
	 *
	 * @return array
	 */
	public function get_exports(): array {
		$total_exports = array();

		$exports = isset( $this->snapshot['tpd_exp_status'] ) ? $this->snapshot['tpd_exp_status'] : '';

		$exports = ( ! is_array( $exports ) && '' !== $exports && null !== $exports ) ? str_replace( "'", '"', $exports ) : $exports;
		$exports = ( ! is_array( $exports ) && '' !== $exports && null !== $exports ) ? json_decode( $exports, true ) : $exports;

		if ( isset( $exports['tpd_s3'] ) ) {
			$total_exports['s3'] = $exports['tpd_s3'];
		}

		if ( isset( $exports['tpd_gdrive'] ) ) {
			$total_exports['gdrive'] = $exports['tpd_gdrive'];
		}

		if ( isset( $exports['tpd_dropbox'] ) ) {
			$total_exports['dropbox'] = $exports['tpd_dropbox'];
		}

		if ( isset( $exports['tpd_ftp'] ) ) {
			$total_exports['ftp'] = $exports['tpd_ftp'];
		}

		if ( isset( $exports['tpd_sftp'] ) ) {
			$total_exports['sftp'] = $exports['tpd_sftp'];
		}

		if ( isset( $exports['tpd_onedrive'] ) ) {
			$total_exports['onedrive'] = $exports['tpd_onedrive'];
		}

		if ( isset( $exports['tpd_azure'] ) ) {
			$total_exports['azure'] = $exports['tpd_azure'];
		}

		return $total_exports;
	}

	/**
	 * Get all the successful exports.
	 *
	 * @return array
	 */
	public function get_failed_exports(): array {
		$failed_exports = array();
		$exports        = $this->get_exports();

		if ( ! empty( $exports ) && is_array( $exports ) ) {
			foreach ( $exports as $key => $export ) {
				if ( ! empty( $export ) && is_array( $export ) ) {
					foreach ( $export as $export_key => $export_value ) {
						if ( in_array( $export_value, $this->export_errors, true ) ) {
							$failed_exports[ $key ] = array( $export_key => $export_value );
						}
					}
				}
			}
		}

		return $failed_exports;
	}

	/**
	 * Retrieve all the successful exports.
	 *
	 * @return array
	 */
	public function get_successful_exports(): array {
		$successful_exports = array();
		$exports            = $this->get_exports();

		if ( ! empty( $exports ) && is_array( $exports ) ) {
			foreach ( $exports as $key => $export ) {
				if ( ! empty( $export ) && is_array( $export ) ) {
					foreach ( $export as $export_key => $export_value ) {
						if ( 'export_success' === $export_value ) {
							$successful_exports[ $key ][] = array( $export_key => $export_value );
						}
					}
				}
			}
		}

		return $successful_exports;
	}

	/**
	 * Check if snapshot has any failed export.
	 *
	 * @return bool
	 */
	public function has_failed_export(): bool {
		$failed_exports = $this->get_failed_exports();
		return ! empty( $failed_exports ) && count( $failed_exports ) > 0;
	}

	/**
	 * Get the destination name.
	 *
	 * @param string $name Destination name.
	 *
	 * @return string
	 */
	public function extract_destination_name( string $name ): string {
		if ( false !== strpos( $name, ' ', 0 ) ) {
			list( $export_id, $name ) = explode( ' ', $name, 2 );
		}

		return $name;
	}
}