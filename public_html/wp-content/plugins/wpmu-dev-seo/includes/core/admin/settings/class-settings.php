<?php
/**
 * Class to manage Settings module.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin\Settings;

use SmartCrawl\Controllers\Assets;
use SmartCrawl\Settings as SC_Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Controllers\Analysis_Content;
use SmartCrawl\Services\Site;
use SmartCrawl\Services\Service;
use SmartCrawl\SmartCrawl;
use SmartCrawl\Third_Party_Import\AIOSEOP;
use SmartCrawl\Third_Party_Import\Importer;
use SmartCrawl\Third_Party_Import\Yoast;

/**
 * Settings module class.
 */
class Settings extends Admin_Settings {

	use Singleton;

	/**
	 * Validates submitted options
	 *
	 * @param array $input Raw input.
	 *
	 * @return array Validated input
	 */
	public function validate( $input ) {
		$result = self::get_specific_options( $this->option_name );

		$disable_schema_key = 'disable-schema';
		if ( isset( $input[ $disable_schema_key ] ) ) {
			$social_options                        = self::get_component_options( self::COMP_SOCIAL );
			$social_options[ $disable_schema_key ] = ! empty( $input[ $disable_schema_key ] );
			self::update_component_options( self::COMP_SOCIAL, $social_options );
			unset( $social_options[ $disable_schema_key ] );
		}

		if (
			isset( $_POST['_wpnonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::SETTINGS_MODULE . '_options-options' ) &&
			isset( $_POST['option_page'] ) &&
			self::SETTINGS_MODULE . '_options' === sanitize_text_field( wp_unslash( $_POST['option_page'] ) )
		) {
			$controller = \SmartCrawl\Modules\Advanced\Controller::get();

			if ( $controller->is_active() ) {
				$options = $controller->get_options();

				foreach ( array_keys( $controller->submodules ) as $submodule ) {
					$options[ $submodule ]['active'] = ! empty( $input['advanced'][ $submodule ] );
				}

				$controller->update_option( '', $options );
			}

			$result['hide_disables'] = ! empty( $input['hide_disables'] );
		}

		$components = array_keys( SC_Settings::get_known_components() );
		foreach ( $components as $component ) {
			$result[ $component ] = ! empty( $input[ $component ] );

			if ( ! $result['sitemap'] ) {
				\SmartCrawl\Controllers\Cron::get()->unschedule();
				\SmartCrawl\Sitemaps\Troubleshooting::get()->stop();
			} else {
				\SmartCrawl\Controllers\Cron::get()->set_up_crawler_schedule();
			}
		}

		// Data settings.
		$result['keep_settings_on_uninstall'] = ! empty( $input['keep_settings_on_uninstall'] );
		$result['keep_data_on_uninstall']     = ! empty( $input['keep_data_on_uninstall'] );
		$result['usage_tracking']             = ! empty( $input['usage_tracking'] );

		if ( ! empty( $input['wds_settings-setup'] ) ) {
			$result['wds_settings-setup'] = true;
		}

		// Analysis/readability.
		$result['analysis-seo']             = ! empty( $input['analysis-seo'] );
		$result['analysis-readability']     = ! empty( $input['analysis-readability'] );
		$result['disable-analysis-on-list'] = ! empty( $input['disable-analysis-on-list'] );
		$result['analysis_strategy']        = ! empty( $input['analysis_strategy'] )
			? sanitize_text_field( $input['analysis_strategy'] )
			: Analysis_Content::STRATEGY_STRICT;
		$result['extras-admin_bar']         = ! empty( $input['extras-admin_bar'] );

		if ( ! empty( $input['metabox-lax_enforcement'] ) ) {
			$result['metabox-lax_enforcement'] = true;
		} else {
			$result['metabox-lax_enforcement'] = false;
		}
		if ( ! empty( $input['general-suppress-generator'] ) ) {
			$result['general-suppress-generator'] = true;
		} else {
			$result['general-suppress-generator'] = false;
		}
		if ( ! empty( $input['general-suppress-redundant_canonical'] ) ) {
			$result['general-suppress-redundant_canonical'] = true;
		} else {
			$result['general-suppress-redundant_canonical'] = false;
		}

		// High Contrast Mode option in Accessibility under Settings module.
		if ( ! empty( $input['high-contrast'] ) ) {
			$result['high-contrast'] = true;
		} else {
			$result['high-contrast'] = false;
		}

		// Roles.
		foreach ( $this->get_permission_contexts() as $ctx ) {
			if ( empty( $input[ $ctx ] ) ) {
				continue;
			}
			$roles          = array_keys( $this->get_filtered_roles( "wds_{$ctx}" ) );
			$check_context  = is_array( $input[ $ctx ] ) ? $input[ $ctx ] : array( $input[ $ctx ] );
			$result[ $ctx ] = array();
			foreach ( $check_context as $ctx_item ) {
				if ( in_array( $ctx_item, $roles, true ) ) {
					$result[ $ctx ][] = $ctx_item;
				}
			}
		}

		if ( isset( $input['verification-google-meta'] ) ) {
			$this->validate_and_save_extra_options( $input );
		}

		return $result;
	}

	/**
	 * Get a list of permission contexts used for roles filtering
	 *
	 * @return array
	 */
	protected function get_permission_contexts() {
		return array(
			'seo_metabox_permission_level',
			'seo_metabox_301_permission_level',
			'urlmetrics_metabox_permission_level',
		);
	}

	/**
	 * Get (optionally filtered) default roles
	 *
	 * @param string|false $context_filter Optional filter to pass the roles through first.
	 *
	 * @return array List of roles
	 */
	protected function get_filtered_roles( $context_filter = false ) {
		$default_roles = array(
			'manage_network'       => __( 'Super Admin', 'wds' ),
			/* translators: %s: Role name */
			'manage_options'       => sprintf( __( '%s (and up)', 'wds' ), __( 'Site Admin', 'wds' ) ),
			/* translators: %s: Role name */
			'moderate_comments'    => sprintf( __( '%s (and up)', 'wds' ), __( 'Editor', 'wds' ) ),
			/* translators: %s: Role name */
			'edit_published_posts' => sprintf( __( '%s (and up)', 'wds' ), __( 'Author', 'wds' ) ),
			/* translators: %s: Role name */
			'edit_posts'           => sprintf( __( '%s (and up)', 'wds' ), __( 'Contributor', 'wds' ) ),
		);
		if ( ! is_multisite() ) {
			unset( $default_roles['manage_network'] );
		}

		return ! empty( $context_filter )
			? (array) apply_filters( $context_filter, $default_roles )
			: $default_roles;
	}

	/**
	 * Processes extra options passed on from the main form
	 *
	 * This is a side-effect method - the extra options don't update
	 * the tab option key, but go to an extternal location
	 *
	 * @param array $input Raw form input.
	 */
	private function validate_and_save_extra_options( $input ) {
		// Sitemaps validation/save.
		$sitemaps         = SC_Settings::get_component_options( SC_Settings::COMP_SITEMAP );
		$sitemaps_updated = false;
		if ( isset( $input['verification-pages'] ) ) {
			$pages = $input['verification-pages'];
			if ( in_array( $pages, array( '', 'home' ), true ) ) {
				$sitemaps['verification-pages'] = $pages;
			}
			$sitemaps_updated = true;
		}

		// Meta tags.
		if ( isset( $input['verification-google-meta'] ) ) {
			$sitemaps['verification-google-meta'] = \smartcrawl_is_valid_meta_tag( $input['verification-google-meta'] ) ? $input['verification-google-meta'] : '';
			$sitemaps_updated                     = true;
		}
		if ( isset( $input['verification-bing-meta'] ) ) {
			$sitemaps['verification-bing-meta'] = \smartcrawl_is_valid_meta_tag( $input['verification-bing-meta'] ) ? $input['verification-bing-meta'] : '';
			$sitemaps_updated                   = true;
		}

		$custom_values_key = 'additional-metas';
		if ( ! empty( $input[ $custom_values_key ] ) && is_array( $input[ $custom_values_key ] ) ) {
			$custom_values           = $input[ $custom_values_key ];
			$sanitized_custom_values = array();
			foreach ( $custom_values as $index => $custom_value ) {
				if ( trim( $custom_value ) ) {
					$sanitized = wp_kses(
						$custom_value,
						array(
							'meta' => array(
								'charset'    => array(),
								'property'   => array(),
								'content'    => array(),
								'http-equiv' => array(),
								'name'       => array(),
								'scheme'     => array(),
							),
						)
					);
					if ( preg_match( '/<meta\b/', trim( $sanitized ) ) ) {
						$sanitized_custom_values[] = $sanitized;
					}
				}
			}
			$sitemaps[ $custom_values_key ] = $sanitized_custom_values;
			$sitemaps_updated               = true;
		}

		if ( $sitemaps_updated ) {
			SC_Settings::update_component_options( SC_Settings::COMP_SITEMAP, $sitemaps );
		}
	}

	/**
	 * Initializes the admin pane.
	 *
	 * @return void
	 */
	public function init() {
		$this->option_name = 'wds_settings_options';
		$this->name        = 'settings';
		$this->slug        = SC_Settings::TAB_SETTINGS;
		$this->action_url  = admin_url( 'options.php' );
		$this->page_title  = sprintf(
		/* translators: %s: plugin title */
			__( '%s Wizard: Settings', 'wds' ),
			\smartcrawl_get_plugin_title()
		);

		add_action( 'admin_init', array( $this, 'activate_component' ) );
		add_action( 'admin_footer', array( $this, 'add_native_dismissible_notice_javascript' ) );
		add_action( 'network_admin_notices', array( $this, 'import_notice' ) );

		if ( ! is_multisite() || is_main_site() ) {
			add_action( 'admin_notices', array( $this, 'import_notice' ) );
		}

		parent::init();

		remove_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ), 99 );
	}

