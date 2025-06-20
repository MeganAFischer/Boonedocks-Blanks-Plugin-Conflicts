<?php
/**
 * Printer class for outputting JSON+LD schema.org data to the page in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Entities\Entity;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Work_Unit;

/**
 * Class Printer
 *
 * Outputs JSON+LD schema.org data to the page.
 */
class Printer extends Work_Unit {

	use Singleton;

	/**
	 * Indicates if the schema injection is running.
	 *
	 * @var bool
	 */
	private $is_running = false;

	/**
	 * Indicates if the schema injection is done.
	 *
	 * @var bool
	 */
	private $is_done = false;

	/**
	 * Boot the hooking part.
	 *
	 * @return void
	 */
	public static function run() {
		self::get()->add_hooks();
	}

	/**
	 * Dispatches the schema injection.
	 *
	 * @return bool True if the schema was injected, false otherwise.
	 */
	public function dispatch_schema_injection() {
		if ( ! ! $this->is_done ) {
			return false;
		}

		if ( $this->is_schema_disabled() ) {
			$this->is_done = true;

			return false; // Disabled.
		}

		$entity = \SmartCrawl\Endpoint_Resolver::resolve()->get_queried_entity();
		if ( ! $entity ) {
			return false;
		}

		$data = $entity->get_schema();
		if ( empty( $data ) ) {
			return false;
		}

		/**
		 * Filter to modify final schema data.
		 *
		 * @param array  $data   Schema data.
		 * @param Entity $entity Entity.
		 */
		$data = apply_filters( 'wds_schema_printer_schema_data', $data, $entity );

		$this->is_done = true;

		echo '<script type="application/ld+json">' .
			wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $data,
				)
			) . "</script>\n";
	}

	/**
	 * Retrieves the filter prefix.
	 *
	 * @return string The filter prefix.
	 */
	public function get_filter_prefix() {
		return 'wds-schema';
	}

	/**
	 * Adds items to the admin bar menu.
	 *
	 * @param \WP_Admin_Bar $admin_bar The admin bar instance.
	 *
	 * @return \WP_Admin_Bar The updated admin bar instance.
	 */
	public function admin_bar_menu_items( $admin_bar ) {
		$schema_options = Settings::get_component_options( Settings::COMP_SCHEMA );
		if (
			is_admin()
			|| ! current_user_can( 'manage_options' )
			|| $this->is_schema_disabled()
			|| empty( $schema_options['schema_enable_test_button'] )
		) {
			return $admin_bar;
		}

		// Do not show if only superadmin can view settings and the current user is not super admin.
		if (
			is_multisite()
			&& \smartcrawl_subsite_manager_role() === 'superadmin'
			&& ! current_user_can( 'manage_network_options' )
		) {
			return $admin_bar;
		}

		$url = esc_url_raw( 'http' . ( isset( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidatedNotSanitized
		$admin_bar->add_menu(
			array(
				'id'    => 'smartcrawl-test-item',
				'title' => __( 'Test Schema', 'wds' ),
				'href'  => sprintf( 'https://search.google.com/test/rich-results?url=%s&user_agent=2', urlencode( $url ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
				'meta'  => array(
					'title'  => __( 'Test Schema', 'wds' ),
					'target' => '_blank',
				),
			)
		);

		return $admin_bar;
	}

	/**
	 * Adds hooks for schema injection.
	 *
	 * @return bool True if hooks were added, false otherwise.
	 */
	private function add_hooks() {
		// Do not double-bind.
		if ( $this->apply_filters( 'is_running', $this->is_running ) ) {
			return true;
		}

		add_action(
			'wp_head',
			array(
				$this,
				'dispatch_schema_injection',
			),
			50
		);
		add_action(
			'smartcrawl_head_after_output',
			array(
				$this,
				'dispatch_schema_injection',
			)
		);
		add_action(
			'admin_bar_menu',
			array(
				$this,
				'admin_bar_menu_items',
			),
			99
		);

		$this->is_running = true;
	}

	/**
	 * Checks if the schema is disabled.
	 *
	 * @return bool True if the schema is disabled, false otherwise.
	 */
	private function is_schema_disabled() {
		$social = Settings::get_component_options( Settings::COMP_SOCIAL );

		return ! empty( $social['disable-schema'] )
			|| ! Admin_Settings::is_tab_allowed( Settings::TAB_SCHEMA );
	}
}