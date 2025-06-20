<?php
/**
 * Service for config.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Configs;

use SmartCrawl\Services;

/**
 * Config Service class
 */
class Service extends Services\Service {

	const VERB_GET_PACKAGE_CONFIGS = 'get-package-configs';
	const VERB_CREATE_CONFIG       = 'create-config';
	const VERB_UPDATE_CONFIG       = 'update-config';
	const VERB_DELETE_CONFIG       = 'delete-config';
	const REST_BASE                = 'package-configs';

	/**
	 * Config Model.
	 *
	 * @var Model|null
	 */
	private $model;

	/**
	 * Returns the API endpoint of config service.
	 *
	 * @return string
	 */
	public function get_service_base_url() {
		$base_url = 'https://wpmudev.com/';

		if ( defined( '\WPMUDEV_CUSTOM_API_SERVER' ) && \WPMUDEV_CUSTOM_API_SERVER ) {
			$base_url = trailingslashit( \WPMUDEV_CUSTOM_API_SERVER );
		}

		return trailingslashit( $base_url ) . 'api/hub/v1';
	}

	/**
	 * Returns an array of known verbs.
	 *
	 * @return array An array containing the known verbs.
	 */
	public function get_known_verbs() {
		return array(
			self::VERB_GET_PACKAGE_CONFIGS,
			self::VERB_CREATE_CONFIG,
			self::VERB_UPDATE_CONFIG,
			self::VERB_DELETE_CONFIG,
		);
	}

	/**
	 * Determines if a verb is cacheable.
	 *
	 * @param string $verb The verb to check.
	 *
	 * @return bool Returns false indicating that the verb is not cacheable.
	 */
	public function is_cacheable_verb( $verb ) {
		return false;
	}

	/**
	 * Returns the request URL for a given verb.
	 *
	 * @param string $verb The verb for the request.
	 *
	 * @return string The request URL.
	 */
	public function get_request_url( $verb ) {
		$query    = array(
			'package_id' => SMARTCRAWL_PACKAGE_ID,
		);
		$base_url = trailingslashit( $this->get_service_base_url() ) . self::REST_BASE;

		if (
			( self::VERB_DELETE_CONFIG === $verb || self::VERB_UPDATE_CONFIG === $verb )
			&& $this->model
		) {
			$base_url = trailingslashit( $base_url ) . $this->model->get_hub_id();
		}

		return esc_url_raw( add_query_arg( $query, $base_url ) );
	}

	/**
	 * Returns the request arguments based on the provided verb.
	 *
	 * @param string $verb The verb to determine the request arguments.
	 *
	 * @return array The request arguments.
	 */
	public function get_request_arguments( $verb ) {
		switch ( $verb ) {
			case self::VERB_CREATE_CONFIG:
				$args = $this->get_create_config_args();
				break;

			case self::VERB_UPDATE_CONFIG:
				$args = $this->get_update_config_args();
				break;

			case self::VERB_DELETE_CONFIG:
				$args = array( 'method' => 'DELETE' );
				break;

			default:
				$args = array( 'method' => 'GET' );
		}

		$args['timeout']   = $this->get_timeout();
		$args['sslverify'] = false;

		$key = (string) $this->get_dashboard_api_key();

		if ( $key ) {
			$args['headers']['Authorization'] = "Basic {$key}";
		}

		return $args;
	}

	/**
	 * Returns the package configs.
	 *
	 * @return array The package configs.
	 */
	public function get_configs() {
		return $this->request( self::VERB_GET_PACKAGE_CONFIGS );
	}

	/**
	 * Publishes a config model.
	 *
	 * @param Model $model Model to be published.
	 *
	 * @return mixed The response of the request.
	 */
	public function publish_config( $model ) {
		$this->model = $model;
		$response    = $this->request( self::VERB_CREATE_CONFIG );
		$this->model = null;

		return $response;
	}

	/**
	 * Updates a config model.
	 *
	 * @param Model $model Config model.
	 *
	 * @return mixed
	 */
	public function update_config( $model ) {
		$this->model = $model;
		$response    = $this->request( self::VERB_UPDATE_CONFIG );
		$this->model = null;

		return $response;
	}

	/**
	 * Deletes a config model.
	 *
	 * @param Model $model Config model.
	 *
	 * @return mixed
	 */
	public function delete_config( $model ) {
		$this->model = $model;
		$response    = $this->request( self::VERB_DELETE_CONFIG );
		$this->model = null;

		return $response;
	}

	/**
	 * Handles the error response for a given verb.
	 *
	 * @param mixed  $response The error response.
	 * @param string $verb The verb that caused the error.
	 *
	 * @return void
	 */
	public function handle_error_response( $response, $verb ) {
		// TODO: Implement handle_error_response() method.
	}

	/**
	 * Returns an array of arguments for the service request.
	 *
	 * @return array An array containing the arguments for creating a config.
	 */
	private function get_create_config_args() {
		return array(
			'method' => 'POST',
			'body'   => array(
				'name'        => $this->model->get_name(),
				'description' => $this->model->get_description(),
				'package'     => array(
					'name' => 'SmartCrawl Pro',
					'id'   => SMARTCRAWL_PACKAGE_ID,
				),
				'config'      => wp_json_encode(
					array(
						'configs' => $this->model->get_configs(),
						'strings' => $this->model->get_strings(),
					)
				),
			),
		);
	}

	/**
	 * Returns an array of arguments used for updating a configuration.
	 *
	 * @return array An array of update configuration arguments.
	 */
	private function get_update_config_args() {
		return array(
			'method' => 'POST',
			'body'   => array(
				'name'        => $this->model->get_name(),
				'description' => $this->model->get_description(),
				'package'     => array(
					'name' => 'SmartCrawl Pro',
					'id'   => SMARTCRAWL_PACKAGE_ID,
				),
			),
		);
	}
}