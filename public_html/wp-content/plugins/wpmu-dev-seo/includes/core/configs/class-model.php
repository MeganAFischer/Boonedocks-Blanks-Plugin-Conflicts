<?php
/**
 * Config Model
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Configs;

use SmartCrawl\Modules\Advanced\Controller as Adv_Controller;
use SmartCrawl\Modules\Advanced\Redirects\Database_Table;
use SmartCrawl\Modules\Advanced\Robots\Controller;
use SmartCrawl\Lighthouse;
use SmartCrawl\Settings;

/**
 * Config Model class
 */
class Model {
	/**
	 * Model ID.
	 *
	 * @var string
	 */
	private $id = '';
	/**
	 * The Hub ID.
	 *
	 * @var string
	 */
	private $hub_id = '';
	/**
	 * Model name.
	 *
	 * @var string
	 */
	private $name = '';
	/**
	 * Model description.
	 *
	 * @var string
	 */
	private $description = '';
	/**
	 * Configurations.
	 *
	 * @var array
	 */
	private $configs = array();
	/**
	 * Strings.
	 *
	 * @var array
	 */
	private $strings = array();
	/**
	 * Flag indicating whether the content is editable or not.
	 *
	 * @var bool
	 */
	private $editable = true;
	/**
	 * Whether the item is removable or not.
	 *
	 * @var bool
	 */
	private $removable = true;
	/**
	 * Flag indicating if it is official.
	 *
	 * @var bool
	 */
	private $official = false;
	/**
	 * Timestamp.
	 *
	 * @var int
	 */
	private $timestamp = 0;

	/**
	 * Creates an instance of the model from a plugin snapshot.
	 *
	 * @param string $name The name of the plugin.
	 * @param string $description The description of the plugin.
	 *
	 * @return Model The created instance of the model.
	 */
	public static function create_from_plugin_snapshot( $name = '', $description = '' ) {
		$configs  = Export::load()->get_all();
		$instance = new self();
		$strings  = $instance->prepare_strings();

		return $instance
			->set_id( uniqid() )
			->set_name( $name )
			->set_description( $description )
			->set_configs( $configs )
			->set_strings( $strings )
			->set_timestamp( time() );
	}

	/**
	 * Creates a model instance from hub data.
	 *
	 * @param array $hub_config_data The hub config data.
	 *
	 * @return Model|null The model instance or null if hub config data is invalid.
	 */
	public static function create_from_hub_data( $hub_config_data ) {
		$hub_config_json = \smartcrawl_get_array_value( $hub_config_data, 'config' );
		$hub_id          = \smartcrawl_get_array_value( $hub_config_data, 'id' );

		if ( ! $hub_config_json || ! $hub_id ) {
			return null;
		}

		$hub_config = json_decode( $hub_config_json, true );

		if ( ! $hub_config ) {
			return null;
		}

		$created_time      = \smartcrawl_get_array_value( $hub_config_data, 'created_time_utc' );
		$created_timestamp = empty( $created_time ) ? 0 : (int) strtotime( $created_time );

		return ( new self() )
			->set_id( uniqid() )
			->set_hub_id( $hub_id )
			->set_name( \smartcrawl_get_array_value( $hub_config_data, 'name' ) )
			->set_description( \smartcrawl_get_array_value( $hub_config_data, 'description' ) )
			->set_configs( \smartcrawl_get_array_value( $hub_config, 'configs' ) )
			->set_strings( \smartcrawl_get_array_value( $hub_config, 'strings' ) )
			->set_editable( (bool) \smartcrawl_get_array_value( $hub_config_data, 'is_editable' ) )
			->set_removable( (bool) \smartcrawl_get_array_value( $hub_config_data, 'is_removable' ) )
			->set_official( (bool) \smartcrawl_get_array_value( $hub_config_data, 'is_official' ) )
			->set_timestamp( $created_timestamp );
	}

	/**
	 * Strings which be visible on the configs screen. The structure is going to be different from how settings are saved in the DB.
	 */
	private function prepare_strings() {
		return array(
			'health'   => $this->prepare_health_string(),
			'onpage'   => $this->prepare_onpage_string(),
			'schema'   => $this->prepare_schema_string(),
			'social'   => $this->prepare_social_string(),
			'sitemap'  => $this->prepare_sitemap_string(),
			'advanced' => $this->prepare_advanced_string(),
			'settings' => $this->prepare_settings_strings(),
		);
	}

