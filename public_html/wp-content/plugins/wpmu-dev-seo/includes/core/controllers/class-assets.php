<?php
/**
 * Class to control assets.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Admin\Pages\Network_Settings;
use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Admin\Settings\Onpage as Onpage_Settings;
use SmartCrawl\Admin\Settings\Sitemap;
use SmartCrawl\Cache\Post_Cache;
use SmartCrawl\Configs;
use SmartCrawl\Entities;
use SmartCrawl\Lighthouse;
use SmartCrawl\Schema;
use SmartCrawl\Services\Service;
use SmartCrawl\Settings;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Singleton;
use SmartCrawl\SmartCrawl;
use SmartCrawl\Third_Party_Import\AIOSEOP;
use WP_Post;
use WP_Taxonomy;
use function smartcrawl_frontend_post_types;
use function smartcrawl_frontend_taxonomies;
use function smartcrawl_get_array_value;
use function smartcrawl_get_news_sitemap_url;
use function smartcrawl_get_sitemap_url;
use function smartcrawl_get_value;
use function smartcrawl_is_switch_active;
use function smartcrawl_metadesc_max_length;
use function smartcrawl_metadesc_min_length;
use function smartcrawl_title_max_length;
use function smartcrawl_title_min_length;
use function smartcrawl_woocommerce_active;
use function user_can_see_seo_metabox_301_redirect;

/**
 * Assets controller.
 */
class Assets extends Controller {

	use Singleton;

	const SUI_JS = 'wds-shared-ui';

	const ADMIN_JS = 'wds-admin';

	const OPENGRAPH_JS = 'wds-admin-opengraph';

	const QTIP2_JS = 'wds-qtip2-script';

	const MACRO_REPLACEMENT = 'wds-macro-replacement';

	const ONPAGE_COMPONENTS = 'wds-onpage-components';

	const ONPAGE_JS = 'wds-admin-onpage';

	const SITEMAPS_PAGE_JS = 'wds-admin-sitemaps';

	const DASHBOARD_PAGE_JS = 'wds-admin-dashboard';

	const ONBOARDING_JS = 'wds-onboard';

	const EMAIL_RECIPIENTS_JS = 'wds-admin-email-recipients';

	const SOCIAL_PAGE_JS = 'wds-admin-social';

	const CONFIGS_JS = 'wds-configs';

	const THIRD_PARTY_IMPORT_JS = 'wds-third-party-import';

	const SETTINGS_PAGE_JS = 'wds-admin-settings';

	const NETWORK_SETTINGS_PAGE_JS = 'wds-admin-network-settings';

	const METABOX_COUNTER_JS = 'wds-metabox-counter';

	const METABOX_COMPONENTS_JS = 'wds-metabox-components';

	const METABOX_LINK_FORMAT_BUTTON = 'wds-link-format-button';

	const METABOX_LINK_REL_ATTRIBUTE_FIELD = 'wds-link-rel-attribute-field';

	const WP_POST_LIST_TABLE_JS = 'wds-admin-post-list-table';

	const WP_POST_LIST_TABLE_CSS = 'wds-admin-post-list-table-styling';

	const TERM_FORM_JS = 'wds-term-form';

	const QTIP2_CSS = 'wds-qtip2-style';

	const APP_CSS = 'wds-app';

	const WP_DASHBOARD_CSS = 'wds-wp-dashboard';

	const SCHEMA_JS = 'wds-admin-schema';

	const SCHEMA_TYPES_JS = 'wds-schema-types';

	const WELCOME_JS = 'wds-welcome-modal';

	const HEALTH_JS = 'wds-admin-health';

	const LIGHTHOUSE_JS = 'wds-admin-lighthouse';

