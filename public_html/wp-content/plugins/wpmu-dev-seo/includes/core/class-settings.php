<?php
/**
 * Main settings class file
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

/**
 * Settings hub class
 *
 * Used for getting/setting component and global settings.
 *
 * TODO: we don't need the *_specific_* methods now so remove them
 */
abstract class Settings extends Renderable {

	const COMP_AUTOLINKS = 'autolinks';

	const COMP_ONPAGE = 'onpage';

	const COMP_SOCIAL = 'social';

	const COMP_SCHEMA = 'schema';

	const COMP_SITEMAP = 'sitemap';

	const COMP_LIGHTHOUSE = 'lighthouse';

	const COMP_HEALTH = 'health';

	const COMP_ROBOTS = 'robots';

	const COMP_BREADCRUMBS = 'breadcrumb';

	const COMP_READABILITY = 'analysis-readability';

	const COMP_SEO = 'analysis-seo';

	const TAB_DASHBOARD = 'wds_wizard';

	const TAB_ONPAGE = 'wds_onpage';

	const TAB_SOCIAL = 'wds_social';

	const TAB_SCHEMA = 'wds_schema';

	const TAB_SITEMAP = 'wds_sitemap';

	const TAB_SETTINGS = 'wds_settings';

	const TAB_LIGHTHOUSE = 'wds_lighthouse';

	const TAB_HEALTH = 'wds_health';

	const ADVANCED_MODULE = 'wds-advanced';

	const AUTOLINKS_SUBMODULE   = 'autolinks';
	const REDIRECTS_SUBMODULE   = 'redirects';
	const WOOCOMMERCE_SUBMODULE = 'woocommerce';
	const BREADCRUMB_SUBMODULE  = 'breadcrumbs';
	const SEOMOZ_SUBMODULE      = 'seomoz';
	const ROBOTS_SUBMODULE      = 'robots';

	// todo: replace '_' with '-'.
	const SETTINGS_MODULE = 'wds_settings';

	/**
	 * Resets all options
	 *
	 * @return array Reset options
	 */
	public static function reset_options() {
		$list = self::get_known_tabs();

		foreach ( $list as $item ) {
			delete_site_option( "{$item}_options" );
			delete_option( "{$item}_options" );
		}

		return self::get_options();
	}

	/**
	 * Gets a list of known tabs
	 *
	 * @return array
	 */
	public static function get_known_tabs() {
		return array(
			self::TAB_DASHBOARD,
			self::TAB_SETTINGS,
			self::ADVANCED_MODULE,
			self::TAB_ONPAGE,
			self::TAB_SITEMAP,
			self::TAB_SOCIAL,
			self::TAB_HEALTH,
			self::TAB_LIGHTHOUSE,
		);
	}

	/**
	 * Options getter
	 *
	 * Use this to get rid of as much of `global` cancer as we can
	 *
	 * TODO: create a single model that includes all plugin settings and has methods for validating and saving changes
	 *
	 * @return array Options array
	 */
	public static function get_options() {
		$settings = self::get_local_settings();
		$onpage   = get_option( self::TAB_ONPAGE . '_options', array() );
		$sitemap  = get_option( self::TAB_SITEMAP . '_options', array() );
		$social   = get_option( self::TAB_SOCIAL . '_options', array() );

		return array_merge(
			(array) $settings,
			(array) $onpage,
			(array) $sitemap,
			(array) $social
		);
	}

	/**
	 * Explicit local site option getter
	 *
	 * @return array
	 */
	public static function get_local_settings() {
		return get_option( 'wds_settings_options', array() );
	}

	/**
	 * Gets contextual per-blog option or sitewide fallback
	 *
	 * @param string $key      Option name to check.
	 * @param mixed  $fallback What to return on failure.
	 *
	 * @return mixed Value or falback
	 */
	public static function get_setting( $key, $fallback = false ) {
		$options = self::get_local_settings();

		return isset( $options[ $key ] )
			? $options[ $key ]
			: $fallback;
	}

