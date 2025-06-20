<?php // phpcs:ignore
/**
 * Snapshot requesting model abstraction class.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper\Zip;

use WPMUDEV\Snapshot4\Helper\Lock;
use WPMUDEV\Snapshot4\Helper\Log;

/**
 * Archive helper class
 */
class Archive extends Abstraction {

	/**
	 * Initializes.
	 */
	public function initialize() {
		$this->_zip = new \ZipArchive();
	}

	/**
	 * Check for zip file
	 *
	 * @param string $path Path to check.
	 *
	 * @return bool
	 */
	public function has( $path ) {
		$path = $this->_to_root_relative( $path );
		if ( empty( $path ) ) {
			return false;
		}

		$handle = $this->_zip->open( $this->_path );
		if ( ! $handle ) {
			return false;
		}

		$status = $this->_zip->locateName( $path );
		$this->_zip->close();

		return false === $status ? false : true;
	}

	/**
	 * Extracts from zip file
	 *
	 * @param string $destination Path to extract.
	 *
	 * @return bool
	 */
	public function extract( $destination ) {
		if ( empty( $destination ) ) {
			return false;
		}

		$destination = wp_normalize_path( $destination );
		if ( empty( $destination ) || ! file_exists( $destination ) ) {
			return false;
		}

		$handle = $this->_zip->open( $this->_path );
		if ( ! $handle ) {
			return false;
		}

		$status = $this->_zip->extractTo( $destination );

		$this->_zip->close();

		return $status;
	}

	/**
	 * Extract backup zip in chunks
	 *
	 * @param string $destination Path to extract.
	 * @param string $backup_id ID of backup being restored.
	 * @return bool|string
	 */
	public function extract_in_chunks( $destination, $backup_id ) {
		if ( empty( $destination ) ) {
			return false;
		}

		$destination = wp_normalize_path( $destination );
		if ( ! file_exists( $destination ) ) {
			return false;
		}

		$handle = $this->_zip->open( $this->_path, \ZipArchive::RDONLY );
		if ( ! $handle ) {
			return false;
		}

		$entries      = $this->_zip->count();
		$chunk_count  = apply_filters( 'snapshot_restore_extraction_chunk', 1000 );
		$lock_content = Lock::read( $backup_id );
		$start_index  = empty( $lock_content['extracted_index'] ) ? 0 : $lock_content['extracted_index'];
		$end_index    = $start_index + $chunk_count;

		if ( $start_index > $entries ) {
			$start_index = $entries;
		}

		Log::info(
			sprintf(
				/* translators: %1$d - Chunk Size, %2$d - Start Index, %3$d - Total Entries */
				__( 'Extracting %1$d files from %2$d of %3$d.', 'snapshot' ),
				$chunk_count,
				$start_index + 1,
				$entries
			)
		);

		$extractions = array();
		for ( $i = $start_index; $i < $end_index; $i++ ) {
			$stat = $this->_zip->statIndex( $i );

			if ( is_array( $stat ) ) {
				array_push( $extractions, $stat['name'] );
			}

			if ( $start_index + 1 >= $entries ) {
				// Break out of the loop as we have extracted all files.
				break;
			}
		}
		$status = $this->_zip->extractTo( $destination, $extractions );

		if ( $end_index + 1 >= $entries ) {
			$lock_content['extracted_index'] = -1;
			$status                          = 'done';
		} else {
			$lock_content['extracted_index'] = $end_index;
		}
		Lock::write( $lock_content, $backup_id );

		$this->_zip->close();

		return $status;
	}

	/**
	 * Extracts specific files from zip file
	 *
	 * @param string $destination Path to extract.
	 * @param array  $files Files to extract.
	 *
	 * @return bool
	 */
	public function extract_specific( $destination, $files ) {
		if ( empty( $destination ) ) {
			return false;
		}

		if ( empty( $files ) ) {
			return false;
		}
		if ( ! is_array( $files ) ) {
			return false;
		}

		$destination = wp_normalize_path( $destination );
		if ( empty( $destination ) || ! file_exists( $destination ) ) {
			return false;
		}

		$handle = $this->_zip->open( $this->_path );
		if ( ! $handle ) {
			return false;
		}

		$status = $this->_zip->extractTo( $destination, $files );
		$this->_zip->close();

		return $status;
	}
}