	/**
	 * Retrieves label for module.
	 *
	 * @param string $module Module indicator.
	 *
	 * @return string Label.
	 */
	public function get_label( $module ) {
		$labels = array(
			'health'   => esc_html__( 'Health', 'wds' ),
			'onpage'   => esc_html__( 'Title & Meta', 'wds' ),
			'schema'   => esc_html__( 'Schema', 'wds' ),
			'social'   => esc_html__( 'Social', 'wds' ),
			'sitemap'  => esc_html__( 'Sitemap', 'wds' ),
			'advanced' => esc_html__( 'Advanced Tools', 'wds' ),
			'settings' => esc_html__( 'Settings', 'wds' ),
		);

		return (string) \smartcrawl_get_array_value( $labels, $module );
	}

	/**
	 * Prepares string for SEO Health.
	 *
	 * @return string
	 */
	private function prepare_health_string() {
		$reporting_status = $this->prepare_lighthouse_reporting_status();

		$parts[] = esc_html__( 'SEO test - Active', 'wds' );
		$parts[] = sprintf(
			/* translators: %s: Report status */
			esc_html__( 'Scheduled performance reports - %s', 'wds' ),
			$reporting_status
		);
		return implode( "\n", $parts );
	}

	/**
	 * Prepares string for Title & Meta.
	 *
	 * @return string
	 */
	private function prepare_onpage_string() {
		return Settings::get_setting( 'onpage' )
			? esc_html__( 'Active', 'wds' )
			: esc_html__( 'Inactive', 'wds' );
	}

	/**
	 * Prepares string for Schema.
	 *
	 * @return string
	 */
	private function prepare_schema_string() {
		$social          = Settings::get_component_options( Settings::COMP_SOCIAL );
		$schema_disabled = ! empty( $social['disable-schema'] );
		return $schema_disabled
			? esc_html__( 'Inactive', 'wds' )
			: esc_html__( 'Active', 'wds' );
	}

	/**
	 * Prepares string for Social.
	 *
	 * @return string
	 */
	private function prepare_social_string() {
		$options       = Settings::get_options();
		$social_active = (bool) \smartcrawl_get_array_value( $options, 'social' );
		if ( ! $social_active ) {
			return $this->get_status_string( $social_active );
		}

		$og_active      = (bool) \smartcrawl_get_array_value( $options, 'og-enable' );
		$twitter_active = (bool) \smartcrawl_get_array_value( $options, 'twitter-card-enable' );

		return join(
			"\n",
			array(
				esc_attr__( 'OpenGraph Support - ', 'wds' ) . $this->get_status_string( $og_active ),
				esc_attr__( 'Twitter Cards - ', 'wds' ) . $this->get_status_string( $twitter_active ),
			)
		);
	}

	/**
	 * Prepares string for Sitemap.
	 *
	 * @return string
	 */
	private function prepare_sitemap_string() {
		return Settings::get_setting( 'sitemap' )
			? esc_html__( 'Active', 'wds' )
			: esc_html__( 'Inactive', 'wds' );
	}

	/**
	 * Prepares string for Advanced Tools.
	 *
	 * @return string
	 */
	private function prepare_advanced_string() {
		$submodule_str = array();

		foreach ( Adv_Controller::get()->submodules as $submodule_name => $handler ) {
			if ( Settings::WOOCOMMERCE_SUBMODULE !== $submodule_name ) {
				$is_running = $handler->should_run();

				if ( Settings::SEOMOZ_SUBMODULE === $submodule_name && $is_running ) {
					$options    = $handler->get_options();
					$is_running = ! empty( $options['access_id'] ) && ! empty( $options['secret_key'] );
				}

				$submodule_str[] = $handler->module_title . ' - ' . $this->get_status_string( $is_running );
			}
		}

		return join( "\n", $submodule_str );
	}