	/**
	 * Binds listening actions.
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), - 10 );
	}

	/**
	 * Check if it's correct page.
	 *
	 * @param string $page Page name.
	 *
	 * @return bool
	 */
	private function is_page( $page ) {
		return $this->is_smartcrawl_page() && smartcrawl_get_array_value( $_GET, 'page' ) === $page; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Check if current page is SmartCrawl page.
	 *
	 * @return bool
	 */
	private function is_smartcrawl_page() {
		global $pagenow;
		$page = (string) smartcrawl_get_array_value( $_GET, 'page' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 'admin.php' === $pagenow && ( str_starts_with( $page, 'wds_' ) || str_starts_with( $page, 'wds-' ) );
	}

	/**
	 * Register assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		$this->register_general_scripts();

		// SmartCrawl pages.
		$this->register_onpage_page_scripts();
		$this->register_sitemap_page_scripts();
		$this->register_dashboard_page_scripts();
		$this->register_social_page_scripts();
		$this->register_settings_page_scripts();
		$this->register_network_settings_page_scripts();
		$this->register_schema_settings_page_scripts();
		$this->register_health_settings_page_scripts();

		// WP pages.
		$this->register_metabox_scripts();
		$this->register_post_list_scripts();
		$this->register_term_form_scripts();

		// CSS.
		$this->register_general_styles();
		$this->register_wp_dashboard_styles();
		$this->register_post_list_styles();
	}

	/**
	 * Get version to be used for JS and Css files.
	 *
	 * @return string
	 */
	private function get_version() {
		$value = SmartCrawl::get_version();
		if ( defined( 'SMARTCRAWL_BUILD' ) ) {
			$value = sprintf( '%s-%s', $value, SMARTCRAWL_BUILD );
		}

		return $value;
	}

	/**
	 * Register Javascript.
	 *
	 * @param string      $handle Name of the script. Should be unique.
	 * @param bool|string $src    Relative URL of the script.
	 * @param string[]    $deps   Optional. An array of registered script handles this script depends on. Default empty array.
	 *
	 * @return void
	 */
	private function register_js( $handle, $src, $deps = array() ) {
		wp_register_script( $handle, $this->base_url( $src ), $deps, $this->get_version(), true );
	}

	/**
	 * Register a CSS stylesheet.
	 *
	 * @param string      $handle Name of the stylesheet. Should be unique.
	 * @param bool|string $src    Relative URL of the stylesheet.
	 * @param string[]    $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 *
	 * @return void
	 */
	private function register_css( $handle, $src, $deps = array() ) {
		wp_register_style( $handle, $this->base_url( $src ), $deps, $this->get_version() );
	}

	/**
	 * Get asset's full URL from plugin's base url.
	 *
	 * @param string $url Relative url of assets.
	 *
	 * @return string
	 */
	private function base_url( $url ) {
		return trailingslashit( SMARTCRAWL_PLUGIN_URL ) . "assets/$url";
	}

	/**
	 * Register generally required scripts.
	 *
	 * @return void
	 */
	private function register_general_scripts() {
		if (
			! $this->is_smartcrawl_page() &&
			! $this->is_post_list_page() &&
			! $this->is_term_edit_page() &&
			! $this->is_post_edit_screen()
		) {
			return;
		}

		// Shared UI.
		$this->register_js(
			self::SUI_JS,
			'js/build/shared-ui.min.js',
			array(
				'jquery',
				'clipboard',
			)
		);

		// Common JS functions and utils.
		$this->register_js(
			self::ADMIN_JS,
			'js/wds-admin.js',
			array(
				self::SUI_JS,
				'jquery',
			)
		);

		$dismissed_messages = get_user_meta( get_current_user_id(), 'wds_dismissed_messages', true );

		if ( empty( $dismissed_messages ) ) {
			$dismissed_messages = array();
		}

		$empty_box_img = sprintf( '%s/assets/images/empty-box.svg', untrailingslashit( SMARTCRAWL_PLUGIN_URL ) );
		$empty_box_img = White_Label::get()->get_wpmudev_hero_image( $empty_box_img );

		/* translators: %s: Heart icon */
		$footer_text = White_Label::get()->get_wpmudev_footer_text( sprintf( esc_html__( 'Made with %s by WPMU DEV', 'wds' ), '<span class="sui-icon-heart" aria-hidden="true" aria-label="love"></span>' ) );

		wp_localize_script(
			self::ADMIN_JS,
			'_wds_admin',
			array(
				'strings'            => array(
					'initializing' => esc_html__( 'Initializing ...', 'wds' ),
					'running'      => esc_html__( 'Running SEO checks ...', 'wds' ),
					'finalizing'   => esc_html__( 'Running final checks and finishing up ...', 'wds' ),
					'characters'   => esc_html__( 'characters', 'wds' ),
				),
				'home_url'           => trailingslashit( home_url() ),
				'plugin_url'         => untrailingslashit( SMARTCRAWL_PLUGIN_URL ),
				'plugin_title'       => \smartcrawl_get_plugin_title(),
				'nonce'              => wp_create_nonce( 'wds-admin-nonce' ),
				'referer'            => remove_query_arg( '_wp_http_referer' ),
				'version'            => str_replace( '.', '-', SMARTCRAWL_SUI_VERSION ),
				'dismissed_messages' => $dismissed_messages,
				'is_member'          => Service::get( Service::SERVICE_SITE )->is_member(),
				'settings_nonce'     => wp_create_nonce( 'wds-settings-nonce' ),
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'rest_url'           => get_rest_url(),
				'plugins_url'        => admin_url( 'plugins.php' ),
				'post_types'         => array_map(
					function ( $post_type ) {
						return get_post_type_object( $post_type )->labels->singular_name;
					},
					smartcrawl_frontend_post_types()
				),
				'new_feature_status' => Settings::get_specific_options( 'wds-features-viewed', 0 ),
				'empty_box_logo'     => $empty_box_img,
				'footer_text'        => $footer_text,
			)
		);

		$this->register_js(
			self::QTIP2_JS,
			'js/external/jquery.qtip.min.js',
			array(
				'jquery',
			)
		);
	}

	/**
	 * Register scripts for Title & Meta page.
	 *
	 * @return void
	 */
	private function register_onpage_page_scripts() {
		if ( ! $this->is_page( Settings::TAB_ONPAGE ) ) {
			return;
		}

		$this->register_common_scripts();
	}

	/**
	 * Register scripts for Sitemaps page.
	 *
	 * @return void
	 */
	private function register_sitemap_page_scripts() {
		if ( ! $this->is_page( Settings::TAB_SITEMAP ) ) {
			return;
		}

		$this->register_email_recipients_js();

		wp_localize_script(
			self::EMAIL_RECIPIENTS_JS,
			'_wds_email_recipients',
			array(
				'id'         => 'wds-sitemap-email-recipients',
				'recipients' => Sitemap::get_email_recipients(),
				'field_name' => 'wds_sitemap_options[sitemap-email-recipients]',
			)
		);

		$sitemap_deps = $this->dynamic_dependencies(
			self::SITEMAPS_PAGE_JS,
			array(
				'jquery',
				self::ADMIN_JS,
				self::EMAIL_RECIPIENTS_JS,
			)
		);
		$this->register_js( self::SITEMAPS_PAGE_JS, 'js/build/wds-admin-sitemaps.min.js', $sitemap_deps );

		$this->set_script_translations( self::SITEMAPS_PAGE_JS );

		wp_localize_script(
			self::SITEMAPS_PAGE_JS,
			'_wds_sitemaps',
			array(
				'nonce'       => wp_create_nonce( 'wds-nonce' ),
				'sitemap_url' => smartcrawl_get_sitemap_url(),
				'strings'     => array(
					'manually_updated'          => esc_html__( 'Your sitemap has been updated.', 'wds' ),
					'manually_notified_engines' => esc_html__( 'Search Engines are being notified with changes.', 'wds' ),
				),
			)
		);

		$seo_service = Service::get( Service::SERVICE_SEO );
		$report      = $seo_service->get_report();

		wp_localize_script(
			self::SITEMAPS_PAGE_JS,
			'_wds_crawler',
			array(
				'nonce'              => wp_create_nonce( 'wds-crawler-nonce' ),
				'issues'             => $report->get_all_issues_grouped_by_type(),
				'advanced_tools_url' => Admin_Settings::admin_url( Settings::ADVANCED_MODULE ) . '&tab=' . Settings::REDIRECTS_SUBMODULE,
				'redirects'          => \smartcrawl_get_array_value( get_option( Settings::ADVANCED_MODULE ), 'redirects', array() ),
			)
		);

		$social_options  = Settings::get_component_options( Settings::COMP_SOCIAL );
		$schema_disabled = ! empty( $social_options['disable-schema'] );

		wp_localize_script(
			self::SITEMAPS_PAGE_JS,
			'_wds_news',
			array_merge(
				array(
					'news_sitemap_url' => smartcrawl_get_news_sitemap_url(),
					'schema_enabled'   => ! $schema_disabled,
				),
				$this->get_news_sitemap_data()
			)
		);
	}

	/**
	 * Register scripts for News sitemap.
	 *
	 * @return array
	 */
	private function get_news_sitemap_data() {
		$settings = Settings::get_component_options( Settings::COMP_SITEMAP );
		$data     = new \SmartCrawl\Sitemaps\News\Data();

		return $data->settings_to_data( $settings );
	}

	/**
	 * Register scripts for Dashboard page.
	 *
	 * @return void
	 */
	private function register_dashboard_page_scripts() {
		if ( ! $this->is_page( Settings::TAB_DASHBOARD ) ) {
			return;
		}

		$this->register_configs_script();

		$this->register_js(
			self::ONBOARDING_JS,
			'js/wds-admin-onboard.js',
			array(
				self::ADMIN_JS,
			)
		);

		wp_localize_script(
			self::ONBOARDING_JS,
			'_wds_onboard',
			array(
				'templates' => array(
					'progress' => Simple_Renderer::load( 'dashboard/onboard-progress' ),
				),
				'strings'   => array(
					'All done' => esc_html__( 'All done, please hold on...', 'wds' ),
				),
				'nonce'     => wp_create_nonce( 'wds-onboard-nonce' ),
			)
		);

		$this->register_js(
			self::WELCOME_JS,
			'js/wds-welcome-modal.js',
			array(
				self::ADMIN_JS,
			)
		);

		$nonce = wp_create_nonce( 'wds-nonce' );
		wp_localize_script(
			self::WELCOME_JS,
			'_wds_welcome',
			array(
				'nonce'             => $nonce,
				'advanced_page_url' => Admin_Settings::admin_url( Settings::ADVANCED_MODULE ),
			)
		);

		$this->register_js(
			self::DASHBOARD_PAGE_JS,
			'js/build/wds-admin-dashboard.min.js',
			array(
				'jquery',
				'underscore',
				self::ADMIN_JS,
				self::ONBOARDING_JS,
				self::WELCOME_JS,
			)
		);

		wp_localize_script(
			self::DASHBOARD_PAGE_JS,
			'_wds_dashboard',
			array(
				'nonce'                    => $nonce,
				'health_page_url'          => Admin_Settings::admin_url( Settings::TAB_HEALTH ),
				'lighthouse_widget_device' => Lighthouse\Options::dashboard_widget_device(),
				'lighthouse_nonce'         => wp_create_nonce( 'wds-lighthouse-nonce' ),
			)
		);
	}

	/**
	 * Register scripts for Social page.
	 *
	 * @return void
	 */
	private function register_social_page_scripts() {
		if ( ! $this->is_page( Settings::TAB_SOCIAL ) ) {
			return;
		}

		$this->register_js(
			self::SOCIAL_PAGE_JS,
			'js/wds-admin-social.js',
			array(
				'jquery',
				self::ADMIN_JS,
			)
		);

		wp_localize_script(
			self::SOCIAL_PAGE_JS,
			'_wds_social',
			array(
				'nonce' => wp_create_nonce( 'wds-social-nonce' ),
			)
		);
	}

	/**
	 * Get timezone string.
	 *
	 * @return string
	 */
	private function get_timezone() {
		$timezone = get_option( 'timezone_string' );
		if ( ! empty( $timezone ) ) {
			return $timezone;
		}

		$offset = (int) get_option( 'gmt_offset', 0 );

		return $offset < 0 ? "UTC$offset" : "UTC+$offset";
	}

	/**
	 * Register scripts for Settings page.
	 *
	 * @return void
	 */
	private function register_settings_page_scripts() {
		if ( ! $this->is_page( Settings::TAB_SETTINGS ) ) {
			return;
		}

		$this->register_configs_script();

		$import_deps = $this->dynamic_dependencies(
			self::THIRD_PARTY_IMPORT_JS,
			array(
				self::ADMIN_JS,
			)
		);
		$this->register_js(
			self::THIRD_PARTY_IMPORT_JS,
			'js/build/wds-third-party-import.min.js',
			$import_deps
		);

		$this->set_script_translations( self::THIRD_PARTY_IMPORT_JS );

		$aioseo_importer = new AIOSEOP();

		wp_localize_script(
			self::THIRD_PARTY_IMPORT_JS,
			'_wds_import',
			array(
				'nonce'               => wp_create_nonce( 'wds-io-nonce' ),
				'aioseop_data_exists' => (bool) $aioseo_importer->data_exists(),
				'is_multisite'        => (bool) is_multisite(),
				'index_settings_url'  => admin_url( 'admin.php?page=wds_onpage' ),
			)
		);

		$setting_deps = $this->dynamic_dependencies(
			self::SETTINGS_PAGE_JS,
			array(
				'jquery',
				self::ADMIN_JS,
				self::THIRD_PARTY_IMPORT_JS,
			)
		);
		$this->register_js( self::SETTINGS_PAGE_JS, 'js/build/wds-admin-settings.min.js', $setting_deps );

		$this->set_script_translations( self::SETTINGS_PAGE_JS );

		wp_localize_script(
			self::SETTINGS_PAGE_JS,
			'_wds_reset',
			array(
				'nonce'           => wp_create_nonce( 'wds-data-reset-nonce' ),
				'multisite_nonce' => wp_create_nonce( 'wds-multisite-data-reset-nonce' ),
			)
		);
	}

	/**
	 * Register scripts for metabox.
	 *
	 * @return void
	 */
	private function register_metabox_scripts() {
		if ( ! $this->is_post_edit_screen() ) {
			return;
		}

		$this->register_opengraph_script();
		$this->register_macro_replacement_script();

		if ( $this->is_block_editor_active() ) {
			$link_format_button_deps = $this->dynamic_dependencies(
				self::METABOX_LINK_FORMAT_BUTTON,
				array(
					'wp-block-editor',
					'wp-i18n',
					'wp-element',
					'wp-components',
					'wp-rich-text',
					'wp-html-entities',
					'wp-element',
					'wp-components',
					'wp-url',
				)
			);

			$this->register_js( self::METABOX_LINK_FORMAT_BUTTON, 'js/build/wds-link-format-button.min.js', $link_format_button_deps );
		} else {
			$this->register_js(
				self::METABOX_LINK_REL_ATTRIBUTE_FIELD,
				'js/wds-link-rel-attribute-field.js',
				array(
					'jquery',
					self::ADMIN_JS,
				)
			);
			wp_localize_script(
				self::METABOX_LINK_REL_ATTRIBUTE_FIELD,
				'_wds_link_rel_attr',
				array(
					'templates' => array(
						'field' => Simple_Renderer::load( 'metabox/underscore-link-rel-attribute' ),
					),
				)
			);
		}

		$options = Settings::get_options();
		if ( ! $this->is_block_editor_active() ) {
			$this->register_js( self::METABOX_COUNTER_JS, 'js/wds-metabox-counter.js' );
			wp_localize_script(
				self::METABOX_COUNTER_JS,
				'l10nWdsCounters',
				array(
					'title_length'       => esc_html__( '{TOTAL_LEFT} characters left', 'wds' ),
					'title_longer'       => esc_html__( 'Over {MAX_COUNT} characters ({CURRENT_COUNT})', 'wds' ),
					'main_title_longer'  => esc_html__( 'Over {MAX_COUNT} characters ({CURRENT_COUNT}) - make sure your SEO title is shorter', 'wds' ),
					'title_min'          => smartcrawl_title_min_length(),
					'title_max'          => smartcrawl_title_max_length(),
					'metadesc_min'       => smartcrawl_metadesc_min_length(),
					'metadesc_max'       => smartcrawl_metadesc_max_length(),
					'main_title_warning' => ! defined( 'SMARTCRAWL_MAIN_TITLE_LENGTH_WARNING_HIDE' ) || ! \SMARTCRAWL_MAIN_TITLE_LENGTH_WARNING_HIDE,
				)
			);
		}

		$post_type   = $this->get_post_type();
		$title       = (string) smartcrawl_get_array_value( $options, 'title-' . $post_type );
		$description = (string) smartcrawl_get_array_value( $options, 'metadesc-' . $post_type );
		$post_id     = $this->get_post_id_query_var();

		$metabox_deps = $this->dynamic_dependencies(
			self::METABOX_COMPONENTS_JS,
			array(
				'jquery',
				'underscore',
				'wp-api',
				'wp-api-fetch',
				'wp-date',
				self::ADMIN_JS,
				self::OPENGRAPH_JS,
				self::MACRO_REPLACEMENT,
			)
		);

		if ( $this->is_block_editor_active() ) {
			if ( smartcrawl_is_switch_active( 'SMARTCRAWL_SHOW_GUTENBERG_LINK_FORMAT_BUTTON' ) ) {
				$metabox_deps[] = self::METABOX_LINK_FORMAT_BUTTON;
			}
		} else {
			$metabox_deps[] = self::METABOX_LINK_REL_ATTRIBUTE_FIELD;
			$metabox_deps[] = self::METABOX_COUNTER_JS;
			$metabox_deps[] = 'autosave';
		}

		$this->register_js( self::METABOX_COMPONENTS_JS, 'js/build/wds-metabox-components.min.js', $metabox_deps );

		$args = array(
			'nonce'                => wp_create_nonce( 'wds-metabox-nonce' ),
			'nonce_name'           => '_wds_nonce',
			'referer'              => remove_query_arg( '_wp_http_referer' ),
			'site_title'           => get_bloginfo( 'name' ),
			'site_description'     => get_bloginfo( 'description' ),
			'meta_title'           => $title,
			'meta_desc'            => $description,
			'seo_title'            => smartcrawl_get_value( 'title', $post_id ),
			'seo_desc'             => smartcrawl_get_value( 'metadesc', $post_id ),
			'home_url'             => home_url( '/' ),
			'post_url'             => $post_id ? get_permalink( $post_id ) : '',
			'title_min_length'     => smartcrawl_title_min_length(),
			'title_max_length'     => smartcrawl_title_max_length(),
			'metadesc_min_length'  => smartcrawl_metadesc_min_length(),
			'metadesc_max_length'  => smartcrawl_metadesc_max_length(),
			'post_type'            => $this->get_post_type(),
			'taxonomies'           => $this->get_taxonomies(),
			'gutenberg_active'     => $this->is_block_editor_active(),
			'primary_terms_active' => Primary_Terms::get()->should_run(),
			'onpage_active'        => Settings::get_setting( 'onpage' ) && Admin_Settings::is_tab_allowed( Settings::TAB_ONPAGE ),
			'readability_active'   => Settings::get_setting( 'analysis-readability' ),
			'seo_active'           => Settings::get_setting( 'analysis-seo' ),
			'enforce_limits'       => isset( $options['metabox-lax_enforcement'] ) && (bool) $options['metabox-lax_enforcement'],
			'macros'               => array_merge(
				Onpage_Settings::get_singular_macros( $this->get_post_type() ),
				Onpage_Settings::get_general_macros()
			),
			'focus_keywords'       => smartcrawl_get_value( 'focus-keywords', $post_id ),
			'tab_onpage_url'       => Admin_Settings::admin_url( Settings::TAB_ONPAGE ),
			'social_active'        => Settings::get_setting( Settings::COMP_SOCIAL ) && Admin_Settings::is_tab_allowed( Settings::TAB_SOCIAL ),
		);

		$show_on_front  = get_option( 'show_on_front' );
		$page_on_front  = (int) get_option( 'page_on_front' );
		$page_for_posts = (int) get_option( 'page_for_posts' );
		if ( 'page' === $show_on_front && ( $page_on_front === (int) $post_id || $page_for_posts === (int) $post_id ) ) {
			$post_type = 'home';
		}

		$og_setting_enabled   = (bool) smartcrawl_get_array_value( $options, 'og-enable' );
		$og_post_type_enabled = (bool) smartcrawl_get_array_value( $options, 'og-active-' . $post_type );

		if ( $og_setting_enabled && $og_post_type_enabled ) {
			$cached_post = Post_Cache::get()->get_post( $post_id );

			if ( $cached_post ) {
				$og = smartcrawl_get_value( 'opengraph', $cached_post->get_post_id() );

				if ( ! is_array( $og ) ) {
					$og = array();
				}

				$og_args = array(
					'disabled' => (bool) smartcrawl_get_array_value( $og, 'disabled' ),
				);

				if ( ! empty( $og['title'] ) ) {
					$og_args['title_value'] = $og['title'];
				}

				$title_placeholder = $cached_post->get_opengraph_title();

				if ( $title_placeholder ) {
					$og_args['title_placeholder'] = $title_placeholder;
				}

				if ( ! empty( $og['description'] ) ) {
					$og_args['desc_value'] = $og['description'];
				}

				$desc_placeholder = $cached_post->get_opengraph_description();

				if ( $desc_placeholder ) {
					$og_args['desc_placeholder'] = $desc_placeholder;
				}

				if ( ! empty( $og['images'] ) ) {
					$og_args['images'] = array();

					foreach ( $og['images'] as $img_id ) {
						$img_src = wp_get_attachment_image_src( $img_id );

						if ( $img_src ) {
							$og_args['images'][] = array(
								'id'  => $img_id,
								'url' => $img_src[0],
							);
						}
					}
				}

				$args['opengraph'] = $og_args;
			}
		}

		$twitter_post_type_enabled = (bool) smartcrawl_get_array_value( $options, 'twitter-active-' . get_post_type( $post_id ) );
		$twitter_setting_enabled   = (bool) smartcrawl_get_array_value( $options, 'twitter-card-enable' );
		if ( $twitter_post_type_enabled && $twitter_setting_enabled ) {
			$cached_post = Post_Cache::get()->get_post( $post_id );

			if ( $cached_post ) {
				$twitter = smartcrawl_get_value( 'twitter', $post_id );

				if ( ! is_array( $twitter ) ) {
					$twitter = array();
				}

				$twt_args = array(
					'disabled' => (bool) smartcrawl_get_array_value( $twitter, 'disabled' ),
				);

				if ( ! empty( $twitter['title'] ) ) {
					$twt_args['title_value'] = $twitter['title'];
				}

				$title_placeholder = $cached_post->get_twitter_title();

				if ( $title_placeholder ) {
					$twt_args['title_placeholder'] = $title_placeholder;
				}

				if ( ! empty( $twitter['description'] ) ) {
					$twt_args['desc_value'] = $twitter['description'];
				}

				$desc_placeholder = $cached_post->get_twitter_description();

				if ( $desc_placeholder ) {
					$twt_args['desc_placeholder'] = $desc_placeholder;
				}

				if ( ! empty( $twitter['images'] ) ) {
					$twt_args['images'] = array();

					foreach ( $twitter['images'] as $img_id ) {
						$img_src = wp_get_attachment_image_src( $img_id );

						if ( $img_src ) {
							$twt_args['images'][] = array(
								'id'  => $img_id,
								'url' => $img_src[0],
							);
						}
					}
				}

				$args['twitter'] = $twt_args;
			}
		}

		$args['advanced'] = array(
			'indexing'  => array(
				'robots_noindex'       => (int) smartcrawl_get_value( 'meta-robots-noindex', $post_id ),
				'robots_nofollow'      => (int) smartcrawl_get_value( 'meta-robots-nofollow', $post_id ),
				'robots_index'         => (int) smartcrawl_get_value( 'meta-robots-index', $post_id ),
				'robots_follow'        => (int) smartcrawl_get_value( 'meta-robots-follow', $post_id ),
				'robots_values'        => array_filter( explode( ',', smartcrawl_get_value( 'meta-robots-adv', $post_id ) ) ),
				'post_type_noindexed'  => (bool) smartcrawl_get_array_value( $options, sprintf( 'meta_robots-noindex-%s', get_post_type( $post_id ) ) ),
				'post_type_nofollowed' => (bool) smartcrawl_get_array_value( $options, sprintf( 'meta_robots-nofollow-%s', get_post_type( $post_id ) ) ),
			),
			'canonical' => array(
				'url' => smartcrawl_get_value( 'canonical', $post_id ),
			),
		);

		if ( \SmartCrawl\Modules\Advanced\Controller::get()->should_run() ) {
			if ( \SmartCrawl\Modules\Advanced\Redirects\Controller::get()->should_run() ) {
				$args['advanced']['redirect'] = array(
					'has_permission' => user_can_see_seo_metabox_301_redirect(),
				);

				$redirect_url = smartcrawl_get_value( 'redirect', $post_id );

				if ( $redirect_url ) {
					$args['advanced']['redirect']['url'] = $redirect_url;
				}
			}

			if ( \SmartCrawl\Modules\Advanced\Autolinks\Controller::get()->should_run() ) {
				$args['advanced']['autolinks'] = array(
					'exclude' => smartcrawl_get_value( 'autolinks-exclude', $post_id ),
				);
			}
		}

		wp_localize_script(
			self::METABOX_COMPONENTS_JS,
			'_wds_metabox',
			$args
		);
	}

	/**
	 * Get macro replacements.
	 *
	 * @return array
	 */
	private function get_replacements() {
		$general_macros = Onpage_Settings::get_general_macros();
		$queried_entity = new Entities\Blog_Home();
		$map            = array();
		foreach ( $general_macros as $macro => $macro_desc ) {
			$map[ str_replace( '%', '', $macro ) ] = $queried_entity->apply_macros( $macro );
		}

		return $map;
	}

	/**
	 * Return if block editor is active or not.
	 *
	 * @return bool
	 */
	private function is_block_editor_active() {
		$screen = get_current_screen();
		if ( $screen && method_exists( $screen, 'is_block_editor' ) ) {
			return $screen->is_block_editor();
		}

		if ( function_exists( '\is_gutenberg_page' ) ) {
			return \is_gutenberg_page();
		}

		return false;
	}

	/**
	 * Check if current page is post list page.
	 *
	 * @return bool
	 */
	private function is_post_list_page() {
		global $pagenow;

		return 'edit.php' === $pagenow;
	}

	/**
	 * Register post list scripts.
	 *
	 * @return void
	 */
	private function register_post_list_scripts() {
		if ( ! $this->is_post_list_page() ) {
			return;
		}

		$this->register_js(
			self::WP_POST_LIST_TABLE_JS,
			'js/wds-admin-post-list-table.js',
			array(
				'jquery',
				'underscore',
				self::ADMIN_JS,
				self::QTIP2_JS,
			)
		);

		wp_localize_script(
			self::WP_POST_LIST_TABLE_JS,
			'_wds_post_list',
			array(
				'strings'             => array(
					'loading' => __( 'Loading, please hold on...', 'wds' ),
				),
				'nonce'               => wp_create_nonce( 'wds-metabox-nonce' ),
				'analyse_posts_delay' => (int) apply_filters( 'wds-list-table-delay', 500 ),
			)
		);
	}

	/**
	 * Check if current page is term edit page or not.
	 *
	 * @return bool
	 */
	private function is_term_edit_page() {
		global $pagenow;

		return 'term.php' === $pagenow;
	}

	/**
	 * Register scripts for term form.
	 *
	 * @return void
	 */
	private function register_term_form_scripts() {
		if ( ! $this->is_term_edit_page() ) {
			return;
		}

		$this->register_common_scripts();

		$this->register_js(
			self::TERM_FORM_JS,
			'js/wds-term-form.js',
			array(
				'jquery',
				self::ADMIN_JS,
				self::OPENGRAPH_JS,
			)
		);
		wp_localize_script(
			self::TERM_FORM_JS,
			'_wds_term_form',
			array(
				'nonce' => wp_create_nonce( 'wds-metabox-nonce' ),
			)
		);
	}

	/**
	 * Register styles for generally used.
	 *
	 * @return void
	 */
	private function register_general_styles() {
		if (
			! $this->is_smartcrawl_page() &&
			! $this->is_post_list_page() &&
			! $this->is_term_edit_page() &&
			! $this->is_post_edit_screen()
		) {
			return;
		}

		$this->register_css( self::QTIP2_CSS, 'css/external/jquery.qtip.min.css' );

		wp_enqueue_style( self::APP_CSS, $this->base_url( 'css/app.min.css' ), array(), $this->get_version() );
	}

	/**
	 * Register style sfor WP dashboard.
	 *
	 * @return void
	 */
	private function register_wp_dashboard_styles() {
		$this->register_css( self::WP_DASHBOARD_CSS, 'css/wp-dashboard.min.css' );
	}

	/**
	 * Register styles for post list.
	 *
	 * @return void
	 */
	private function register_post_list_styles() {
		$this->register_css(
			self::WP_POST_LIST_TABLE_CSS,
			'css/wp-post-list-table.min.css',
			array( self::QTIP2_CSS )
		);
	}

	/**
	 * Register scripts for network settings.
	 *
	 * @return void
	 */
	private function register_network_settings_page_scripts() {
		if ( ! $this->is_page( Network_Settings::MENU_SLUG ) ) {
			return;
		}

		$this->register_js(
			self::NETWORK_SETTINGS_PAGE_JS,
			'js/wds-admin-network-settings.js',
			array(
				'jquery',
				self::ADMIN_JS,
			)
		);
	}

	/**
	 * Check if current page is post edit page or not.
	 *
	 * @return bool
	 */
	private function is_post_edit_screen() {
		global $pagenow;

		return 'post-new.php' === $pagenow || 'post.php' === $pagenow;
	}

	/**
	 * Get p ost id from URL query string.
	 *
	 * @return mixed|null
	 */
	private function get_post_id_query_var() {
		return smartcrawl_get_array_value( $_GET, 'post' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get post type for current post.
	 *
	 * @return string
	 */
	private function get_post_type() {
		$post = get_post();

		return ( $post instanceof WP_Post ) ? $post->post_type : 'post';
	}

	/**
	 * Get taxonomies for current post type.
	 *
	 * @return string[]|WP_Taxonomy[]
	 */
	private function get_taxonomies() {
		$post_type = $this->get_post_type();

		return get_object_taxonomies( $post_type );
	}

	/**
	 * Register scripts for Schema page.
	 *
	 * @return void
	 */
	private function register_schema_settings_page_scripts() {
		if ( ! $this->is_page( Settings::TAB_SCHEMA ) ) {
			return;
		}

		$this->register_js(
			self::SCHEMA_JS,
			'js/wds-admin-schema.js',
			array(
				'jquery',
				self::ADMIN_JS,
			)
		);

		wp_localize_script(
			self::SCHEMA_JS,
			'_wds_schema',
			array(
				'nonce'               => wp_create_nonce( 'wds-schema-nonce' ),
				'youtube_key_valid'   => esc_html__( 'Key valid!', 'wds' ),
				'youtube_key_invalid' => esc_html__( 'Key invalid', 'wds' ),
			)
		);

		$post_types     = array_map(
			function ( $post_type ) {
				return get_post_type_object( $post_type )->labels->singular_name;
			},
			smartcrawl_frontend_post_types()
		);
		$post_formats   = $this->get_post_formats();
		$page_templates = wp_get_theme()->get_page_templates();
		$user_roles     = array_map(
			function ( $role ) {
				return smartcrawl_get_array_value( $role, 'name' );
			},
			wp_roles()->roles
		);
		$taxonomies     = array_map(
			function ( $taxonomy ) {
				return $taxonomy->label;
			},
			smartcrawl_frontend_taxonomies()
		);

		$schema_types_deps = $this->dynamic_dependencies(
			self::SCHEMA_TYPES_JS,
			array(
				'jquery-ui-datepicker',
			)
		);
		$this->register_js( self::SCHEMA_TYPES_JS, 'js/build/wds-schema-types.min.js', $schema_types_deps );

		$this->set_script_translations( self::SCHEMA_TYPES_JS );

		wp_localize_script(
			self::SCHEMA_TYPES_JS,
			'_wds_schema_types',
			array(
				'plugin_version'       => SMARTCRAWL_VERSION,
				'post_types'           => $post_types,
				'post_formats'         => $post_formats,
				'page_templates'       => $page_templates,
				'taxonomies'           => $taxonomies,
				'post_type_taxonomies' => $this->get_post_type_taxonomies(),
				'user_roles'           => $user_roles,
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'types'                => Schema\Types::get()->get_schema_types(),
				'woocommerce'          => smartcrawl_woocommerce_active(),
				'settings_updated'     => smartcrawl_get_array_value( $_GET, 'settings-updated' ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			)
		);
	}

	/**
	 * Get taxonomies for available post types.
	 *
	 * @return array
	 */
	private function get_post_type_taxonomies() {
		$post_types           = smartcrawl_frontend_post_types();
		$available_taxonomies = smartcrawl_frontend_taxonomies();
		$post_type_taxonomies = array();

		foreach ( $post_types as $post_type ) {
			$taxonomies      = get_object_taxonomies( $post_type, 'objects' );
			$taxonomies      = empty( $taxonomies ) ? array() : $taxonomies;
			$taxonomies      = array_filter(
				$taxonomies,
				function ( $taxonomy ) use ( $available_taxonomies ) {
					return array_key_exists( $taxonomy->name, $available_taxonomies );
				}
			);
			$post_type_label = get_post_type_object( $post_type )->labels->singular_name;
			if ( empty( $taxonomies ) ) {
				$post_type_taxonomies[ $post_type ] = $post_type_label;
				continue;
			}

			$post_type_taxonomies[ $post_type ] = array(
				'label'   => $post_type_label,
				'options' => array_merge(
					array(
						$post_type => $post_type_label,
					),
					array_map(
						function ( $taxonomy ) {
							return isset( $taxonomy->labels->singular_name ) ? $taxonomy->labels->singular_name : $taxonomy->label;
						},
						$taxonomies
					)
				),
			);
		}

		return $post_type_taxonomies;
	}

	/**
	 * Get post formats.
	 *
	 * @return array
	 */
	private function get_post_formats() {
		$post_formats = smartcrawl_get_array_value( get_theme_support( 'post-formats' ), 0 );
		$post_formats = empty( $post_formats ) ? array() : $post_formats;

		return array_combine( $post_formats, $post_formats );
	}

	/**
	 * Register scripts for email recipients.
	 *
	 * @return void
	 */
	private function register_email_recipients_js() {
		$recipient_deps = $this->dynamic_dependencies(
			self::EMAIL_RECIPIENTS_JS,
			array(
				self::ADMIN_JS,
			)
		);

		$this->register_js( self::EMAIL_RECIPIENTS_JS, 'js/build/wds-admin-email-recipients.min.js', $recipient_deps );

		$this->set_script_translations( self::EMAIL_RECIPIENTS_JS );
	}

	/**
	 * Register scripts for Health page.
	 *
	 * @return void
	 */
	private function register_health_settings_page_scripts() {
		if ( ! $this->is_page( Settings::TAB_HEALTH ) ) {
			return;
		}

		$this->register_email_recipients_js();

		$this->register_js(
			self::HEALTH_JS,
			'js/wds-admin-health.js',
			array(
				self::ADMIN_JS,
			)
		);

		wp_localize_script(
			self::HEALTH_JS,
			'_wds_health',
			array(
				'nonce' => wp_create_nonce( 'wds-health-nonce' ),
			)
		);

		wp_localize_script(
			self::EMAIL_RECIPIENTS_JS,
			'_wds_email_recipients',
			array(
				'id'         => 'wds-lighthouse-email-recipients',
				'recipients' => Lighthouse\Options::email_recipients(),
				'field_name' => 'wds_health_options[lighthouse-recipients]',
			)
		);

		$this->register_js(
			self::LIGHTHOUSE_JS,
			'js/build/wds-admin-lighthouse.min.js',
			array(
				self::ADMIN_JS,
				self::EMAIL_RECIPIENTS_JS,
				self::HEALTH_JS,
			)
		);

		$lighthouse = Service::get( Service::SERVICE_LIGHTHOUSE );

		wp_localize_script(
			self::LIGHTHOUSE_JS,
			'_wds_lighthouse',
			array(
				'start_time' => $lighthouse->get_start_time(),
				'nonce'      => wp_create_nonce( 'wds-lighthouse-nonce' ),
				'strings'    => array(
					'analyzing'         => esc_html__( 'Analyzing data and preparing report...', 'wds' ),
					'running'           => esc_html__( 'Running SEO test...', 'wds' ),
					'refreshing'        => esc_html__( 'Refreshing data. Please wait...', 'wds' ),
					'audit_copied'      => esc_html__( 'The audit has been copied successfully.', 'wds' ),
					'audit_copy_failed' => esc_html__( 'Audit could not be copied to clipboard.', 'wds' ),
				),
			)
		);
	}

	/**
	 * Sets translated strings for a script.
	 *
	 * @param string $handle Script handle the textdomain will be attached to.
	 *
	 * @return void
	 */
	private function set_script_translations( $handle ) {
		if ( ! function_exists( '\wp_set_script_translations' ) ) {
			return;
		}

		wp_set_script_translations( $handle, 'wds' );
	}

	/**
	 * Get script dependencies.
	 *
	 * @param string $file_name  File name.
	 * @param array  $extra_deps Extra dependencies.
	 *
	 * @return array
	 */
	private function dynamic_dependencies( $file_name, $extra_deps = array() ) {
		$assets_path = SMARTCRAWL_PLUGIN_DIR . 'assets/js/build/assets.php';

		if ( ! file_exists( $assets_path ) ) {
			return $extra_deps;
		}

		$assets     = require $assets_path;
		$key        = "$file_name.min.js";
		$asset_data = smartcrawl_get_array_value( $assets, $key );

		if ( ! $asset_data ) {
			return $extra_deps;
		}

		$dependencies = smartcrawl_get_array_value( $asset_data, 'dependencies' );
		$dependencies = empty( $dependencies ) ? array() : $dependencies;

		return array_merge(
			$extra_deps,
			$dependencies
		);
	}

	/**
	 * Register script for opengraph.
	 *
	 * @return void
	 */
	private function register_opengraph_script() {
		$this->register_js(
			self::OPENGRAPH_JS,
			'js/wds-admin-opengraph.js',
			array(
				'underscore',
				'jquery',
				self::ADMIN_JS,
			)
		);

		wp_localize_script(
			self::OPENGRAPH_JS,
			'_wds_opengraph',
			array(
				'templates' => array(
					'item' => Simple_Renderer::load(
						'social-image-item',
						array(
							'id'         => '{{= id }}',
							'url'        => '{{= url }}',
							'field_name' => '{{= name }}',
						)
					),
				),
			)
		);
	}

	/**
	 * Register script for macro replacement.
	 *
	 * @return void
	 */
	private function register_macro_replacement_script() {
		$macro_replacement_deps = $this->dynamic_dependencies(
			self::MACRO_REPLACEMENT,
			array(
				'jquery',
				'underscore',
				'wp-api',
				'wp-api-fetch',
				'wp-date',
			)
		);
		$this->register_js( self::MACRO_REPLACEMENT, 'js/build/wds-macro-replacement.min.js', $macro_replacement_deps );

		wp_localize_script(
			self::MACRO_REPLACEMENT,
			'_wds_replacement',
			array(
				'date_format'         => get_option( 'date_format' ),
				'time_format'         => get_option( 'time_format' ),
				'metadesc_max_length' => smartcrawl_metadesc_max_length(),
				'taxonomies'          => $this->get_taxonomies(),
				'replacements'        => $this->get_replacements(),
				'omitted_shortcodes'  => apply_filters( 'wds-omitted-shortcodes', array() ),
			)
		);
	}

	/**
	 * Register script for Configs in Settings page.
	 *
	 * @return void
	 */
	private function register_configs_script() {
		$configs_deps = $this->dynamic_dependencies(
			self::CONFIGS_JS,
			array(
				'jquery',
				self::ADMIN_JS,
			)
		);
		$this->register_js( self::CONFIGS_JS, 'js/build/wds-configs.min.js', $configs_deps );

		$this->set_script_translations( self::CONFIGS_JS );

		$service = new Configs\Service();
		wp_localize_script(
			self::CONFIGS_JS,
			'_wds_config',
			array(
				'is_member'    => $service->is_member(),
				'nonce'        => wp_create_nonce( 'wds-configs-nonce' ),
				'configs'      => Configs\Collection::get()->get_deflated_configs(),
				'timezone'     => $this->get_timezone(),
				'manage_url'   => Admin_Settings::admin_url( Settings::TAB_SETTINGS ) . '&tab=tab_configs',
				'default_icon' => sprintf( '%s/assets/images/default-config.svg', SMARTCRAWL_PLUGIN_URL ),
			)
		);
	}

	/**
	 * Registers script that are commonly used in Onpage and term form.
	 *
	 * @return void
	 */
	private function register_common_scripts() {
		$this->register_opengraph_script();
		$this->register_macro_replacement_script();

		$onpage_deps = $this->dynamic_dependencies(
			self::ONPAGE_COMPONENTS,
			array(
				'jquery',
				'underscore',
			)
		);
		$this->register_js( self::ONPAGE_COMPONENTS, 'js/build/wds-onpage-components.min.js', $onpage_deps );
		wp_localize_script(
			self::ONPAGE_COMPONENTS,
			'_wds_onpage_components',
			array(
				'random_posts' => Onpage_Settings::get_random_post_data(),
				'random_terms' => Onpage_Settings::get_random_terms(),
			)
		);

		$this->register_js(
			self::ONPAGE_JS,
			'js/wds-admin-onpage.js',
			array(
				'jquery',
				self::ADMIN_JS,
				self::OPENGRAPH_JS,
				self::ONPAGE_COMPONENTS,
				self::MACRO_REPLACEMENT,
			)
		);
		wp_localize_script(
			self::ONPAGE_JS,
			'_wds_onpage',
			array(
				'templates'         => array(
					'notice'  => Simple_Renderer::load( 'notice', array( 'message' => '{{- message }}' ) ),
					'preview' => Simple_Renderer::load( 'onpage/underscore-onpage-preview' ),
				),
				'home_url'          => home_url( '/' ),
				'nonce'             => wp_create_nonce( 'wds-onpage-nonce' ),
				'title_min'         => smartcrawl_title_min_length(),
				'title_max'         => smartcrawl_title_max_length(),
				'metadesc_min'      => smartcrawl_metadesc_min_length(),
				'metadesc_max'      => smartcrawl_metadesc_max_length(),
				'random_archives'   => Onpage_Settings::get_random_archives(),
				'random_buddypress' => Onpage_Settings::get_random_buddypress(),
			)
		);
	}
}