	/**
	 * Get title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Settings', 'wds' );
	}

	/**
	 * Updates the options to activate a component.
	 */
	public function activate_component() {
		$data = $this->get_request_data();
		if ( isset( $data['wds-activate-component'] ) ) {
			$component = sanitize_key( $data['wds-activate-component'] );

			if ( 'advanced' !== $component ) {
				$options               = self::get_specific_options( $this->option_name );
				$options[ $component ] = 1;

				self::update_specific_options( $this->option_name, $options );
			}

			do_action( "smartcrawl_component_activated_$component" );

			wp_safe_redirect( esc_url_raw( add_query_arg( array() ) ) );
		}
	}

	/**
	 * Add admin settings page
	 */
	public function options_page() {
		parent::options_page();

		$arguments['default_roles'] = $this->get_filtered_roles();

		$arguments['plugin_modules'] = $this->get_plugin_modules();

		foreach ( $this->get_permission_contexts() as $ctx ) {
			$arguments[ $ctx ] = $this->get_filtered_roles( "wds_$ctx" );
		}

		$sitemap_settings                 = \SmartCrawl\Admin\Settings\Sitemap::get();
		$arguments['sitemap_option_name'] = $sitemap_settings->option_name;

		$arguments['verification_pages'] = array(
			''     => __( 'All pages', 'wds' ),
			'home' => __( 'Home page', 'wds' ),
		);

		$arguments['active_tab'] = $this->get_active_tab( 'tab_general_settings' );

		wp_enqueue_script( Assets::SETTINGS_PAGE_JS );

		$options = self::get_specific_options( $this->option_name );

		wp_localize_script(
			Assets::SETTINGS_PAGE_JS,
			'_wds_settings',
			array(
				'hide_disables' => isset( $options['hide_disables'] ) ? $options['hide_disables'] : true,
			)
		);

		$this->render_page( 'settings/settings', $arguments );
	}

