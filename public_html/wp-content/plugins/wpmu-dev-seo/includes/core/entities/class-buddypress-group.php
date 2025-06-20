<?php
/**
 * BuddyPress Group Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

use SmartCrawl\BuddyPress\Api;

/**
 * BuddyPress Group Entity.
 */
class BuddyPress_Group extends Entity {

	/**
	 * BuddPress API provider.
	 *
	 * @var API
	 */
	private $buddypress_api;

	/**
	 * BP Group Instance.
	 *
	 * @var \BP_Groups_Group
	 */
	private $buddypress_group;

	/**
	 * BP Group Name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * BP Group Description.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Class Constructor.
	 *
	 * @param \BP_Groups_Group|object $buddypress_group BP group.
	 */
	public function __construct( $buddypress_group ) {
		$this->buddypress_api   = new Api();
		$this->buddypress_group = $buddypress_group;
	}

	/**
	 * Loads meta title.
	 *
	 * @return string Meta title.
	 */
	protected function load_meta_title() {
		return $this->load_option_string_value(
			'bp_groups',
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return '%%bp_group_name%% %%sep%% %%sitename%%';
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
			'bp_groups',
			array( $this, 'load_meta_desc_from_options' ),
			function () {
				return '%%bp_group_description%%';
			}
		);
	}

	/**
	 * Loads robots meta tag.
	 *
	 * @return string Robots meta tag value.
	 */
	protected function load_robots() {
		$noindex  = $this->get_noindex_setting( 'bp_groups' ) ? 'noindex' : 'index';
		$nofollow = $this->get_nofollow_setting( 'bp_groups' ) ? 'nofollow' : 'follow';

		return "{$noindex},{$nofollow}";
	}

	/**
	 * Loads canonical URL.
	 *
	 * @return string Canonical URL if Buddypress group exists, otherwise returns an empty string.
	 */
	protected function load_canonical_url() {
		if ( ! $this->buddypress_group ) {
			return '';
		}

		return $this->buddypress_api->bp_get_group_permalink( $this->buddypress_group );
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
		return $this->is_opengraph_enabled_for_location( 'bp_groups' );
	}

	/**
	 * Loads OpenGraph title.
	 *
	 * @return string OpenGraph title.
	 */
	protected function load_opengraph_title() {
		return $this->load_option_string_value(
			'bp_groups',
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
			'bp_groups',
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
		$images = $this->load_opengraph_images_from_options( 'bp_groups' );

		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Loads enabled status for Twitter for BP Group.
	 *
	 * @return bool
	 */
	protected function load_twitter_enabled() {
		return $this->is_twitter_enabled_for_location( 'bp_groups' );
	}

	/**
	 * Loads Twitter title.
	 *
	 * @return string Twitter title.
	 */
	protected function load_twitter_title() {
		return $this->load_option_string_value(
			'bp_groups',
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
			'bp_groups',
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
		$images = $this->load_twitter_images_from_options( 'bp_groups' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Retrieves the name of the entity.
	 *
	 * If the name has not been loaded yet, loads it.
	 *
	 * @return string
	 */
	public function get_name() {
		if ( is_null( $this->name ) ) {
			$this->name = $this->load_name();
		}

		return $this->name;
	}

	/**
	 * Loads the name of the group.
	 *
	 * @return string The name of the group, or an empty string if the group is not set.
	 */
	private function load_name() {
		if ( ! $this->buddypress_group ) {
			return '';
		}

		return $this->buddypress_api->bp_get_group_name( $this->buddypress_group );
	}

	/**
	 * Retrieves the description.
	 *
	 * If the description is null, it will call the `load_description` method
	 * to load and set the description value before returning it.
	 *
	 * @return string The description.
	 */
	public function get_description() {
		if ( is_null( $this->description ) ) {
			$this->description = $this->load_description();
		}

		return $this->description;
	}

	/**
	 * Loads the description of the buddypress group.
	 * If the buddypress group is not set, return an empty string.
	 *
	 * @return string The description of the buddypress group.
	 */
	private function load_description() {
		if ( ! $this->buddypress_group ) {
			return '';
		}

		return $this->buddypress_api->bp_get_group_description( $this->buddypress_group );
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
			'%%bp_group_name%%'        => array( $this, 'get_name' ),
			'%%bp_group_description%%' => array( $this, 'get_description' ),
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