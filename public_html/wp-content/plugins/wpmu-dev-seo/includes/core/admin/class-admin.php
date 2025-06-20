<?php
/**
 * Admin side handling
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin;

use SmartCrawl\Controllers\Assets;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Admin\Settings as Admin_Settings;
use SmartCrawl\Controllers;

/**
 * Admin handling root class
 */
class Admin extends Controllers\Controller {

	use Singleton;

	/**
	 * Admin page handlers
	 *
	 * @var \SmartCrawl\Admin\Settings\Admin_Settings[]
	 */
	private $handlers = array();

	/**
	 * Initializing method
	 */
	protected function init() {
		// Set up dash.
		// TODO: dash setup probably needs its own controller.
		if ( file_exists( \SMARTCRAWL_PLUGIN_DIR . 'external/dash/wpmudev-dash-notification.php' ) ) {
			global $wpmudev_notices;
			if ( ! is_array( $wpmudev_notices ) ) {
				$wpmudev_notices = array();
			}
			$wpmudev_notices[] = array(
				'id'      => 167,
				'name'    => 'SmartCrawl',
				'screens' => array(
					'toplevel_page_wds_wizard-network',
					'toplevel_page_wds_wizard',
					'smartcrawl-pro_page_wds_health-network',
					'smartcrawl-pro_page_wds_health',
					'smartcrawl-pro_page_wds_onpage-network',
					'smartcrawl-pro_page_wds_onpage',
					'smartcrawl-pro_page_wds_schema-network',
					'smartcrawl-pro_page_wds_schema',
					'smartcrawl-pro_page_wds_social-network',
					'smartcrawl-pro_page_wds_social',
					'smartcrawl-pro_page_wds_sitemap-network',
					'smartcrawl-pro_page_wds_sitemap',
					'smartcrawl-pro_page_wds_advanced-network',
					'smartcrawl-pro_page_wds_advanced',
					'smartcrawl-pro_page_wds_settings-network',
					'smartcrawl-pro_page_wds_settings',
				),
			);
			require_once \SMARTCRAWL_PLUGIN_DIR . 'external/dash/wpmudev-dash-notification.php';
		}

		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_init', array( $this, 'manage_plugin_title' ) );

		add_filter( 'load-index.php', array( $this, 'enqueue_dashboard_resources' ), 20 );

		add_action( 'wp_ajax_wds_dismiss_message', array( $this, 'smartcrawl_dismiss_message' ) );

		$settings_opts = Settings::get_specific_options( Settings::SETTINGS_MODULE . '_options' );

		if ( ! isset( $settings_opts['extras-admin_bar'] ) || $settings_opts['extras-admin_bar'] ) {
			add_action( 'admin_bar_menu', array( $this, 'add_toolbar_items' ), 99 );
		}

		// Sanity check first!
		if ( ! get_option( 'blog_public' ) ) {
			add_action( 'admin_notices', array( $this, 'blog_not_public_notice' ) );
		}

		$this->handlers['dashboard'] = Admin_Settings\Dashboard::get();
		$this->handlers['health']    = Admin_Settings\Health::get();

		$hide_disables = \smartcrawl_get_array_value( $settings_opts, 'hide_disables', true );

		$modules = array(
			'onpage'  => Admin_Settings\Onpage::get(),
			'schema'  => Admin_Settings\Schema::get(),
			'social'  => Admin_Settings\Social::get(),
			'sitemap' => Admin_Settings\Sitemap::get(),
		);

		foreach ( $modules as $module_name => $module_handler ) {
			if ( 'schema' === $module_name ) {
				$social_opts = Settings::get_component_options( Settings::COMP_SOCIAL );
				$is_active   = ! isset( $social_opts['disable-schema'] ) || empty( $social_opts['disable-schema'] );
			} else {
				$is_active = ! isset( $settings_opts[ $module_name ] ) || ! empty( $settings_opts[ $module_name ] );
			}

			if ( $is_active || ! $hide_disables ) {
				$this->handlers[ $module_name ] = $module_handler;
			}
		}

		$this->handlers['settings'] = Admin_Settings\Settings::get();
	}

	/**
	 * Brute-register all the settings.
	 *
	 * If we got this far, this is a sane thing to do.
	 *
	 * In response to "Unable to save options multiple times" bug.
	 */
	public function register_setting() {
		$modules = array(
			'settings',
			'sitemap',
			'onpage',
			'social',
			'schema',
		);

		foreach ( $modules as $module ) {
			if ( $this->get_handler( $module ) ) {
				register_setting(
					"wds_{$module}_options",
					"wds_{$module}_options",
					array(
						$this->get_handler( $module ),
						'validate',
					)
				);
			}
		}
	}

	/**
	 * Applies white labeled plugin name if necessary.
	 */
	public function manage_plugin_title() {
		$modules = array(
			'settings',
			'sitemap',
			'onpage',
			'social',
			'schema',
		);

		foreach ( $modules as $module ) {
			if ( $this->get_handler( $module ) ) {
				register_setting(
					"wds_{$module}_options",
					"wds_{$module}_options",
					array(
						$this->get_handler( $module ),
						'validate',
					)
				);
			}
		}
	}