	/**
	 * Retrieves plugin modules.
	 *
	 * @return array
	 */
	private function get_plugin_modules() {
		$disable_schema = (bool) \smartcrawl_get_array_value(
			self::get_component_options( SC_Settings::COMP_SOCIAL ),
			'disable-schema'
		);

		// All available modules excluding Advanced Tools. Advanced Tools will be added by JS.
		$all_plugin_modules = array(
			'onpage'  => $this->plugin_module_args(
				__( 'Title & Meta', 'wds' ),
				__( 'Customize your homepage title, description, and meta options.', 'wds' ),
				'onpage'
			),
			'schema'  => $this->plugin_module_args(
				__( 'Schema', 'wds' ),
				__( 'Let search engines know whether you\'re an organization or a person, and add all your social profiles so search engines know which social profiles to attribute your web content to.', 'wds' ),
				'disable-schema',
				true,
				$disable_schema
			),
			'social'  => $this->plugin_module_args(
				__( 'Social Network', 'wds' ),
				__( 'Add meta data to your pages to make them look great when shared on platforms such as Facebook and other popular social networks.', 'wds' ),
				'social'
			),
			'sitemap' => $this->plugin_module_args(
				__( 'Sitemaps', 'wds' ),
				__( 'Automatically generate a sitemap and regularly send updates to Google.', 'wds' ),
				'sitemap'
			),
		);

		if ( ! is_multisite() || is_network_admin() ) {
			return $all_plugin_modules;
		}

		// The modules that are to be shown in the sub-site settings.
		$sub_site_modules = array();
		$active_blog_tabs = self::get_blog_tabs();
		foreach ( $all_plugin_modules as $plugin_module => $label ) {
			if ( array_key_exists( 'wds_' . $plugin_module, $active_blog_tabs ) ) {
				$sub_site_modules[ $plugin_module ] = $label;
			}
		}

		return $sub_site_modules;
	}

