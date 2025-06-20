<?php
/**
 * Health settings
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin\Settings;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Controllers\Assets;
use SmartCrawl\Entities;
use SmartCrawl\Lighthouse\Options;
use SmartCrawl\Lighthouse\Renderer;
use SmartCrawl\Services\Lighthouse;
use SmartCrawl\Services\Service;

/**
 * Health
 */
class Health extends Admin_Settings {

	use Singleton;

	/**
	 * Validate.
	 *
	 * @param array $input Input.
	 *
	 * @return array
	 */
	public function validate( $input ) {
		return array();
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function init() {
		$this->option_name = 'wds_health_options';
		$this->name        = Settings::COMP_HEALTH;
		$this->slug        = Settings::TAB_HEALTH;
		$this->action_url  = admin_url( 'options.php' );
		$this->page_title  = sprintf(
			/* translators: %s: plugin title */
			__( '%s Wizard: SEO Health', 'wds' ),
			\smartcrawl_get_plugin_title()
		);

		add_action( 'wp_ajax_wds-save-health-settings', array( $this, 'save_health_settings' ) );

		parent::init();

		remove_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ), 93 );
	}

	/**
	 * Save health settings.
	 *
	 * @return void
	 */
	public function save_health_settings() {
		$data = $this->get_request_data();
		if ( empty( $data ) ) {
			wp_send_json_error();
		}

		Options::save_form_data( wp_unslash( $_GET['wds_health_options'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

		wp_send_json_success();
	}

	/**
	 * Get the title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'SEO Health', 'wds' );
	}

	/**
	 * Render the page content.
	 *
	 * @return void
	 */
	public function options_page() {
		wp_enqueue_script( Assets::LIGHTHOUSE_JS );

		$lighthouse = Service::get( Service::SERVICE_LIGHTHOUSE );

		$device      = empty( $_GET['device'] ) ? 'desktop' : sanitize_text_field( wp_unslash( $_GET['device'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$last_report = get_option( Lighthouse::OPTION_ID_LAST_REPORT, false );

		$image_url = sprintf( '%s/assets/images/empty-box.svg', SMARTCRAWL_PLUGIN_URL );
		$image_url = \SmartCrawl\Controllers\White_Label::get()->get_wpmudev_hero_image( $image_url );

		if ( empty( $last_report ) ) {
			$report_data = array(
				'no_data' => true,
				'image'   => $image_url,
			);
		} elseif ( ! empty( $last_report['error'] ) ) {
			$report_data = array(
				'error'   => \smartcrawl_get_array_value( $last_report, 'code' ),
				'message' => \smartcrawl_get_array_value( $last_report, 'message' ),
			);
		} else {
			$device_report = \smartcrawl_get_array_value( $last_report, array( 'data', $device ) );
			if ( ! $device_report ) {
				$report_data = array(
					'error' => 'unexpected-error',
				);
			} else {
				$report_data = \smartcrawl_get_array_value( $device_report, array( 'metrics' ) );
			}
		}

		$page_on_front = get_option( 'page_on_front' );
		$show_on_front = get_option( 'show_on_front' );

		$has_static_homepage = 'posts' !== $show_on_front && $page_on_front;

		if ( ! $has_static_homepage || ! current_user_can( 'edit_page', $page_on_front ) ) {
			$homepage_url = '';
		} else {
			$homepage_url = get_edit_post_link( $page_on_front );
		}

		$posts_on_front = 'posts' === $show_on_front || 0 === (int) $page_on_front;

		if ( $posts_on_front ) {
			$home = new Entities\Blog_Home();
		} else {
			$home = new Entities\Product( $page_on_front );
		}

		$home_robots = $home->get_robots();

		$service = new \SmartCrawl\Configs\Service();

		$args = array(
			'start_time' => $lighthouse->get_start_time(),
			'report'     => $report_data,
			'nonce'      => wp_create_nonce( 'wds-lighthouse-nonce' ),
		);

		if ( ! isset( $report_data['error'] ) && ! isset( $report_data['no_data'] ) ) {
			$args = array_merge(
				$args,
				array(
					'homepage_url'             => $homepage_url,
					'timestamp'                => \smartcrawl_get_array_value( $last_report, array( 'data', 'time' ) ),
					'testing_tool'             => sprintf( 'https://search.google.com/test/rich-results?url=%s&user_agent=2', urlencode( home_url() ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
					'admin_url'                => admin_url(),
					'plugin_install_url'       => is_multisite() && is_super_admin() ?
						network_admin_url( 'plugin-install.php?s=hreflang&tab=search&type=term' ) :
						( current_user_can( 'install_plugins' ) ?
							admin_url( 'plugin-install.php?s=hreflang&tab=search&type=term' ) :
							false ),
					'is_tab_onpage_allowed'    => Admin_Settings::is_tab_allowed( Settings::TAB_ONPAGE ),
					'tab_onpage_url'           => Admin_Settings::admin_url( Settings::TAB_ONPAGE ),
					'is_tab_autolinks_allowed' => Admin_Settings::is_tab_allowed( Settings::ADVANCED_MODULE ),
					'tab_autolinks_url'        => Admin_Settings::admin_url( Settings::ADVANCED_MODULE ),
					'is_tab_schema_allowed'    => Admin_Settings::is_tab_allowed( Settings::TAB_SCHEMA ),
					'tab_schema_url'           => Admin_Settings::admin_url( Settings::TAB_SCHEMA ),
					'is_multisite'             => ! ! is_multisite(),
					'is_blog_public'           => ! ! get_option( 'blog_public' ),
					'is_home_no_index'         => strpos( $home_robots, 'noindex' ) !== false,
				)
			);
		}

		wp_localize_script( Assets::LIGHTHOUSE_JS, '_wds_lighthouse', $args );

		$this->render_view( 'lighthouse/lighthouse-settings' );
	}

	/**
	 * Save defaults.
	 *
	 * @return void
	 */
	public function defaults() {
		Options::save_defaults();
	}

	/**
	 * Get view defaults.
	 *
	 * @return array
	 */
	protected function get_view_defaults() {
		$mode_defaults = Renderer::get()->view_defaults();

		return array_merge(
			array(
				'active_tab' => $this->get_active_tab( 'tab_lighthouse' ),
			),
			$mode_defaults,
			parent::get_view_defaults()
		);
	}

	/**
	 * Get request data.
	 *
	 * @return array
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-health-nonce' ) ? $_POST : array();
	}
}