	/**
	 * Retrieves the status based on the value.
	 *
	 * @param bool $active Indicates if the item is active.
	 *
	 * @return string The status string, either "Active" or "Inactive".
	 */
	private function get_status_string( $active ) {
		return $active
			? esc_html__( 'Active', 'wds' )
			: esc_html__( 'Inactive', 'wds' );
	}

	/**
	 * Prepares string for Settings.
	 *
	 * @return string
	 */
	private function prepare_settings_strings() {
		$options                      = Settings::get_options();
		$seo_analysis_enabled         = (bool) \smartcrawl_get_array_value( $options, 'analysis-seo' );
		$readability_analysis_enabled = (bool) \smartcrawl_get_array_value( $options, 'analysis-readability' );
		$keep_settings_on_uninstall   = (bool) \smartcrawl_get_array_value( $options, 'keep_settings_on_uninstall' );
		$keep_data_on_uninstall       = (bool) \smartcrawl_get_array_value( $options, 'keep_data_on_uninstall' );
		$usage_tracking               = (bool) \smartcrawl_get_array_value( $options, 'usage_tracking' );
		$high_contrast                = (bool) \smartcrawl_get_array_value( $options, 'high-contrast' );

		return join(
			"\n",
			array(
				esc_attr__( 'In-Post Page Analysis - ', 'wds' ) . $this->get_status_string( $seo_analysis_enabled ),
				esc_attr__( 'In-Post Readability Analysis - ', 'wds' ) . $this->get_status_string( $readability_analysis_enabled ),
				esc_attr__( 'Preserve settings on uninstall - ', 'wds' ) . $this->get_status_string( $keep_settings_on_uninstall ),
				esc_attr__( 'Keep data on uninstall - ', 'wds' ) . $this->get_status_string( $keep_data_on_uninstall ),
				esc_attr__( 'Usage tracking - ', 'wds' ) . $this->get_status_string( $usage_tracking ),
				esc_attr__( 'High Contrast Mode - ', 'wds' ) . $this->get_status_string( $high_contrast ),
			)
		);
	}

	/**
	 * Prepares string for Lighthouse reporting status.
	 *
	 * @return string
	 */
	private function prepare_lighthouse_reporting_status() {
		if ( ! Lighthouse\Options::is_cron_enabled() ) {
			return esc_html__( 'Inactive', 'wds' );
		}

		$recipients = Lighthouse\Options::email_recipients();
		$frequency  = \smartcrawl_get_array_value(
			\SmartCrawl\Controllers\Cron::get()->get_frequencies(),
			Lighthouse\Options::reporting_frequency()
		);

		return sprintf(
			/* translators: 1: Frequency, 2: Receipients */
			esc_html__( 'Active and sending %1$s to %2$d recipients', 'wds' ),
			$frequency,
			count( $recipients )
		);
	}