	/**
	 * Generates plugin module arguments.
	 *
	 * @param string $label Module name.
	 * @param string $tooltip Module tooltip.
	 * @param string $field_name  Module field name.
	 * @param bool   $inverted Determines if field name is inverted one.
	 * @param mixed  $checked Is checked.
	 * @return array
	 */
	private function plugin_module_args( $label, $tooltip, $field_name, $inverted = false, $checked = null ) {
		if ( is_null( $checked ) ) {
			$checked = \smartcrawl_get_array_value( self::get_options(), $field_name );
		}

		return array(
			'field_name' => "{$this->option_name}[$field_name]",
			'html_label' => '<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="' . esc_attr( $tooltip ) . '" style="--tooltip-width: 240px;">' . esc_html( $label ) . '</span>',
			'inverted'   => $inverted,
			'checked'    => $checked,
		);
	}

	/**
	 * Get allowed blog tabs
	 *
	 * @return array
	 */
	public static function get_blog_tabs() {
		$blog_tabs = get_site_option( 'wds_blog_tabs' );

		return is_array( $blog_tabs )
			? $blog_tabs
			: array();
	}

	/**
	 * Default settings
	 */
	public function defaults() {
		$this->options = self::get_specific_options( $this->option_name );

		if ( empty( $this->options ) ) {
			$this->options['seomoz']  = 0;
			$this->options['sitemap'] = 1;
			$this->options['onpage']  = 1;
			$this->options['social']  = 1;
		}

		if ( empty( $this->options['seo_metabox_permission_level'] ) ) {
			$this->options['seo_metabox_permission_level'] = 'manage_options';
		}

		if ( empty( $this->options['urlmetrics_metabox_permission_level'] ) ) {
			$this->options['urlmetrics_metabox_permission_level'] = 'manage_options';
		}

		if ( empty( $this->options['seo_metabox_301_permission_level'] ) ) {
			$this->options['seo_metabox_301_permission_level'] = 'manage_options';
		}

		if ( ! isset( $this->options['analysis-seo'] ) ) {
			$this->options['analysis-seo'] = true;
		}
		if ( ! isset( $this->options['analysis-readability'] ) ) {
			$this->options['analysis-readability'] = true;
		}
		if ( ! isset( $this->options['extras-admin_bar'] ) ) {
			$this->options['extras-admin_bar'] = true;
		}
		if ( ! isset( $this->options['keep_settings_on_uninstall'] ) ) {
			$this->options['keep_settings_on_uninstall'] = true;
		}
		if ( ! isset( $this->options['keep_data_on_uninstall'] ) ) {
			$this->options['keep_data_on_uninstall'] = true;
		}
		if ( ! isset( $this->options['usage_tracking'] ) ) {
			$this->options['usage_tracking'] = false;
		}

		if ( ! isset( $this->options['hide_disables'] ) ) {
			$this->options['hide_disables'] = true;
		}

		$this->options = apply_filters_deprecated(
			'wds_defaults',
			array( $this->options ),
			'6.4.2',
			'smartcrawl_default_settings',
			__( 'Please use our new filter `smartcrawl_default_settings` in SmartCrawl.', 'wds' )
		);

		$this->options = apply_filters( 'smartcrawl_default_settings', $this->options );

		self::update_specific_options( $this->option_name, $this->options );

		$blog_tabs = get_site_option( 'wds_blog_tabs', false );
		if ( false === $blog_tabs ) {
			\smartcrawl_activate_all_blog_tabs();
		}
	}

