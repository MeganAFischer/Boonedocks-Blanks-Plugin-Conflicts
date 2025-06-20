<?php
namespace WPMUDEV\Snapshot4\Model;

use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper\Fs;
use WPMUDEV\Snapshot4\Helper\Lock;
use WPMUDEV\Snapshot4\Helper\Log;
use ZipArchive;
use WP_Error;

/**
 * Handles backup file downloads in chunks with proper validation.
 *
 * This class manages the download process of backup files, handling large files
 * by downloading them in chunks and validating the final zip archive.
 */
class Download extends Model {

	/** @var string Base directory path for downloads */
	private $local_base = '';

	/** @var int Maximum number of download retry attempts */
	private $max_retries = 3;

	/** @var int Delay between retry attempts in seconds */
	private $retry_delay = 2;

	/**
	 * Initializes download parameters and sets up base directory.
	 */
	public function __construct() {
		$this->local_base = Lock::get_lock_dir();
	}

	/**
	 * Prepares download data including chunk sizes and iteration counts.
	 *
	 * @return array Download configuration data
	 */
	private function prepare_download_data() {
		$data = Lock::read( $this->get( 'backup_id' ) ) ?: array();
		$size = (int) $data['size'];
		$this->set( 'dowload_size', $size );

		$index = isset( $data['index'] ) ? (int) $data['index'] : 0;

		// Default multiplier based on file size with filter support
		$default_multiplier = $size > ( 100 * 1024 * 1024 ) ? 10 : 5;

		/**
		 * Filter the download chunk multiplier.
		 *
		 * The default multiplier is 5MB for files smaller than 100MB and 10MB for larger files.
		 * It can be adjusted based on the file size to optimize download performance.
		 * Finally, it is applied to the file size in MB to determine the chunk size.
		 */
		$multiplier = apply_filters( 'snapshot_download_chunk_multiplier', $default_multiplier, $size );
		$chunk_size = $multiplier * 1024 * 1024;

		return array(
			'size'       => $size,
			'index'      => $index,
			'chunk_size' => $chunk_size,
			'iterations' => isset( $data['iterations'] )
				? (int) $data['iterations']
				: (int) ceil( $size / $chunk_size ),
		);
	}

	/**
	 * Creates and validates the download directory and file path.
	 *
	 * @return string|WP_Error Path to download file or error on failure
	 */
	private function prepare_download_path() {
		$local_dirpath = path_join( $this->local_base, $this->get( 'backup_id' ) );

		if ( ! wp_mkdir_p( $local_dirpath ) ) {
			return new WP_Error(
				'directory_creation_failed',
				__( 'Failed to create directory for download', 'snapshot' )
			);
		}

		return path_join( $local_dirpath, $this->get( 'backup_id' ) . '.zip' );
	}

	/**
	 * Downloads a chunk of the backup file with retry mechanism.
	 *
	 * @param string $download_link URL of the backup file
	 * @param array  $data Download configuration data
	 * @param string $local_file Path to save the downloaded chunk
	 * @return array Download response data
	 */
	private function process_download_chunk( $download_link, $data, $local_file ) {
		$start = $data['index'] * $data['chunk_size'];
		$end   = ( $start + $data['chunk_size'] ) - 1;

		$args = array(
			'timeout'  => 120,
			'headers'  => array(
				'Connection' => 'keep-alive',
				'Range'      => "bytes=$start-$end",
			),
			'stream'   => true,
			'filename' => $local_file . '.part' . $data['index'],
		);

		$response = $this->download_with_retry( $download_link, $args, $data, $local_file );

		if ( ! is_wp_error( $response ) ) {
			// Append the part file to main file
			$part_file = $args['filename'];
			if ( file_exists( $part_file ) ) {
				$main_fp = fopen( $local_file, 'ab' );
				$part_fp = fopen( $part_file, 'rb' );

				stream_copy_to_stream( $part_fp, $main_fp );

				fclose( $part_fp );
				fclose( $main_fp );
				unlink( $part_file );
			}
		}

		return array(
			'response'     => $response,
			'start'        => $start,
			'end'          => $end,
			'is_last_part' => ( ( $end + $data['chunk_size'] ) > $data['size'] )
				&& ( $end < $data['size'] ),
		);
	}

