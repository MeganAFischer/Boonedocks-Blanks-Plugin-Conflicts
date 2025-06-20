<?php // phpcs:ignore
/**
 * Various functions
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Controller;

/**
 * Checks if a particular error code is represented in errors
 *
 * @param string $errstr Error code fragment.
 * @param array  $errors Errors array, or error object instance.
 *
 * @return bool
 */
function snapshot_has_error( $errstr, $errors ) {
	if ( ! is_array( $errors ) ) {
		$errors = array( $errors );
	}

	$delimiter = Model::SCOPE_DELIMITER;

	foreach ( $errors as $error ) {
		if ( ! is_wp_error( $error ) ) {
			continue;
		}
		$code = $error->get_error_code();

		$pos = stripos( $code, "{$errstr}{$delimiter}" );
		if ( 0 === $pos ) {
			// Position zero - we matched error up to delimiter.
			return true;
		}

		$string = "{$delimiter}{$errstr}";
		$pos    = stripos( $code, $string );
		if ( false !== $pos && strlen( $code ) === $pos + strlen( $string ) ) {
			// We found the substring, and it matches from delimiter to EOS.
			return true;
		}
	}

	return false;
}

if ( ! function_exists( 'snapshot_user_can_snapshot' ) ) {

	/**
	 * Checks if the user can perform snapshot actions.
	 *
	 * @param int $user_id Optional user ID - defaults to current user.
	 *
	 * @return bool
	 */
	function snapshot_user_can_snapshot( $user_id = null ) {
		if ( ! empty( $user_id ) ) {
			// Implement for non-current user.
			return false;
		}

		return current_user_can(
			Controller\Admin::get()->get_capability()
		);
	}
}

if ( ! function_exists( 'get_request_body' ) ) {

	/**
	 * Returns HTTP request body.
	 *
	 * @param bool $decode_json Decode request body as JSON.
	 * @param bool $return_array When true, returned objects will be converted into associate array.
	 *
	 * @return string|array|object|null
	 */
	function get_request_body( $decode_json = false, $return_array = false ) {
		$result = apply_filters( 'snapshot_get_request_body', file_get_contents( 'php://input' ) );
		return $decode_json ? json_decode( $result, $return_array ) : $result;
	}
}

if ( ! function_exists( 'wp_timezone_string' ) ) {

	/**
	 * Retrieves the timezone from site settings as a string.
	 *
	 * @return string PHP timezone string or a ±HH:MM offset.
	 */
	function wp_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}
}//end if

if ( ! function_exists( 'wp_timezone' ) ) {

	/**
	 * * Retrieves the timezone from site settings as a `DateTimeZone` object.
	 *
	 * @return \DateTimeZone
	 */
	function wp_timezone() {
		return new \DateTimeZone( wp_timezone_string() );
	}
}

if ( ! function_exists( 'snapshot_get_external_links' ) ) {

	/**
	 * Get external sources link
	 *
	 * @param string $endpoint Link required for.
	 *
	 * @return string
	 */
	function snapshot_get_external_links( $endpoint = '' ) {
		$domain = 'https://wpmudev.com';

		switch ( $endpoint ) {
			case 'hub-welcome':
			case 'configs':
				$link = "{$domain}/hub2/configs/my-configs";
				break;

			default:
				$link = $domain;
				break;
		}

		return $link;
	}
}//end if

if ( ! function_exists( 'is_suspicious_file_name' ) ) {

	/**
	 * Checks if the passed file path is suspicious
	 *
	 * @param string $path file path.
	 *
	 * @return boolean
	 */
	function is_suspicious_file_name( $path ) {
		return wp_strip_all_tags( $path ) !== $path;
	}
}

if ( ! function_exists( 'is_wpmudev_allowed_user' ) ) {

	/**
	 * Checks if currently logged in user has correct permission;
	 *
	 * @return boolean
	 */
	function is_wpmudev_allowed_user() {
		/**
		 * WPMUDEV interface object
		 *
		 * @var \WPMUDEV_Dashboard object
		 */
		global $wpmudev_un;

		if (
			! is_object( $wpmudev_un ) &&
			class_exists( '\WPMUDEV_Dashboard' ) &&
			method_exists( '\WPMUDEV_Dashboard', 'instance' )
		) {
			$wpmudev_un = \WPMUDEV_Dashboard::instance();
		}

		return $wpmudev_un::$site->allowed_user();
	}
}//end if

if ( ! function_exists( 'mb_to_gb' ) ) {

	/**
	 * Convert megabytes to gigabytes
	 *
	 * @param string $size Size in megabytes.
	 *
	 * @return int
	 */
	function mb_to_gb( $size ) {
		$natural = (float) $size;
		return round( ( $natural / 1024 ), 2 );
	}
}

if ( ! function_exists( 'parse_size_readable' ) ) {

	/**
	 * Converts bytes to human readable form
	 *
	 * @param integer $bytes     Number of bytes.
	 *
	 * @return string            Human readable size.
	 */
	function parse_size_readable( int $bytes ): string {
		return size_format( $bytes, 2 );
	}
}

if ( ! function_exists( 'snapshot_encrypt_data' ) ) {

	/**
	 * Encrypts data using AES-256-CBC encryption.
	 *
	 * This function encrypts the provided data using the AES-256-CBC algorithm and a specified encryption key.
	 * The initialization vector (IV) used in the encryption process is prepended to the encrypted data and the
	 * resulting string is encoded in base64 for safe storage.
	 *
	 * @param string $data The data to encrypt.
	 * @param string $key  The encryption key. The key should be a secure, random string with the correct length (32 bytes for AES-256).
	 *
	 * @return string The base64-encoded string of the IV combined with the encrypted data.
	 */
	function snapshot_encrypt_data (string $data, string $key = '' ): string {
    if ( empty( $key ) ) {
        $key = 'snapshot-backups-' . SNAPSHOT_BACKUPS_VERSION;
    }

    $key_hash  = sodium_crypto_generichash( $key, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
    $nonce     = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
    $encrypted = sodium_crypto_secretbox( $data, $nonce, $key_hash );

    return base64_encode( $nonce . $encrypted );
}
}


if ( ! function_exists( 'snapshot_decrypt_data' ) ) {

	/**
	 * Decrypts data encrypted with AES-256-CBC encryption.
	 *
	 * This function decrypts data that was encrypted using the AES-256-CBC algorithm. It expects the input
	 * to be a base64-encoded string containing the IV concatenated with the encrypted data.
	 *
	 * @param string $encrypted_data The base64-encoded string containing the IV and encrypted data.
	 * @param string $key            The decryption key. The key should match the key used during encryption.
	 *
	 * @return string|false The original decrypted data, or false on failure.
	 */
	function snapshot_decrypt_data(string $encrypted_data, string $key = ''): string {
		if ( empty( $key ) ) {
			$key = 'snapshot-backups-' . SNAPSHOT_BACKUPS_VERSION;
		}

		$decoded = base64_decode( $encrypted_data );
		if ( $decoded === false || strlen( $decoded ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return $encrypted_data; // Not valid for decryption.
		}

		$nonce = substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$key_hash = sodium_crypto_generichash( $key, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );

		return sodium_crypto_secretbox_open( $ciphertext, $nonce, $key_hash ) ?: $encrypted_data;
	}
}