	/**
	 * Retrieves the id of the model.
	 *
	 * @return string The id of the model.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Sets the id of the model.
	 *
	 * @param string $id ID.
	 *
	 * @return Model
	 */
	public function set_id( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Generates new ID of the model which is unique.
	 *
	 * @return Model
	 */
	public function refresh_id() {
		return $this->set_id( uniqid() );
	}

	/**
	 * Retrieves Hub ID.
	 *
	 * @return string
	 */
	public function get_hub_id() {
		return $this->hub_id;
	}

	/**
	 * Sets Hub ID.
	 *
	 * @param string $hub_id Hub ID.
	 *
	 * @return Model
	 */
	public function set_hub_id( $hub_id ) {
		$this->hub_id = $hub_id;
		return $this;
	}

	/**
	 * Retrieves name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Sets name.
	 *
	 * @param string $name Name.
	 *
	 * @return Model
	 */
	public function set_name( $name ) {
		$this->name = sanitize_text_field( $name );
		return $this;
	}

	/**
	 * Retrieves description.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Sets description.
	 *
	 * @param string $description Description.
	 *
	 * @return Model
	 */
	public function set_description( $description ) {
		$this->description = sanitize_text_field( $description );
		return $this;
	}

	/**
	 * Retrieves configurations.
	 *
	 * @return array
	 */
	public function get_configs() {
		return $this->configs;
	}

	/**
	 * Sets configurations.
	 *
	 * @param array $configs Configs.
	 *
	 * @return Model
	 */
	public function set_configs( $configs ) {
		$this->configs = $configs;
		return $this;
	}

	/**
	 * Retrieves strings.
	 *
	 * @return array
	 */
	public function get_strings() {
		return $this->strings;
	}

	/**
	 * Sets strings.
	 *
	 * @param array $strings Strings to be set.
	 *
	 * @return Model
	 */
	public function set_strings( $strings ) {
		$this->strings = $strings;
		return $this;
	}

	/**
	 * Checks if the model is editable.
	 *
	 * @return bool Returns true if the model is editable, false otherwise.
	 */
	public function is_editable() {
		return $this->editable;
	}

	/**
	 * Sets model as editable.
	 *
	 * @param bool $editable Flag indicating if the model is editable.
	 *
	 * @return Model
	 */
	public function set_editable( $editable ) {
		$this->editable = $editable;
		return $this;
	}

	/**
	 * Checks if the model is removable.
	 *
	 * @return bool
	 */
	public function is_removable() {
		return $this->removable;
	}

	/**
	 * Sets the removable flag.
	 *
	 * @param bool $removable Flag indicating if the element is removable.
	 *
	 * @return Model
	 */
	public function set_removable( $removable ) {
		$this->removable = $removable;
		return $this;
	}

	/**
	 * Checks if the model is official.
	 *
	 * @return bool Returns true if official, false otherwise.
	 */
	public function is_official() {
		return $this->official;
	}

	/**
	 * Sets the official flag.
	 *
	 * @param bool $official The official flag.
	 *
	 * @return Model
	 */
	public function set_official( $official ) {
		$this->official = $official;
		return $this;
	}

	/**
	 * Retrieves the timestamp.
	 *
	 * @return int The timestamp.
	 */
	public function get_timestamp() {
		return $this->timestamp;
	}

	/**
	 * Sets the timestamp.
	 *
	 * @param int $timestamp The timestamp to set.
	 *
	 * @return Model
	 */
	public function set_timestamp( $timestamp ) {
		$this->timestamp = $timestamp;
		return $this;
	}

	/**
	 * Returns the filename for the model.
	 *
	 * The filename is generated by concatenating 'smartcrawl-config-' and
	 * the name of the model with spaces replaced by dashes.
	 *
	 * @return string The generated filename.
	 */
	public function get_filename() {
		return 'smartcrawl-config-' . str_replace( ' ', '-', $this->get_name() );
	}

	/**
	 * Inflates an instance of the Model class with data.
	 *
	 * @param array $data The data used to inflate the instance.
	 *
	 * @return Model The inflated instance.
	 */
	public static function inflate( $data ) {
		return ( new self() )
			->set_id( \smartcrawl_get_array_value( $data, 'id' ) )
			->set_hub_id( \smartcrawl_get_array_value( $data, 'hub_id' ) )
			->set_name( \smartcrawl_get_array_value( $data, 'name' ) )
			->set_description( \smartcrawl_get_array_value( $data, 'description' ) )
			->set_configs( \smartcrawl_get_array_value( $data, 'configs' ) )
			->set_strings( \smartcrawl_get_array_value( $data, 'strings' ) )
			->set_editable( (bool) \smartcrawl_get_array_value( $data, 'editable' ) )
			->set_removable( (bool) \smartcrawl_get_array_value( $data, 'removable' ) )
			->set_official( (bool) \smartcrawl_get_array_value( $data, 'official' ) )
			->set_timestamp( (int) \smartcrawl_get_array_value( $data, 'timestamp' ) );
	}

	/**
	 * Deflates the object into an associative array.
	 *
	 * @return array The deflated object array.
	 */
	public function deflate() {
		return array(
			'id'          => $this->get_id(),
			'hub_id'      => $this->get_hub_id(),
			'name'        => $this->get_name(),
			'description' => $this->get_description(),
			'configs'     => $this->get_configs(),
			'strings'     => $this->get_strings(),
			'editable'    => $this->is_editable(),
			'removable'   => $this->is_removable(),
			'official'    => $this->is_official(),
			'timestamp'   => $this->get_timestamp(),
		);
	}
}