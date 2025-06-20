<?php
/**
 * Controls Settings Config.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Configs;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Controllers;
use SmartCrawl\Controllers\Onboard;
use function smartcrawl_get_array_value;
use function smartcrawl_subsite_manager_role;

/**
 * Settings Config controller.
 */
class Controller extends Controllers\Controller {

	use Singleton;

	/**
	 * Config Service instance.
	 *
	 * @var Service
	 */
	private $service;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->service = new Service();

		parent::__construct();
	}

	/**
	 * Initializes the actions for AJAX requests.
	 */
	protected function init() {
		add_action( 'wp_ajax_smartcrawl_sync_configs', array( $this, 'sync_configs' ) );
		add_action( 'wp_ajax_smartcrawl_create_config', array( $this, 'create_config' ) );
		add_action( 'wp_ajax_smartcrawl_update_config', array( $this, 'update_config' ) );
		add_action( 'wp_ajax_smartcrawl_delete_config', array( $this, 'delete_config' ) );
		add_action( 'wp_ajax_smartcrawl_apply_config', array( $this, 'apply_config' ) );
		add_action( 'wp_ajax_smartcrawl_upload_config', array( $this, 'upload_config' ) );
	}

	/**
	 * Synchronizes hub configs.
	 */
	public function sync_configs() {
		$this->validate_request_data();

		$collection = Collection::get();

		if ( $this->service->is_member() ) {
			$synced = $collection->sync_with_hub();

			if ( ! $synced ) {
				wp_send_json_error( array( 'message' => __( 'Failed to sync with Hub.', 'wds' ) ) );
			}
		}

		wp_send_json_success(
			array(
				'configs' => $collection->get_deflated_configs(),
			)
		);
	}

	/**
	 * Creates a config.
	 *
	 * Attempts to create a config with the provided data. If the data is empty or if the name is empty,
	 * it sends a JSON error response. If the service is a member, it attempts to publish the config and
	 * sets the hub ID if successful. After that, it adds the config to the collection and saves it. Finally,
	 * it sends a JSON success response with the config ID and the deflated configs in the collection.
	 */
	public function create_config() {
		$data = $this->validate_request_data();
		$name = sanitize_text_field( smartcrawl_get_array_value( $data, 'name' ) );

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Name is not defined.', 'wds' ) ) );
		}

		$description = sanitize_text_field( smartcrawl_get_array_value( $data, 'description' ) );

		$config = Model::create_from_plugin_snapshot( $name, $description );

		if ( $this->service->is_member() ) {
			$response = $this->service->publish_config( $config );

			if ( empty( $response['id'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Failed to retrieve Response ID.', 'wds' ) ) );
			} else {
				$config->set_hub_id( $response['id'] );
			}
		}

		$collection = Collection::get();
		$collection->add( $config );
		$collection->save();

		wp_send_json_success(
			array(
				'config_id' => $config->get_id(),
				'configs'   => $collection->get_deflated_configs(),
			)
		);
	}

	/**
	 * Updates the configuration based on the request data.
	 *
	 * If the request data is valid, the configuration with matching ID and name
	 * is updated with the provided name and description. If the configuration
	 * exists and user is a member, the configuration is either updated or
	 * published based on the hub ID.
	 *
	 * If the configuration is successfully updated or published, the collection
	 * is saved and a success response is sent back with the deflated configurations.
	 * If any validation or update fails, an error response is sent back.
	 *
	 * @return void
	 */
	public function update_config() {
		$data        = $this->validate_request_data();
		$config_id   = smartcrawl_get_array_value( $data, 'config_id' );
		$name        = smartcrawl_get_array_value( $data, 'name' );
		$description = smartcrawl_get_array_value( $data, 'description' );

		if ( ! $config_id || ! $name ) {
			wp_send_json_error( array( 'message' => __( 'Config ID or Name is not defined.', 'wds' ) ) );
		}

		$collection = Collection::get();
		$config     = $collection->get_by_id( $config_id );

		if ( ! $config ) {
			wp_send_json_error( array( 'message' => __( 'Failed to retrieve config.', 'wds' ) ) );
		}

		$config->set_name( sanitize_text_field( $name ) );
		$config->set_description( sanitize_text_field( $description ) );

		if ( $this->service->is_member() ) {
			if ( $config->get_hub_id() ) {
				$response = $this->service->update_config( $config );
			} else {
				$response = $this->service->publish_config( $config );
				if ( ! empty( $response['id'] ) ) {
					$config->set_hub_id( $response['id'] );
				}
			}

			if ( ! $response ) {
				wp_send_json_error( array( 'message' => __( 'Response is not valid.', 'wds' ) ) );
			}
		}

		$collection->save();

		wp_send_json_success(
			array(
				'configs' => $collection->get_deflated_configs(),
			)
		);
	}

	/**
	 * Deletes a configuration.
	 */
	public function delete_config() {
		$data      = $this->validate_request_data();
		$config_id = smartcrawl_get_array_value( $data, 'config_id' );

		if ( ! $config_id ) {
			wp_send_json_error( array( 'message' => __( 'Config ID is not defined.', 'wds' ) ) );
		}

		$collection = Collection::get();
		$config     = $collection->get_by_id( $config_id );

		if ( ! $config ) {
			wp_send_json_error( array( 'message' => __( 'Failed to retrieve config.', 'wds' ) ) );
		}

		if ( $this->service->is_member() ) {
			$response = $this->service->delete_config( $config );

			if ( ! $response ) {
				wp_send_json_error( array( 'message' => __( 'Response is not valid.', 'wds' ) ) );
			}
		}

		$collection->remove( $config );
		$collection->save();

		wp_send_json_success(
			array(
				'configs' => $collection->get_deflated_configs(),
			)
		);
	}

	/**
	 * Applies a configuration.
	 */
	public function apply_config() {
		$data      = $this->validate_request_data();
		$config_id = smartcrawl_get_array_value( $data, 'config_id' );

		if ( ! $config_id ) {
			wp_send_json_error( array( 'message' => __( 'Config ID is not defined.', 'wds' ) ) );
		}

		$collection = Collection::get();
		$config     = $collection->get_by_id( $config_id );

		if ( ! $config ) {
			wp_send_json_error( array( 'message' => __( 'Failed to retrieve config.', 'wds' ) ) );
		}

		$configs = $config->get_configs();
		$this->apply_handler( $configs );

		wp_send_json_success();
	}

	/**
	 * Uploads the configuration file and saves the configuration.
	 *
	 * @return void
	 */
	public function upload_config() {
		$this->validate_request_data();

		$config_json = file_get_contents( $_FILES['file']['tmp_name'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! $config_json ) {
			wp_send_json_error( array( 'message' => __( 'Config data is not defined.', 'wds' ) ) );
		}

		$config_json   = json_decode( $config_json, true );
		$wds_blog_tabs = isset( $config_json['configs']['options']['wds_blog_tabs'] ) ? $config_json['configs']['options']['wds_blog_tabs'] : array();

		if ( isset( $wds_blog_tabs['wds_autolinks'] ) ) {
			$config_json['configs']['options']['wds_blog_tabs'][ Settings::ADVANCED_MODULE ] = $wds_blog_tabs['wds_autolinks'];
		}

		$config = Model::inflate( $config_json );

		if ( ! $config->get_id() ) {
			wp_send_json_error( array( 'message' => __( 'Failed to retrieve Config ID.', 'wds' ) ) );
		}

		$config->refresh_id();
		$config->set_timestamp( time() );
		$collection = Collection::get();

		if ( $this->service->is_member() ) {
			$response = $this->service->publish_config( $config );

			if ( empty( $response['id'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Failed to retrieve Response ID.', 'wds' ) ) );
			} else {
				$config->set_hub_id( $response['id'] );
			}
		}

		$collection->add( $config );
		$collection->save();

		wp_send_json_success(
			array(
				'config_id' => $config->get_id(),
				'configs'   => $collection->get_deflated_configs(),
			)
		);
	}

	/**
	 * Handles to apply the config data.
	 *
	 * If the 'options' key is empty in the configs, it applies the basic configuration using the method apply_basic_config().
	 * If the 'options' key is not empty, it loads the configuration using Import::load() and saves it.
	 * After applying the configuration, it marks the onboarding as done using Onboard::get()->mark_onboarding_done().
	 *
	 * @param array $configs The configuration to apply.
	 *
	 * @return void
	 */
	public function apply_handler( array $configs ) {
		$is_basic_config = empty( $configs['options'] );

		if ( $is_basic_config ) {
			$this->apply_defaults();
		} else {
			Import::load( wp_json_encode( $configs ) )->save();
		}

		$this->apply_extra();

		Onboard::get()->set_done();
	}

	/**
	 * Applies Default SEO Config.
	 */
	private function apply_defaults() {
		// Resets everything else so defaults can be applied.
		foreach ( Settings::get_all_components() as $component ) {
			if ( Settings::COMP_HEALTH !== $component ) {
				Settings::delete_component_options( $component );
			}
		}

		Settings::delete_specific_options( 'wds_settings_options' );

		$adv_controller = \SmartCrawl\Modules\Advanced\Controller::get();

		$options = array();

		foreach ( array_keys( $adv_controller->submodules ) as $submodule ) {
			$options[ $submodule ]['active'] = false;
		}

		update_option( $adv_controller->module_name, $options );
	}

	/**
	 * Applies extra configs.
	 */
	private function apply_extra() {
		update_option( 'wds-features-viewed', -1 );
	}

	/**
	 * Determines if current user has permission to perform actions.
	 *
	 * @return bool
	 * @since 3.3.1
	 */
	private function has_permission() {
		// Site admins only.
		$cap = 'manage_options';

		// If only super admins should access.
		if ( is_multisite() && smartcrawl_subsite_manager_role() === 'superadmin' ) {
			$cap = 'manage_network_options';
		}

		// Only admins should have access.
		return current_user_can( $cap );
	}

	/**
	 * Retrieves request data.
	 *
	 * This method checks if the user has permission before processing the request data.
	 *
	 * @return array
	 */
	private function validate_request_data() {
		if ( ! $this->has_permission() ) {
			wp_send_json_error( array( 'message' => __( 'You don\'t have permission to do this.', 'wds' ) ) );
		}

		if ( ! isset( $_POST['_wds_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-configs-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to verify nonce.', 'wds' ) ) );
		}

		return stripslashes_deep( $_POST );
	}
}