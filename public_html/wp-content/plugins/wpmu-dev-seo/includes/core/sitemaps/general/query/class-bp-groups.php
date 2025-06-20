<?php
/**
 * BP_Groups class for handling BuddyPress groups in SmartCrawl sitemaps.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\General\Queries;

use SmartCrawl\Settings;
use SmartCrawl\Sitemaps\Query;
use SmartCrawl\Singleton;
use SmartCrawl\Sitemaps\Utils;
use SmartCrawl\Sitemaps\General\Item;

/**
 * Class BP_Groups
 *
 * Handles the retrieval and processing of BuddyPress groups for sitemaps.
 */
class BP_Groups extends Query {

	use Singleton;

	const TYPE = 'bp_groups';

	/**
	 * Returns the supported types.
	 *
	 * @return string[] The supported types.
	 */
	public function get_supported_types() {
		return array( self::TYPE );
	}

	/**
	 * Retrieves the items for the given type and page number.
	 *
	 * @param string $type The type of items to retrieve.
	 * @param int    $page_number The page number for pagination.
	 *
	 * @return array|Item[] The retrieved items.
	 */
	public function get_items( $type = '', $page_number = 0 ) {
		if ( ! $this->can_return_items() ) {
			return array();
		}

		$groups = $this->get_groups( $page_number );
		$items  = array();

		foreach ( $groups as $group ) {
			$url = bp_get_group_permalink( $group );
			if ( $this->is_group_excluded( $group ) || Utils::is_url_ignored( $url ) ) {
				continue;
			}

			$item = new Item();
			$item->set_location( $url )
				->set_last_modified( strtotime( $group->last_activity ) )
				->set_images( $this->get_group_images( $group ) );

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Retrieves the images associated with a group.
	 *
	 * @param object $group The group object.
	 *
	 * @return array The images.
	 */
	private function get_group_images( $group ) {
		if ( ! Utils::sitemap_images_enabled() ) {
			return array();
		}

		$markup  = $group->description;
		$markup .= $this->get_group_avatar( $group );
		$images  = $this->find_images( $markup );

		$cover = $this->get_group_cover_url( $group );
		if ( $cover ) {
			$images[] = array(
				'src'   => $cover,
				'title' => '',
				'alt'   => '',
			);
		}

		return $images;
	}

	/**
	 * Retrieves the avatar of a group.
	 *
	 * @param object $group The group object.
	 *
	 * @return string The group avatar HTML.
	 */
	private function get_group_avatar( $group ) {
		return function_exists( '\bp_core_fetch_avatar' )
			? \bp_core_fetch_avatar(
				array(
					'item_id' => $group->id,
					'object'  => 'group',
					'type'    => 'full',
					'html'    => true,
				)
			)
			: '';
	}

	/**
	 * Retrieves the cover URL of a group.
	 *
	 * @param object $group The group object.
	 *
	 * @return string The group cover URL.
	 */
	private function get_group_cover_url( $group ) {
		return function_exists( '\bp_attachments_get_attachment' )
			? \bp_attachments_get_attachment(
				'url',
				array(
					'object_dir' => 'groups',
					'item_id'    => $group->id,
				)
			)
			: '';
	}

	/**
	 * Checks if the type can be handled.
	 *
	 * @param string $type The type to check.
	 *
	 * @return bool True if the type can be handled, false otherwise.
	 */
	public function can_handle_type( $type ) {
		return parent::can_handle_type( $type )
			&& $this->can_return_items();
	}

	/**
	 * Checks if items can be returned.
	 *
	 * @return bool True if items can be returned, false otherwise.
	 */
	private function can_return_items() {
		return defined( '\BP_VERSION' )
			&& \smartcrawl_is_main_bp_site()
			&& function_exists( '\groups_get_groups' )
			&& function_exists( '\bp_get_group_permalink' )
			&& $this->bp_groups_enabled();
	}

	/**
	 * Returns the filter prefix.
	 *
	 * @return string The filter prefix.
	 */
	public function get_filter_prefix() {
		return 'wds-sitemap-bp_groups';
	}

	/**
	 * Checks if BuddyPress groups are enabled.
	 *
	 * @return bool True if BuddyPress groups are enabled, false otherwise.
	 */
	private function bp_groups_enabled() {
		$options = $this->get_options();

		return ! empty( $options['sitemap-buddypress-groups'] );
	}

	/**
	 * Retrieves the groups for the given page number.
	 *
	 * @param int $page_number The page number for pagination.
	 *
	 * @return \BP_Groups_Group[] The retrieved groups.
	 */
	private function get_groups( $page_number ) {
		$per_page = $this->get_limit( $page_number );

		$groups = groups_get_groups(
			array(
				'per_page' => $per_page,
				'page'     => $page_number,
				'orderby'  => 'last_activity',
				'order'    => 'ASC',
			)
		);

		return ! empty( $groups['groups'] ) ? $groups['groups'] : array();
	}

	/**
	 * Checks if a group is excluded.
	 *
	 * @param object $group The group object.
	 *
	 * @return bool True if the group is excluded, false otherwise.
	 */
	private function is_group_excluded( $group ) {
		$options = $this->get_options();

		return ! empty( $options[ "sitemap-buddypress-exclude-buddypress-group-$group->slug" ] );
	}

	/**
	 * Retrieves the options.
	 *
	 * @return array The options.
	 */
	private function get_options() {
		return Settings::get_options();
	}
}