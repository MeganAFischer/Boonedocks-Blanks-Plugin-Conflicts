<?php
/**
 * BP_Profile class for handling BuddyPress profile sitemaps in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\General\Queries;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Sitemaps\General\Item;
use SmartCrawl\Sitemaps\Query;
use SmartCrawl\Sitemaps\Utils;

/**
 * Class BP_Profile
 *
 * Handles the generation of BuddyPress profile sitemap items.
 */
class BP_Profile extends Query {

	use Singleton;

	const TYPE = 'bp_profile';

	/**
	 * Retrieves the supported types.
	 *
	 * @return string[] The supported types.
	 */
	public function get_supported_types() {
		return array( self::TYPE );
	}

	/**
	 * Retrieves the list of items for the sitemap.
	 *
	 * @param string $type The type of items to retrieve.
	 * @param int    $page_number The page number.
	 *
	 * @return array|Item[] The list of items.
	 */
	public function get_items( $type = '', $page_number = 0 ) {
		if ( ! $this->can_return_items() ) {
			return array();
		}

		$users = $this->get_users( $page_number );
		$items = array();
		foreach ( $users as $user ) {
			$url = bp_core_get_user_domain( $user->id );
			if ( $this->is_role_excluded( $user ) || Utils::is_url_ignored( $url ) ) {
				continue;
			}

			$item = new Item();
			$item->set_location( $url )
				->set_last_modified( strtotime( $user->last_activity ) )
				->set_images( $this->get_user_images( $user->id ) );

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Determines if the type can be handled.
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
	 * Retrieves the list of users.
	 *
	 * @param int $page_number The page number.
	 *
	 * @return array|mixed The list of users.
	 */
	private function get_users( $page_number ) {
		$per_page = $this->get_limit( $page_number );

		add_filter( 'bp_user_query_uid_clauses', array( $this, 'order_asc' ) );

		$users = bp_core_get_users(
			array(
				'per_page' => $per_page,
				'page'     => $page_number,
			)
		);

		remove_filter(
			'bp_user_query_uid_clauses',
			array(
				$this,
				'order_asc',
			)
		);

		return ! empty( $users['users'] )
			? $users['users']
			: array();
	}

	/**
	 * Orders the user query in ascending order.
	 *
	 * @param array $sql The SQL clauses.
	 *
	 * @return mixed The modified SQL clauses.
	 */
	public function order_asc( $sql ) {
		$sql['order'] = 'ASC';

		return $sql;
	}

	/**
	 * Determines if items can be returned.
	 *
	 * @return bool True if items can be returned, false otherwise.
	 */
	private function can_return_items() {
		return defined( '\BP_VERSION' )
			&& \smartcrawl_is_main_bp_site()
			&& function_exists( '\bp_core_get_users' )
			&& function_exists( '\bp_core_get_user_domain' )
			&& $this->bp_profile_enabled();
	}

	/**
	 * Retrieves the filter prefix.
	 *
	 * @return string The filter prefix.
	 */
	public function get_filter_prefix() {
		return 'wds-sitemap-bp_profile';
	}

	/**
	 * Retrieves the options.
	 *
	 * @return array The options.
	 */
	private function get_options() {
		return Settings::get_options();
	}

	/**
	 * Determines if BuddyPress profiles are enabled.
	 *
	 * @return bool True if BuddyPress profiles are enabled, false otherwise.
	 */
	private function bp_profile_enabled() {
		$options = $this->get_options();

		return ! empty( $options['sitemap-buddypress-profiles'] );
	}

	/**
	 * Determines if the user's role is excluded.
	 *
	 * @param object $user The user object.
	 *
	 * @return bool True if the user's role is excluded, false otherwise.
	 */
	private function is_role_excluded( $user ) {
		$wp_user = new \WP_User( $user->id );
		$role    = array_shift( $wp_user->roles );
		if ( empty( $role ) ) {
			return false;
		}
		$options = $this->get_options();

		return ! empty( $options[ "sitemap-buddypress-roles-exclude-profile-role-$role" ] );
	}

	/**
	 * Retrieves the user's images.
	 *
	 * @param int $id The user ID.
	 *
	 * @return array The user's images.
	 */
	private function get_user_images( $id ) {
		if ( ! Utils::sitemap_images_enabled() ) {
			return array();
		}

		$avatar = $this->get_user_avatar( $id );
		$images = $this->find_images( $avatar );

		$cover = $this->get_user_cover_url( $id );
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
	 * Retrieves the user's avatar.
	 *
	 * @param int $id The user ID.
	 *
	 * @return string The user's avatar URL.
	 */
	private function get_user_avatar( $id ) {
		return function_exists( '\bp_core_fetch_avatar' )
			? \bp_core_fetch_avatar(
				array(
					'item_id' => $id,
					'object'  => 'user',
					'type'    => 'full',
					'html'    => true,
				)
			)
			: '';
	}

	/**
	 * Retrieves the user's cover URL.
	 *
	 * @param int $id The user ID.
	 *
	 * @return string|void The user's cover URL.
	 */
	private function get_user_cover_url( $id ) {
		return function_exists( '\bp_attachments_get_attachment' )
			? \bp_attachments_get_attachment(
				'url',
				array(
					'object_dir' => 'members',
					'item_id'    => $id,
				)
			)
			: '';
	}
}