	/**
	 * Gets component-specific options
	 *
	 * @param string $component One of the known components (use class constants pl0x).
	 *
	 * @return array Component-specific options
	 */
	public static function get_component_options( $component ) {
		if ( empty( $component ) ) {
			return array();
		}
		if ( ! in_array( $component, self::get_all_components(), true ) ) {
			return array();
		}

		$options_key = "wds_{$component}_options";

		return (array) self::get_specific_options( $options_key );
	}

	/**
	 * Returns extended list of known components keys
	 *
	 * @return array Known components
	 */
	public static function get_all_components() {
		return array(
			self::COMP_ONPAGE,
			self::COMP_SCHEMA,
			self::COMP_SOCIAL,
			self::COMP_SITEMAP,
			self::COMP_HEALTH,
			self::COMP_LIGHTHOUSE,
		);
	}

	/**
	 * Gets component-specific options
	 *
	 * @param string $options_key   Specific options key we're after.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed Options
	 */
	public static function get_specific_options( $options_key, $default_value = array() ) {
		if ( empty( $options_key ) ) {
			return $default_value;
		}

		return get_option( $options_key, $default_value );
	}

	/**
	 * Updates component-specific options
	 *
	 * @param string $component One of the known components (use class constants pl0x).
	 * @param array  $options   Specific options we want to save.
	 */
	public static function update_component_options( $component, $options ) {
		if ( empty( $component ) ) {
			return array();
		}
		if ( ! in_array( $component, self::get_all_components(), true ) ) {
			return array();
		}

		$options_key = "wds_{$component}_options";

		return self::update_specific_options( $options_key, $options );
	}

	/**
	 * Updates component-specific options
	 *
	 * @param string $option_key Specific options key we're after.
	 * @param mixed  $options    Specific options we want to save.
	 */
	public static function update_specific_options( $option_key, $options ) {
		$old_values = self::get_specific_options( $option_key );

		$updated = update_option( $option_key, $options );

		if ( $updated ) {
			/**
			 * Action hook to trigger after updating specific options.
			 *
			 * @param array  $options    Option value.
			 * @param array  $old_values Old values.
			 * @param string $option_key Updated option key.
			 */
			do_action( 'smartcrawl_after_update_specific_options', $options, $old_values, $option_key );
		}

		return $updated;
	}

	/**
	 * Deletes component options.
	 *
	 * @param string $component Component key.
	 *
	 * @return array|bool
	 */
	public static function delete_component_options( $component ) {
		if ( empty( $component ) ) {
			return array();
		}
		if ( ! in_array( $component, self::get_all_components(), true ) ) {
			return array();
		}

		$options_key = "wds_{$component}_options";

		return self::delete_specific_options( $options_key );
	}

	/**
	 * Deletes a specific option by key.
	 *
	 * @param string $option_key Option key.
	 *
	 * @return bool
	 */
	public static function delete_specific_options( $option_key ) {
		return delete_option( $option_key );
	}

	/**
	 * Deactivates a component.
	 *
	 * @param string $component Component name.
	 *
	 * @return void
	 */
	public static function deactivate_component( $component ) {
		$options               = self::get_specific_options( 'wds_settings_options' );
		$options[ $component ] = 0;
		self::update_specific_options( 'wds_settings_options', $options );
	}

	/**
	 * Returns known components, as component => title pairs
	 *
	 * @return array Known components
	 */
	public static function get_known_components() {
		return array(
			self::COMP_ONPAGE  => __( 'Title & Meta Optimization', 'wds' ),
			self::COMP_SOCIAL  => __( 'Social', 'wds' ),
			self::COMP_SITEMAP => __( 'XML Sitemap', 'wds' ),
		);
	}

	/**
	 * Retrieves the current locale.
	 *
	 * @return string
	 */
	public static function get_locale() {
		return get_locale();
	}

	/**
	 * Get a key value from the values provided.
	 *
	 * @param string $key     Key.
	 * @param array  $values  Array of values.
	 * @param mixed  $default_value Default value.
	 *
	 * @return false|mixed
	 *
	 * @since 3.7.0
	 */
	public static function get_value( $key, $values = array(), $default_value = false ) {
		return isset( $values[ $key ] ) ? $values[ $key ] : $default_value;
	}
}