	/**
	 * Admin page handler getter
	 *
	 * @param string $handler Handler to get.
	 *
	 * @return \SmartCrawl\Admin\Settings\Admin_Settings|false
	 */
	public function get_handler( $handler ) {
		return isset( $this->handlers[ $handler ] )
			? $this->handlers[ $handler ]
			: false;
	}

	/**
	 * Adds admin toolbar items
	 *
	 * Todo: move this method to module controller once modularization is done.
	 *
	 * @param object $admin_bar Admin toolbar object.
	 *
	 * @return void
	 */
	public function add_toolbar_items( $admin_bar ) {
		if ( ! function_exists( '\is_admin_bar_showing' ) ) {
			return;
		}

		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! apply_filters( 'smartcrawl_admin_ui_show_bar', true ) ) {
			return;
		}

		// Do not show if only superadmin can view settings and the current user is not super admin.
		if (
			is_multisite()
			&& \smartcrawl_subsite_manager_role() === 'superadmin'
			&& ! current_user_can( 'manage_network_options' )
		) {
			return;
		}

		// On single site don't show for non-admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$optional_nodes = array();

		foreach ( $this->handlers as $handler ) {
			if ( empty( $handler->slug ) ) {
				continue;
			}

			if ( ! $this->is_admin_bar_node_allowed( $handler->slug ) ) {
				continue;
			}

			$optional_nodes[] = $this->create_admin_bar_node( $handler->slug, $handler->get_title() );
		}

		$optional_nodes = apply_filters( 'smartcrawl_admin_bar_menu', $optional_nodes );

		if ( ! empty( $optional_nodes ) ) {
			$admin_bar->add_node( $this->create_admin_bar_node( Settings::TAB_DASHBOARD, \smartcrawl_get_plugin_title() ) );
			$admin_bar->add_node( $this->create_admin_bar_node( Settings::TAB_DASHBOARD . '_dashboard', __( 'Dashboard', 'wds' ), Settings::TAB_DASHBOARD ) );

			foreach ( $optional_nodes as $optional_node ) {
				$admin_bar->add_node( $optional_node );
			}
		}
	}

	/**
	 * Checks if admin bar node is available.
	 *
	 * @param string $slug Node slug.
	 *
	 * @return bool
	 */
	private function is_admin_bar_node_allowed( $slug ) {
		if ( is_multisite() ) {
			return \SmartCrawl\Admin\Settings\Admin_Settings::is_tab_allowed( $slug );
		}

		return true;
	}

	/**
	 * Returns a admin bar node object as an array.
	 *
	 * @param string $id ID of the item.
	 * @param string $title Title of the node.
	 * @param string $slug Slug for the item.
	 *
	 * @return array
	 */
	private function create_admin_bar_node( $id, $title, $slug = '' ) {
		$node = array(
			'id'    => $id,
			'title' => $title,
			'href'  => sprintf(
				'%s?page=%s',
				admin_url( 'admin.php' ),
				empty( $slug ) ? $id : $slug
			),
		);

		if ( Settings::TAB_DASHBOARD !== $id ) {
			$node['parent'] = Settings::TAB_DASHBOARD;
		}

		return $node;
	}

	/**
	 * Validate user data for some/all of your input fields
	 *
	 * @param mixed $input Raw input.
	 *
	 * @return mixed
	 */
	public function validate( $input ) {
		return $input; // return validated input.
	}

	/**
	 * Shows blog not being public notice.
	 *
	 * TODO: probably not the right class for this method. We can probably make a separate controller for admin messages and the dismiss message.
	 */
	public function blog_not_public_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$message = sprintf(
			'%1$s <a href="%3$s">%2$s</a>',
			esc_html__( 'This site discourages search engines from indexing the pages, which will affect your SEO efforts.', 'wds' ),
			esc_html__( 'You can fix this here', 'wds' ),
			admin_url( '/options-reading.php' )
		);

		echo '<div class="notice-error notice is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	/**
	 * Process message dismissal request
	 */
	public function smartcrawl_dismiss_message() {
		$data    = $this->get_request_data();
		$message = sanitize_key( \smartcrawl_get_array_value( $data, 'message' ) );

		if ( empty( $message ) ) {
			wp_send_json_error();
		}

		$dismissed_messages             = get_user_meta( get_current_user_id(), 'wds_dismissed_messages', true );
		$dismissed_messages             = '' === $dismissed_messages ? array() : $dismissed_messages;
		$dismissed_messages[ $message ] = true;

		update_user_meta( get_current_user_id(), 'wds_dismissed_messages', $dismissed_messages );

		$page = \smartcrawl_get_array_value( $data, 'page' );

		do_action( 'smartcrawl_dismissed_message', $message, $page );

		wp_send_json_success();
	}

	/**
	 * TODO: we should remove widgets from the WordPress dashboard making dashboard resources unnecessary.
	 */
	public function enqueue_dashboard_resources() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_css' ) );
	}

	/**
	 * Enqueues a CSS stylesheet.
	 *
	 * @return void
	 */
	public function enqueue_dashboard_css() {
		wp_enqueue_style( Assets::WP_DASHBOARD_CSS );
	}

	/**
	 * Retrieves POST Request data.
	 *
	 * @return array|mixed
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-admin-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}
}