	/**
	 * Downloads a chunk of the backup file using cURL with retry mechanism.
	 *
	 * @param string $download_link URL of the backup file
	 * @param array  $data Download configuration data
	 * @param string $local_file Path to save the downloaded chunk
	 * @return array Download response data
	 */
	private function process_download_chunk_with_curl( $download_link, $data, $local_file ) {
		$start = $data['index'] * $data['chunk_size'];
		$end   = ( $start + $data['chunk_size'] ) - 1;

		$attempts = 0;
		$response = null;

		while ( $attempts < $this->max_retries ) {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $download_link );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 120 );
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					"Range: bytes=$start-$end",
					'Connection: keep-alive',
				)
			);
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_FILE, fopen( $local_file . '.part' . $data['index'], 'wb' ) );

			$response  = curl_exec( $ch );
			$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			curl_close( $ch );

			if ( $http_code == 206 || $http_code == 200 ) {
				// Append the part file to main file
				$part_file = $local_file . '.part' . $data['index'];
				if ( file_exists( $part_file ) ) {
					$main_fp = fopen( $local_file, 'ab' );
					$part_fp = fopen( $part_file, 'rb' );

					stream_copy_to_stream( $part_fp, $main_fp );

					fclose( $part_fp );
					fclose( $main_fp );
					unlink( $part_file );
				}
				break;
			}

			++$attempts;
			Log::warning(
				sprintf(
					__( 'Download attempt %1$d failed. Retrying in %2$d seconds...', 'snapshot' ),
					$attempts,
					$this->retry_delay
				)
			);

			if ( $attempts < $this->max_retries ) {
				sleep( $this->retry_delay );
			}
		}//end while

		if ( $http_code != 206 && $http_code != 200 ) {
			return array(
				'response'     => new WP_Error( 'curl_error', __( 'cURL download failed', 'snapshot' ) ),
				'start'        => $start,
				'end'          => $end,
				'is_last_part' => false,
			);
		}

		return array(
			'response'     => $response,
			'start'        => $start,
			'end'          => $end,
			'is_last_part' => ( ( $end + $data['chunk_size'] ) > $data['size'] )
				&& ( $end < $data['size'] ),
		);
	}

	/**
	 * Performs download with retry mechanism on failure.
	 *
	 * @param string $url Download URL
	 * @param array  $args WordPress HTTP request arguments
	 * @param array  $data Download configuration data
	 * @param string $local_file Path to save the downloaded chunk
	 * @return WP_Error|array Response data or WP_Error on ultimate failure
	 */
	private function download_with_retry( $url, $args, $data, $local_file ) {
		$attempts = 0;
		$use_curl = false;

		while ( $attempts < $this->max_retries ) {
			if ( $use_curl && function_exists( 'curl_exec' ) ) {
				$response = $this->process_download_chunk_with_curl( $url, $data, $local_file );
			} else {
				$response = wp_remote_get( $url, $args );
			}

			if ( ! is_wp_error( $response ) ) {
				return $response;
			}

			++$attempts;
			Log::warning(
				sprintf(
					__( 'Download attempt %1$d failed. Retrying in %2$d seconds...', 'snapshot' ),
					$attempts,
					$this->retry_delay
				)
			);

			if ( $attempts >= 2 && function_exists( 'curl_exec' ) ) {
				Log::warning( __( 'Switching to cURL for download.', 'snapshot' ) );
				$use_curl = true;
			}

			if ( $attempts < $this->max_retries ) {
				sleep( $this->retry_delay );
			}
		}//end while

		return $response;
	}

	/**
	 * Validates the downloaded zip file.
	 *
	 * @param string $local_file Path to the downloaded file
	 * @return array Validation status and related information
	 */
	private function validate_downloaded_file( $local_file ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return array( 'status' => 'download_complete' );
		}

		$zip_archive = new ZipArchive();
		$open_status = $zip_archive->open( $local_file );

		if ( true === $open_status ) {
			$zip_archive->close();
			return array( 'status' => 'download_complete' );
		}

		return array(
			'status'     => 'invalid_zip',
			'local_file' => $local_file,
			'message'    => __( 'The downloaded zip file was invalid and cannot be opened. Please try restoration from the beginning.', 'snapshot' ),
		);
	}

	/**
	 * Processes the download response and determines next steps.
	 *
	 * @param array  $download_response Response from download chunk process
	 * @param array  $data Download configuration data
	 * @param string $download_link Original download URL
	 * @param string $local_file Path to the local file
	 * @return array Status and progress information
	 */
	private function handle_download_response( $download_response, $data, $download_link, $local_file ) {
		if ( is_wp_error( $download_response['response'] ) ) {
			$this->set( 'download_completed', false );
			Log::error( 'Download failed: ' . $download_response['response']->get_error_message() );
			return array(
				'status'  => 'error',
				'message' => $download_response['response']->get_error_message(),
			);
		}

		$this->set( 'downloaded_till', $download_response['end'] );

		if ( $download_response['end'] >= $data['size'] ) {
			$this->set( 'download_completed', true );
			return $this->validate_downloaded_file( $local_file );
		}

		$this->set( 'download_completed', false );

		Log::info(
			sprintf(
				__( 'Downloaded %s bytes of data.', 'snapshot' ),
				$download_response['end']
			)
		);

		return array(
			'status'     => 'part_downloaded',
			'link'       => rawurlencode( $download_link ),
			'index'      => $data['index'] + 1,
			'size'       => $data['size'],
			'stage'      => 'download',
			'iterations' => $data['iterations'],
			'last_part'  => $download_response['is_last_part'],
			'downloaded' => $download_response['end'],
		);
	}

	/**
	 * Returns string to be used when an export has failed to be downloaded on restore.
	 *
	 * @return string
	 */
	public function get_download_error_string() {
		return esc_html__( 'the exported backup was being downloaded', 'snapshot' );
	}

	/**
	 * Invalid download link message.
	 *
	 * @return string
	 */
	public function get_downloadable_file_not_found_error_string() {
		return esc_html__( 'The provided download link is invalid or is expired.', 'snapshot' );
	}

	/**
	 * Gets information about a downloadable backup file.
	 *
	 * @param string $link URL of the backup file
	 * @return array|false Backup information or false on failure
	 */
	public function get_downloadable_backup_info( $link ) {
		$headers = get_headers( $link, true );

		if ( 'HTTP/1.1 200 OK' !== $headers[0] ) {
			$this->errors[] = array(
				'invalid_link',
				$this->get_downloadable_file_not_found_error_string(),
			);
			return false;
		}

		return array(
			'download_link' => rawurlencode( $link ),
			'backup_id'     => $this->get( 'backup_id' ),
			'size'          => $headers['Content-Length'],
			'readable'      => Fs::format_size( $headers['Content-Length'] ),
		);
	}

	/**
	 * Main method to handle the download process.
	 *
	 * @param string $download_link URL of the backup file
	 * @return array Download status and progress information
	 */
	public function handle_download( $download_link ) {
		$download_data = $this->prepare_download_data();
		$download_path = $this->prepare_download_path( $download_data['index'] );

		if ( is_wp_error( $download_path ) ) {
			return array(
				'status'  => 'error',
				'message' => $download_path->get_error_message(),
			);
		}

		$download_response = $this->process_download_chunk(
			$download_link,
			$download_data,
			$download_path
		);

		return $this->handle_download_response(
			$download_response,
			$download_data,
			$download_link,
			$download_path
		);
	}
}