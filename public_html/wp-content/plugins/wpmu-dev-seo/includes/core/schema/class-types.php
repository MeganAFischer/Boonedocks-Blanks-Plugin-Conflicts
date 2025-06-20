<?php
/**
 * Schema types controller
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema;

use SmartCrawl\Singleton;
use SmartCrawl\Controllers;

/**
 * Schema types controller.
 */
class Types extends Controllers\Controller {

	use Singleton;

	const SCHEMA_TYPES_OPTION_ID = 'wds-schema-types';

	const SCHEMA_TYPE_OPTION_PREFIX = 'wds-schema-type-';

	/**
	 * Adds action hooks.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'init', array( $this, 'save_settings' ) );
	}

	/**
	 * Handles to save settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		// Not sanitizing nonce as it might alter the value.
		if ( ! current_user_can( 'manage_options' ) || empty( $_POST['_wpnonce'] ) || wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), '_wpnonce' ) ) {
			return;
		}

		$types_json = \smartcrawl_get_array_value( $_POST, self::SCHEMA_TYPES_OPTION_ID );

		if ( ! $types_json ) {
			return;
		}

		$current_types = json_decode( stripslashes_deep( $types_json ), true );

		$previous_types = $this->get_schema_types();

		$this->flush_old_schema_types();
		$this->save_schema_types( $current_types );

		$new_types     = array_diff( array_keys( $current_types ), array_keys( $previous_types ) );
		$deleted_types = array_diff( array_keys( $previous_types ), array_keys( $current_types ) );

		if ( ! empty( $new_types ) ) {
			/**
			 * Action hook to trigger when new schema types are added.
			 *
			 * @since 3.7.0
			 *
			 * @param array $new_types      New schema types.
			 * @param array $previous_types Old schema types.
			 * @param array $current_types  Current schema types.
			 * @param array $_POST          Post data.
			 */
			do_action( 'smartcrawl_after_add_schema_types', $new_types, $previous_types, $current_types, $_POST );
		}

		if ( ! empty( $deleted_types ) ) {
			/**
			 * Action hook to trigger after schema types deleted.
			 *
			 * @since 3.7.0
			 *
			 * @param array $deleted_types  Delete schema types.
			 * @param array $previous_types Old schema types.
			 * @param array $current_types  Current schema types.
			 */
			do_action( 'smartcrawl_after_delete_schema_types', $deleted_types, $previous_types, $current_types );
		}
	}

	/**
	 * Retrieves schema types.
	 *
	 * @return array
	 */
	public function get_schema_types() {
		$types     = array();
		$type_keys = get_option( self::SCHEMA_TYPES_OPTION_ID, array() );
		foreach ( $type_keys as $type_key ) {
			$type_option = get_option( $this->type_key_to_option_id( $type_key ) );

			if ( $type_option ) {
				$types[ $type_key ] = $type_option;
			}
		}

		return $types;
	}

	/**
	 * Clears old schema types settings.
	 *
	 * @return void
	 */
	private function flush_old_schema_types() {
		$types = get_option( self::SCHEMA_TYPES_OPTION_ID, array() );
		foreach ( $types as $type_key ) {
			delete_option( $this->type_key_to_option_id( $type_key ) );
		}
	}

	/**
	 * Retrieves options id from type key.
	 *
	 * @param string $type_key Type key.
	 *
	 * @return string
	 */
	private function type_key_to_option_id( $type_key ) {
		return self::SCHEMA_TYPE_OPTION_PREFIX . $type_key;
	}

	/**
	 * Handles to save schema types.
	 *
	 * @param array $types Types data.
	 *
	 * @return void
	 */
	private function save_schema_types( $types ) {
		update_option( self::SCHEMA_TYPES_OPTION_ID, array_keys( $types ), false );
		foreach ( $types as $type_key => $schema ) {
			update_option( $this->type_key_to_option_id( $type_key ), $schema, false );
		}
	}
}