<?php
/**
 * Manages config collection.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Configs;

use SmartCrawl\Logger;
use SmartCrawl\Singleton;
use function smartcrawl_get_array_value;

/**
 * Config Collection class.
 */
class Collection {

	use Singleton;

	const CONFIGS_INDEX_OPTION_ID = 'wds-configs-index';

	const CONFIG_OPTION_ID_PREFIX = 'wds-config-';

	/**
	 * Configs.
	 *
	 * @var Model[]
	 */
	private $configs = array();

	/**
	 * Includes deleted configs.
	 *
	 * @var array
	 */
	private $deleted = array();

	/**
	 * Config Service object.
	 *
	 * @var Service
	 */
	private $service;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->service = new Service();
		$this->load_from_storage();
	}

	/**
	 * Syncs data with hub.
	 *
	 * Works under the assumption that the HUB has the most up-to-date version of the data.
	 *
	 * @return bool
	 */
	public function sync_with_hub() {
		$local_changes_pushed  = $this->push_local_changes();
		$remote_changes_pulled = $this->pull_remote_changes();
		$this->save();

		return $local_changes_pushed && $remote_changes_pulled;
	}

	/**
	 * If there are any configs that were never published to the hub, this method publishes them.
	 * This is basically for free users that upgrade to pro and need their local configs published.
	 *
	 * @return bool
	 */
	private function push_local_changes() {
		$success = true;

		foreach ( $this->get_configs() as $local_config ) {
			if ( $local_config->get_hub_id() ) {
				// The local config already exists on hub, nothing to do.
				continue;
			}

			$saved_to_hub = $this->service->publish_config( $local_config );

			if ( ! empty( $saved_to_hub['id'] ) ) {
				$local_config->set_hub_id( $saved_to_hub['id'] );
			} else {
				Logger::error( 'There was an error while publishing a local config to remote' );
			}

			$success = $success && $saved_to_hub;
		}

		return $success;
	}

	/**
	 * Retrieves remote config changes from the hub.
	 *
	 * @return bool
	 */
	private function pull_remote_changes() {
		$hub_configs = $this->get_hub_configs();

		if ( false === $hub_configs ) {
			Logger::error( 'There was an error fetching configs from the HUB' );

			return false;
		}

		$applied = $this->apply_remote_changes_to_local( $hub_configs );
		$removed = $this->remove_remotely_deleted_from_local( $hub_configs );

		return $applied && $removed;
	}

	/**
	 * Adds config.
	 *
	 * @param Model $config Config model.
	 */
	public function add( $config ) {
		$this->configs[ $this->key( $config ) ] = $config;
	}

	/**
	 * Removes a config.
	 *
	 * @param Model $config Config to be removed.
	 *
	 * @return void
	 */
	public function remove( $config ) {
		$config_key = $this->key( $config );

		if ( array_key_exists( $config_key, $this->configs ) ) {
			unset( $this->configs[ $config_key ] );
			$this->deleted[ $config_key ] = $config;
		}
	}

	/**
	 * Retrieves key.
	 *
	 * @param Model $config Config model.
	 *
	 * @return string
	 */
	private function key( $config ) {
		return $this->id_to_key( $config->get_id() );
	}

	/**
	 * Retrieves config key from ID.
	 *
	 * @param string $id Config ID.
	 *
	 * @return string
	 */
	private function id_to_key( $id ) {
		return 'config-' . $id;
	}

	/**
	 * Loads config from database.
	 *
	 * @return void
	 */
	private function load_from_storage() {
		$config_ids = $this->get_stored_config_ids();

		foreach ( $config_ids as $config_id ) {
			$config_data = $this->get_stored_config_data( $config_id );
			$config      = Model::inflate( $config_data );

			if ( $config->get_id() ) {
				$this->add( $config );
			}
		}
	}

	/**
	 * Saves configs.
	 *
	 * @return bool
	 */
	public function save() {
		$config_ids = array();
		foreach ( $this->configs as $config ) {
			$this->save_config_data_to_storage( $config );
			$config_ids[] = $config->get_id();
		}

		foreach ( $this->deleted as $deleted_key => $deleted_config ) {
			$this->delete_config_data_from_storage( $deleted_config );
			unset( $this->deleted[ $deleted_key ] );
		}

		return update_option( self::CONFIGS_INDEX_OPTION_ID, $config_ids, false );
	}

	/**
	 * Retrieves stored config ids.
	 *
	 * @return array
	 */
	private function get_stored_config_ids() {
		$option = get_option( self::CONFIGS_INDEX_OPTION_ID, array() );

		return empty( $option )
			? array()
			: $option;
	}

	/**
	 * Retrieves stored config data by config ID.
	 *
	 * @param string $config_id Config ID.
	 *
	 * @return array
	 */
	private function get_stored_config_data( $config_id ) {
		$config_data = get_option( $this->config_option_id( $config_id ), array() );

		return empty( $config_data )
			? array()
			: $config_data;
	}

	/**
	 * Saves a config data.
	 *
	 * @param Model $config Config model.
	 *
	 * @return bool
	 */
	private function save_config_data_to_storage( $config ) {
		return update_option( $this->config_option_id( $config->get_id() ), $config->deflate(), false );
	}

	/**
	 * Deletes a config from database.
	 *
	 * @param Model $config Config data.
	 *
	 * @return bool
	 */
	private function delete_config_data_from_storage( $config ) {
		$config_id = $config->get_id();

		if ( ! $config_id ) {
			return false;
		}

		return delete_option( $this->config_option_id( $config_id ) );
	}

	/**
	 * Returns config option id.
	 *
	 * @param string $config_id Config ID.
	 *
	 * @return string
	 */
	private function config_option_id( $config_id ) {
		return self::CONFIG_OPTION_ID_PREFIX . $config_id;
	}

	/**
	 * Returns configs.
	 *
	 * @return Model[]
	 */
	public function get_configs() {
		return empty( $this->configs ) ? array() : $this->configs;
	}

	/**
	 * Sorts configs by comparator and return it.
	 *
	 * @return Model[]
	 */
	public function get_sorted_configs() {
		$configs = $this->get_configs();
		uasort( $configs, array( $this, 'comparator' ) );

		return $configs;
	}

	/**
	 * Comparison function.
	 *
	 * @param Model $first Config model.
	 * @param Model $second Config model.
	 *
	 * @return int
	 */
	private function comparator( $first, $second ) {
		if ( $first->get_timestamp() === $second->get_timestamp() ) {
			return 0;
		}

		return ( $first->get_timestamp() < $second->get_timestamp() ) ? 1 : - 1;
	}

	/**
	 * Retrieves deflated configs.
	 *
	 * @return array
	 */
	public function get_deflated_configs() {
		return array_map(
			function ( $config ) {
				return $config->deflate();
			},
			$this->get_sorted_configs()
		);
	}

	/**
	 * Retrieves config by id.
	 *
	 * @param string $config_id Config ID.
	 *
	 * @return Model|null
	 */
	public function get_by_id( $config_id ) {
		return smartcrawl_get_array_value( $this->configs, $this->id_to_key( $config_id ) );
	}

	/**
	 * Retrieves config by hub ID.
	 *
	 * @param string $hub_id Hub ID.
	 *
	 * @return Model|null
	 */
	public function get_by_hub_id( $hub_id ) {
		if ( ! $hub_id ) {
			return null;
		}

		foreach ( $this->get_configs() as $config ) {
			if ( $config->get_hub_id() === $hub_id ) {
				return $config;
			}
		}

		return null;
	}

	/**
	 * Set service.
	 *
	 * @param Service $service Service.
	 */
	public function set_service( $service ) {
		$this->service = $service;
	}

	/**
	 * Resets configs to empty.
	 *
	 * @return void
	 */
	public function reset() {
		$this->configs = array();
		self::get( true );
	}

	/**
	 * Retrieves Hub configs.
	 *
	 * @return array|false
	 */
	private function get_hub_configs() {
		$hub_configs_array = $this->service->get_configs();

		if ( empty( $hub_configs_array ) ) {
			return false;
		}

		$hub_configs = array();

		foreach ( $hub_configs_array as $hub_config_data ) {
			$hub_config = Model::create_from_hub_data( $hub_config_data );

			if ( ! $hub_config || ! $hub_config->get_hub_id() ) {
				return false;
			}

			$hub_configs[ $this->id_to_key( $hub_config->get_hub_id() ) ] = $hub_config;
		}

		return $hub_configs;
	}

	/**
	 * User could update name and description of configs or add brand-new configs on the hub side.
	 * This method applies those changes to local.
	 *
	 * @param Model[] $hub_configs Hub configs.
	 *
	 * @return bool
	 */
	private function apply_remote_changes_to_local( $hub_configs ) {
		foreach ( $hub_configs as $hub_config ) {
			$local_config = $this->get_by_hub_id( $hub_config->get_hub_id() );

			if ( $local_config ) {
				$local_config
					->set_name( $hub_config->get_name() )
					->set_description( $hub_config->get_description() )
					->set_official( $hub_config->is_official() )
					->set_timestamp( $hub_config->get_timestamp() );
			} else {
				$this->add( $hub_config );
			}
		}

		return true;
	}

	/**
	 * The user could delete configs on the hub. This method removes such configs from local.
	 *
	 * @param Model[] $hub_configs Hub configs.
	 *
	 * @return bool
	 */
	private function remove_remotely_deleted_from_local( $hub_configs ) {
		foreach ( $this->get_configs() as $local_config ) {
			if ( ! $local_config->get_hub_id() ) {
				// At this point in the sync process there shouldn't be any local configs without hub IDs, something is not right.
				Logger::notice( 'Unexpected config without HUB ID found' );

				return false;
			}
			$hub_config = smartcrawl_get_array_value(
				$hub_configs,
				$this->id_to_key( $local_config->get_hub_id() )
			);
			if ( ! $hub_config ) {
				// Hub version was removed, remove local version as well.
				$this->remove( $local_config );
			}
		}

		return true;
	}

	/**
	 * Update config option wds_blog_tabs settings.
	 *
	 * @param string $config_id Config Model ID.
	 * @param array  $config_data Config Data.
	 *
	 * @return void
	 */
	public function update_config_blog_tabs_settings( $config_id, $config_data ) {
		update_option( $this->config_option_id( $config_id ), $config_data, false );
	}
}