	/**
	 * Manages import notice.
	 *
	 * @return void
	 */
	public function import_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->show_import_notice(
			new Yoast(),
			'yoast-seo',
			esc_html__( 'Yoast SEO', 'wds' )
		);

		$this->show_import_notice(
			new AIOSEOP(),
			'all-in-one-seo',
			esc_html__( 'All In One SEO', 'wds' )
		);
	}

	/**
	 * Handles to show import notice.
	 *
	 * @param Importer $importer Third party plugin as an importer.
	 * @param string   $plugin_key Importer plugin key.
	 * @param string   $plugin_name Plugin name.
	 *
	 * @return void
	 */
	private function show_import_notice( $importer, $plugin_key, $plugin_name ) {
		if ( ! $importer->data_exists() ) {
			return;
		}

		$auto_import_url = sprintf(
			/* translators: %s: Url to Auto Import settings page. */
			'<a href="%s">%s</a>',
			Admin_Settings::admin_url( SC_Settings::TAB_SETTINGS ) . '&tab=tab_import_export',
			esc_html__( 'auto-import', 'wds' )
		);
		$message = sprintf(
			/* translators: 1: Plugin name, 2: Anchor tag to Import/Export page, 3,4: strong tag, 5: plugin title */
			esc_html__( 'We\'ve detected you have %1$s settings. Do you want to %2$s your configuration into %3$s%5$s%4$s?', 'wds' ),
			$plugin_name,
			$auto_import_url,
			'<strong>',
			'</strong>',
			\smartcrawl_get_plugin_title()
		);
		$message_key          = sprintf( '%s-import', $plugin_key );
		$dismissed_messages   = get_user_meta( get_current_user_id(), 'wds_dismissed_messages', true );
		$is_message_dismissed = \smartcrawl_get_array_value( $dismissed_messages, $message_key ) === true;

		if ( $is_message_dismissed ) {
			return;
		}

		?>
		<div
			class="notice-warning notice is-dismissible wds-native-dismissible-notice"
			data-message-key="<?php echo esc_attr( $message_key ); ?>"
		>
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Adds javascript for native dismissible notice.
	 *
	 * @return void
	 */
	public function add_native_dismissible_notice_javascript() {
		$this->render_view( 'native-dismissible-notice-javascript' );
	}

	/**
	 * Not being used as of now.
	 *
	 * @return void
	 */
	public function wp_org_rating_request() {
		$service = $this->get_service();
		if ( $service->is_member() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( is_multisite() && ! is_network_admin() ) {
			return;
		}

		$days              = 7;
		$now               = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$free_install_date = get_site_option( 'wds-free-install-date' );
		if ( ( $now - (int) $free_install_date ) < ( $days * 24 * 60 * 60 ) ) {
			return;
		}

		$key                  = 'wp-org-rating-request';
		$dismissed_messages   = get_user_meta( get_current_user_id(), 'wds_dismissed_messages', true );
		$is_message_dismissed = \smartcrawl_get_array_value( $dismissed_messages, $key ) === true;
		if ( $is_message_dismissed ) {
			return;
		}

		?>
		<div
			class="notice-info notice is-dismissible wds-native-dismissible-notice"
			data-message-key="<?php echo esc_attr( $key ); ?>"
		>
			<p>
				<?php
				printf(
					/* translators: 1,2: strong tag, 3: plugin title */
					esc_html__( 'Excellent! You\'ve been using %1$s%3$s%2$s for over a week. Hope you are enjoying it so far. We have spent countless hours developing this free plugin for you, and we would really appreciate it if you could drop us a rating on wp.org to help us spread the word and boost our motivation.', 'wds' ),
					'<strong>',
					'</strong>',
					esc_html( \smartcrawl_get_plugin_title() )
				);
				?>
			</p>
			<a
				target="_blank" href="https://wordpress.org/plugins/smartcrawl-seo#reviews"
				class="button button-primary"
			>
				<?php esc_html_e( 'Rate SmartCrawl', 'wds' ); ?>
			</a>
			<a href="#" class="wds-native-dismiss">No Thanks</a>
			<p></p>
		</div>
		<?php
	}

	/**
	 * Retrieves service.
	 *
	 * @return Site;
	 */
	private static function get_service() {
		return Service::get( Service::SERVICE_SITE );
	}

	/**
	 * Retrieves request data.
	 *
	 * @return array
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-settings-nonce' ) ? $_POST : array();
	}
}
