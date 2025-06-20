<?php
/**
 * BP Profile Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

use SmartCrawl\BuddyPress\Api;

/**
 * BuddyPress_Profile Entity.
 */
class BuddyPress_Profile extends Entity {

	/**
	 * BP API Provider.
	 *
	 * @var Api
	 */
	private $buddypress_api;

	/**
	 * WordPress User instance.
	 *
	 * @var \WP_User
	 */
	private $wp_user;

	/**
	 * User name.
	 *
	 * @var string
	 */
	private $username;

	/**
	 * User's display name.
	 *
	 * @var string
	 */
	private $display_name;

	/**
	 * Class constructor.
	 *
	 * @param \WP_User $wp_user WP user object.
	 */
	public function __construct( $wp_user ) {
		$this->wp_user        = $wp_user;
		$this->buddypress_api = new Api();
	}

	/**
	 * Loads meta title.
	 *
	 * @return string Meta title.
	 */
	protected function load_meta_title() {
		return $this->load_option_string_value(
			'bp_profile',
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return '%%bp_user_username%% %%sep%% %%sitename%%';
			}
		);
	}

	/**
	 * Loads meta description.
	 *
	 * @return string Meta description.
	 */
	protected function load_meta_description() {
		return $this->load_option_string_value(
			'bp_profile',
			array( $this, 'load_meta_desc_from_options' ),
			function () {
				return '%%bp_user_full_name%%';
			}
		);
	}

	/**
	 * Loads robots meta tag.
	 *
	 * @return string Robots meta tag value.
	 */
	protected function load_robots() {
		$noindex  = $this->get_noindex_setting( 'bp_profile' ) ? 'noindex' : 'index';
		$nofollow = $this->get_nofollow_setting( 'bp_profile' ) ? 'nofollow' : 'follow';

		return "{$noindex},{$nofollow}";
	}

	/**
	 * Loads canonical URL.
	 *
	 * @return string Canonical URL if Buddypress group exists, otherwise returns an empty string.
	 */
	protected function load_canonical_url() {
		if ( ! $this->wp_user ) {
			return '';
		}

		return $this->buddypress_api->bp_core_get_user_domain( $this->wp_user->ID );
	}

	/**
	 * Loads schema.
	 *
	 * @return array The schema array.
	 */
	protected function load_schema() {
		return array();
	}

	/**
	 * Loads OpenGraph enabled value for BP Groups.
	 *
	 * @return bool Indicates if OpenGraph is enabled for BP Groups.
	 */
	protected function load_opengraph_enabled() {
		return $this->is_opengraph_enabled_for_location( 'bp_profile' );
	}

	/**
	 * Loads OpenGraph title.
	 *
	 * @return string OpenGraph title.
	 */
	protected function load_opengraph_title() {
		return $this->load_option_string_value(
			'bp_profile',
			array( $this, 'load_opengraph_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads OpenGraph description.
	 *
	 * @return string OpenGraph description.
	 */
	protected function load_opengraph_description() {
		return $this->load_option_string_value(
			'bp_profile',
			array( $this, 'load_opengraph_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads OpenGraph images for the groups.
	 *
	 * @return array Array of image URLs.
	 */
	protected function load_opengraph_images() {
		$images = $this->load_opengraph_images_from_options( 'bp_profile' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Loads enabled status for Twitter.
	 *
	 * @return bool
	 */
	protected function load_twitter_enabled() {
		return $this->is_twitter_enabled_for_location( 'bp_profile' );
	}

	/**
	 * Loads Twitter title.
	 *
	 * @return string Twitter title.
	 */
	protected function load_twitter_title() {
		return $this->load_option_string_value(
			'bp_profile',
			array( $this, 'load_twitter_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads Twitter description.
	 *
	 * @return string Twitter description.
	 */
	protected function load_twitter_description() {
		return $this->load_option_string_value(
			'bp_profile',
			array( $this, 'load_twitter_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads Twitter images.
	 *
	 * @return array List of Twitter image URLs.
	 */
	protected function load_twitter_images() {
		$images = $this->load_twitter_images_from_options( 'bp_profile' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Retrieves the username.
	 *
	 * @return string The username.
	 */
	public function get_username() {
		if ( is_null( $this->username ) ) {
			$this->username = $this->load_username();
		}

		return $this->username;
	}

	/**
	 * Loads the username of the current user.
	 *
	 * @return string The username of the current user.
	 */
	private function load_username() {
		if ( ! $this->wp_user ) {
			return '';
		}

		return $this->buddypress_api->bp_core_get_username( $this->wp_user->ID );
	}

	/**
	 * Retrieves the display name.
	 *
	 * If the display name is null, it is loaded from the load_display_name() method
	 * and stored in the $display_name property.
	 *
	 * @return string The display name or null if it is not set.
	 */
	public function get_display_name() {
		if ( is_null( $this->display_name ) ) {
			$this->display_name = $this->load_display_name();
		}

		return $this->display_name;
	}

	/**
	 * Loads the display name.
	 *
	 * If the WordPress user object $wp_user is not set, it returns an empty string.
	 * Otherwise, it calls the bp_core_get_user_displayname() method of the $buddypress_api object
	 * passing the ID of the WordPress user as the argument and returns the result.
	 *
	 * @return string The display name or an empty string if the WordPress user object is not set.
	 */
	private function load_display_name() {
		if ( ! $this->wp_user ) {
			return '';
		}

		return $this->buddypress_api->bp_core_get_user_displayname( $this->wp_user->ID );
	}

	/**
	 * Returns the macros.
	 *
	 * @param string $subject The subject of the macros.
	 *
	 * @return array An array of macros.
	 */
	public function get_macros( $subject = '' ) {
		return array(
			'%%bp_user_username%%'  => array( $this, 'get_username' ),
			'%%bp_user_full_name%%' => array( $this, 'get_display_name' ),
		);
	}

	/**
	 * Sets the Buddypress API.
	 *
	 * @param API $api The Buddypress API provider.
	 *
	 * @return void
	 */
	public function set_buddypress_api( $api ) {
		$this->buddypress_api = $api;
	}
}