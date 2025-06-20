<?php
/**
 * Admin area setup stuff
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin\Settings;

use SmartCrawl\Controllers\Assets;
use SmartCrawl\Settings;
use SmartCrawl\Services\Service;

/**
 * Admin area instance page abstraction
 */
abstract class Admin_Settings extends Settings {

	/**
	 * Sections
	 *
	 * @var array
	 */
	public $sections = array();

	/**
	 * Settings corresponding to this page
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Capability required for this page
	 *
	 * @var string
	 */
	public $capability = 'manage_options';

	/**
	 * Page title.
	 *
	 * @var string|null
	 */
	public $page_title;

	/**
	 * Name of the options corresponding to this page
	 *
	 * @var string
	 */
	public $option_name = '';

	/**
	 * Page name
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Page slug
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Action URL
	 *
	 * @var string
	 */
	public $action_url = '';

	/**
	 * Action message
	 *
	 * @var string
	 */
	public $msg = '';

	/**
	 * Current page hook
	 *
	 * @var string
	 */
	public $smartcrawl_page_hook = '';

	/**
	 * Constructor
	 */
	protected function __construct() {
		$this->init();
	}

	/**
	 * Initializes the interface and binds hooks
	 */
	public function init() {
		$this->options = self::get_specific_options( $this->option_name );
		if ( is_multisite() && \smartcrawl_subsite_manager_role() === 'superadmin' ) {
			$this->capability = 'manage_network_options';
		}

		add_action( 'init', array( $this, 'defaults' ), 999 );
		add_filter( 'admin_body_class', array( $this, 'add_body_class' ), 20 );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Is current screen SmartCrawl.
	 *
	 * @return bool
	 */
	private function is_current_screen() {
		$screen = get_current_screen();

		return (
			! empty( $screen->id ) &&
			! empty( $this->smartcrawl_page_hook ) &&
			strpos( $screen->id, $this->smartcrawl_page_hook ) === 0
		);
	}

	/**
	 * Unified admin tab URL getter
	 *
	 * Also takes into account whether the tab is allowed or not
	 *
	 * @param string $tab Tab to check.
	 *
	 * @return string Unescaped admin URL, or tab anchor on failure
	 */
	public static function admin_url( $tab ) {
		$admin_url = esc_url_raw( add_query_arg( 'page', $tab, admin_url( 'admin.php' ) ) );

		if ( class_exists( '\WP_Defender\Model\Setting\Mask_Login' ) && ! is_user_logged_in() ) {
			$mask_login_model = new \WP_Defender\Model\Setting\Mask_Login();

			if ( $mask_login_model->is_active() ) {
				$admin_url = add_query_arg(
					'redirect_to',
					urlencode( $admin_url ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
					$mask_login_model->get_new_login_url()
				);
			}
		}

		return $admin_url;
	}

	/**
	 * Validation abstraction
	 *
	 * @param array $input Raw input to validate.
	 *
	 * @return array
	 */
	abstract public function validate( $input);

	/**
	 * Adds submenu page to the admin menu.
	 */
	public function add_page() {
		if ( ! $this->is_current_tab_allowed() ) {
			return;
		}

		$settings_opts = Settings::get_specific_options( Settings::SETTINGS_MODULE . '_options' );
		$hide_disables = \smartcrawl_get_array_value( $settings_opts, 'hide_disables', true );

		if ( 'schema' === $this->name ) {
			$social_opts = Settings::get_component_options( Settings::COMP_SOCIAL );
			$is_disabled = ! empty( $social_opts['disable-schema'] );
		} else {
			$is_disabled = isset( $settings_opts[ $this->name ] ) && empty( $settings_opts[ $this->name ] );
		}

		if ( $is_disabled && $hide_disables ) {
			return '';
		}

		$title = apply_filters( 'smartcrawl_admin_settings_submenu_title', $this->get_title(), $this->slug );
		$title = wp_kses( $title, array( 'span' => array( 'class' => array() ) ) );

		$this->smartcrawl_page_hook = add_submenu_page(
			'wds_wizard',
			$this->get_page_title(),
			$title,
			$this->capability,
			$this->slug,
			array( $this, 'options_page' )
		);

		// For pages that can deal with run requests, let's make sure they actually do that early enough.
		if ( is_callable( array( $this, 'process_run_action' ) ) ) {
			add_action( 'load-' . $this->smartcrawl_page_hook, array( $this, 'process_run_action' ) );
		}

		add_action( "admin_print_styles-{$this->smartcrawl_page_hook}", array( $this, 'admin_styles' ) );
	}

	/**
	 * Get title.
	 *
	 * @return mixed
	 */
	abstract public function get_title();

	/**
	 * Check if the current tab (settings page) is allowed for access
	 *
	 * @return bool
	 */
	protected function is_current_tab_allowed() {
		return ! empty( $this->slug ) && self::is_tab_allowed( $this->slug );
	}

	/**
	 * Check if a tab (settings page) is allowed for access
	 *
	 * It can be not allowed for access to site admins
	 *
	 * @param string $tab Tab to check.
	 *
	 * @return bool
	 */
	public static function is_tab_allowed( $tab ) {
		// On single installs, everything is good.
		if ( ! is_multisite() ) {
			return true;
		}

		// SEO health not supported on sub-sites.
		if ( self::TAB_HEALTH === $tab ) {
			return is_main_site();
		}

		// Dashboard shown on all sub-sites.
		if ( self::TAB_DASHBOARD === $tab ) {
			return true;
		}

		// Check whether the tab is blocked on network level.
		$allowed = \SmartCrawl\Admin\Settings\Settings::get_blog_tabs();
		$allowed = empty( $allowed ) ? array() : $allowed;

		return in_array( $tab, array_keys( $allowed ), true ) && ! empty( $allowed[ $tab ] );
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		wp_enqueue_style( Assets::APP_CSS );
	}

	/**
	 * Display the admin options page.
	 */
	public function options_page() {
		$this->msg = '';
		if ( ! empty( $_GET['updated'] ) || ! empty( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->msg = __( 'Settings updated', 'wds' );

			if ( function_exists( '\w3tc_pgcache_flush' ) ) {
				\w3tc_pgcache_flush();
				$this->msg .= __( ' &amp; W3 Total Cache Page Cache flushed', 'wds' );
			} elseif ( function_exists( '\wp_cache_clear_cache' ) ) {
				\wp_cache_clear_cache();
				$this->msg .= __( ' &amp; WP Super Cache flushed', 'wds' );
			}
		}

		$errors = get_settings_errors( $this->option_name );
		if ( $errors ) {
			set_transient( 'wds-settings-save-errors', $errors, 3 );
		}
	}

	/**
	 * Sets up contextual help
	 *
	 * @param string $contextual_help Help.
	 *
	 * @return string
	 */
	public function contextual_help( $contextual_help ) {
		$page = \smartcrawl_get_array_value( $_GET, 'page' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $page ) && $page === $this->slug && ! empty( $this->contextual_help ) ) {
			$contextual_help = $this->contextual_help;
		}

		return $contextual_help;
	}

	/**
	 * Adds body class
	 *
	 * @param string $classes Class that's being processed.
	 *
	 * @return string
	 */
	public function add_body_class( $classes ) {
		$sui_class = \smartcrawl_sui_class();
		if ( $this->is_current_screen() && strpos( $classes, $sui_class ) === false ) {
			$classes .= " {$sui_class} ";

			$service = Service::get( Service::SERVICE_SITE );
			if ( $service->is_member() ) {
				$classes .= ' wds-is-member';
			}
		}

		return $classes;
	}

	/**
	 * Renders the whole page view by calling `_render`
	 *
	 * As a side-effect, also calls `WDEV_Plugin_Ui::output()`
	 *
	 * @param string $view View file to load.
	 * @param array  $args Optional array of arguments to pass to view.
	 *
	 * @return bool
	 */
	public function render_page( $view, $args = array() ) {
		$this->render_view( $view, $args );

		return true;
	}

	/**
	 * Render settings fields.
	 *
	 * @param string $option_group Option group.
	 *
	 * @return void
	 */
	protected function settings_fields( $option_group ) {
		echo "<input type='hidden' name='option_page' value='" . esc_attr( $option_group ) . "' />";
		echo '<input type="hidden" name="action" value="update" />';
		wp_nonce_field( "$option_group-options", '_wpnonce', false );
	}

	/**
	 * Populates view defaults with view meta information
	 *
	 * @return array Defaults
	 */
	protected function get_view_defaults() {
		$errors  = get_transient( 'wds-settings-save-errors' );
		$errors  = ! empty( $errors ) ? $errors : array();
		$service = Service::get( Service::SERVICE_SITE );

		return array(
			'_view' => array(
				'slug'        => $this->slug,
				'name'        => $this->name,
				'option_name' => $this->option_name,
				'options'     => $this->options,
				'action_url'  => $this->action_url,
				'msg'         => $this->msg,
				'errors'      => $errors,
				'is_member'   => $service->is_member(),
			),
		);
	}

	/**
	 * Checks if the last active tab is stored in the transient and returns its value. If nothing is available then it returns the default value.
	 *
	 * @param string $default_tab Fallback value.
	 *
	 * @return string The last active tab.
	 */
	protected function get_active_tab( $default_tab = '' ) {
		return empty( $_GET['tab'] ) ? $default_tab : sanitize_text_field( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get page Title
	 *
	 * @return string
	 */
	public function get_page_title() {
		return str_replace( 'SmartCrawl', \smartcrawl_get_plugin_title(), $this->page_title );
	}
}