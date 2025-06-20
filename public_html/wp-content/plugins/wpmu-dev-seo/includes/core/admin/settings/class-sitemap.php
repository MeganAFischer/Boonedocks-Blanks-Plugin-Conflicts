<?php
/**
 * Sitemap settings admin page
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin\Settings;

use SmartCrawl\Controllers\Assets;
use SmartCrawl\Controllers\Cron;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Services\Service;
use SmartCrawl\Sitemaps\Utils;

/**
 * Sitemap settings admin page class
 */
class Sitemap extends Admin_Settings {

	use Singleton;

	/**
	 * Validates submitted options
	 *
	 * @param array $input Raw input.
	 *
	 * @return array Validated input
	 */
	public function validate( $input ) {
		$result          = array();
		$previous_values = self::get_specific_options( $this->option_name );

		if ( isset( $input['override-native'] ) ) {
			$result['override-native'] = ! empty( $input['override-native'] );
		} elseif ( isset( $previous_values['override-native'] ) ) {
			$result['override-native'] = ! empty( $previous_values['override-native'] );
		}

		if ( ! empty( $input['wds_sitemap-setup'] ) ) {
			$result['wds_sitemap-setup'] = true;
		}

		$strings = array(
			'verification-google-meta',
			'verification-bing-meta',
			'verification-pages',
		);
		foreach ( $strings as $str ) {
			if ( isset( $input[ $str ] ) ) {
				$result[ $str ] = sanitize_text_field( $input[ $str ] );
			} else {
				$result[ $str ] = \smartcrawl_get_array_value( $previous_values, $str );
			}
		}

		$booleans = array(
			'sitemap-images',
			'sitemap-stylesheet',
			'sitemap-dashboard-widget',
			'sitemap-buddypress-groups',
			'sitemap-buddypress-profiles',
		);
		foreach ( $booleans as $bool ) {
			if ( ! empty( $input[ $bool ] ) ) {
				$result[ $bool ] = true;
			}
		}

		$result['ping-google'] = ! empty( $input['auto-notify-search-engines'] );
		$result['ping-bing']   = ! empty( $input['auto-notify-search-engines'] );

		// Array Booleans.
		foreach ( array_keys( $this->get_post_types_options() ) as $post_type ) {
			$result[ $post_type ] = ! empty( $input[ $post_type ] );
		}
		foreach ( array_keys( $this->get_taxonomies_options() ) as $tax ) {
			$result[ $tax ] = ! empty( $input[ $tax ] );
		}

		// BuddyPress-specific.
		$bpo = $this->get_buddyress_template_values();
		if ( ! empty( $bpo['exclude_groups'] ) && is_array( $bpo['exclude_groups'] ) ) {
			foreach ( $bpo['exclude_groups'] as $slug => $name ) {
				$key            = "sitemap-buddypress-{$slug}";
				$result[ $key ] = ! empty( $input[ $key ] );
			}
		}

		if ( ! empty( $bpo['exclude_roles'] ) && is_array( $bpo['exclude_roles'] ) ) {
			foreach ( $bpo['exclude_roles'] as $slug => $name ) {
				$key            = "sitemap-buddypress-roles-{$slug}";
				$result[ $key ] = ! empty( $input[ $key ] );
			}
		}

		// Meta tags.
		if ( ! empty( $input['verification-google-meta'] ) ) {
			$result['verification-google-meta'] = \smartcrawl_is_valid_meta_tag( $input['verification-google-meta'] ) ? $input['verification-google-meta'] : '';
		}
		if ( ! empty( $input['verification-bing-meta'] ) ) {
			$result['verification-bing-meta'] = \smartcrawl_is_valid_meta_tag( $input['verification-bing-meta'] ) ? $input['verification-bing-meta'] : '';
		}

		$custom_values_key = 'additional-metas';
		if ( isset( $input[ $custom_values_key ] ) && is_array( $input[ $custom_values_key ] ) ) {
			$result[ $custom_values_key ] = $input[ $custom_values_key ];
		} else {
			$result[ $custom_values_key ] = \smartcrawl_get_array_value( $previous_values, $custom_values_key );
		}

		$result = $this->validate_crawler_settings( $input, $result );
		$result = $this->validate_auto_update_settings( $input, $result );

		if ( isset( $input['extra_sitemap_urls'] ) ) {
			$extra_urls           = explode( "\n", $input['extra_sitemap_urls'] );
			$sanitized_extra_urls = array();
			foreach ( $extra_urls as $extra_url ) {
				if ( trim( $extra_url ) ) {
					$sanitized_extra_urls[] = esc_url( $extra_url );
				}
			}
			Utils::set_extra_urls( $sanitized_extra_urls );

			unset( $input['extra_sitemap_urls'] );
		}

		if ( isset( $input['sitemap_ignore_urls'] ) ) {
			$ignore_urls           = explode( "\n", $input['sitemap_ignore_urls'] );
			$sanitized_ignore_urls = array();
			foreach ( $ignore_urls as $ignore_url ) {
				if ( trim( $ignore_url ) ) {
					$sanitized_ignore_urls[] = \smartcrawl_sanitize_relative_url( $ignore_url );
				}
			}
			Utils::set_ignore_urls( $sanitized_ignore_urls );

			unset( $input['sitemap_ignore_urls'] );
		}

		if ( isset( $input['sitemap_ignore_post_ids'] ) ) {
			$ignore_post_ids           = explode( ',', $input['sitemap_ignore_post_ids'] );
			$sanitized_ignore_post_ids = array();
			foreach ( $ignore_post_ids as $pid ) {
				if ( trim( $pid ) && (int) $pid ) {
					$sanitized_ignore_post_ids[] = (int) $pid;
				}
			}
			Utils::set_ignore_ids( $sanitized_ignore_post_ids );

			unset( $input['sitemap_ignore_post_ids'] );
		}

		$per_sitemap = (int) \smartcrawl_get_array_value( $input, 'items-per-sitemap' );
		if ( $per_sitemap <= 0 ) {
			$per_sitemap = Utils::DEFAULT_ITEMS_PER_SITEMAP;
			add_settings_error(
				$this->option_name,
				'items-per-sitemap',
				esc_html__( 'Please enter a valid number in the "Items Per Sitemap" setting', 'wds' )
			);
		}
		$max_per_sitemap = Utils::get_max_items_per_sitemap();
		if ( $per_sitemap > $max_per_sitemap ) {
			$per_sitemap = $max_per_sitemap;

			add_settings_error(
				$this->option_name,
				'max-items-per-sitemap',
				// translators: %s max items per sitemap.
				sprintf( esc_html__( 'The maximum number allowed for "Items Per Sitemap" setting is %d', 'wds' ), $max_per_sitemap )
			);
		}
		$result['items-per-sitemap'] = $per_sitemap;

		if ( empty( $input['sitemap-email-recipients'] ) ) {
			add_settings_error(
				$this->option_name,
				'empty_recipient',
				esc_html__( 'Please add at least one recipient to enable the scheduled report.', 'wds' )
			);
		}

		if ( isset( $input['troubleshoot-count'] ) ) {
			$result['troubleshoot-count'] = (int) $input['troubleshoot-count'];
		}

		return array_merge(
			$result,
			$this->process_news_settings( $input )
		);
	}

	/**
	 * Processes News Sitemap settings.
	 *
	 * @param array $input The input data.
	 *
	 * @return array The processed news settings.
	 */
	private function process_news_settings( $input ) {
		$json = \smartcrawl_get_array_value( $input, 'news-settings', '' );
		$data = json_decode( $json, true );
		if ( empty( $data ) ) {
			return array();
		}

		$to_settings = new \SmartCrawl\Sitemaps\News\Data();

		return $to_settings->data_to_settings( $data );
	}

	/**
	 * Retrieves a list of post type based options
	 *
	 * @return array
	 */
	protected function get_post_types_options() {
		$options = array();

		foreach (
			get_post_types(
				array(
					'public'  => true,
					'show_ui' => true,
				)
			) as $post_type
		) {
			if ( in_array( $post_type, array( 'revision', 'nav_menu_item', 'attachment' ), true ) ) {
				continue;
			}
			$pt = get_post_type_object( $post_type );

			$options[ 'post_types-' . $post_type . '-not_in_sitemap' ] = $pt;
		}

		return $options;
	}

	/**
	 * Retrieves a list of taxonomy based options
	 *
	 * @return array
	 */
	protected function get_taxonomies_options() {
		$options = array();

		foreach (
			get_taxonomies(
				array(
					'public'  => true,
					'show_ui' => true,
				)
			) as $taxonomy
		) {
			if ( in_array( $taxonomy, array( 'nav_menu', 'link_category', 'post_format' ), true ) ) {
				continue;
			}
			$tax = get_taxonomy( $taxonomy );

			$options[ 'taxonomies-' . $taxonomy . '-not_in_sitemap' ] = $tax;
		}

		return $options;
	}

	/**
	 * BuddyPress settings fields helper.
	 *
	 * @return array BuddyPress values for the template
	 */
	private function get_buddyress_template_values() {
		$arguments = array();
		if ( ! defined( 'BP_VERSION' ) ) {
			return $arguments;
		}

		$arguments['checkbox_options'] = array(
			'yes' => __( 'Yes', 'wds' ),
		);

		if ( function_exists( '\groups_get_groups' ) ) { // We have BuddyPress groups, so let's get some settings.
			$groups                      = \groups_get_groups();
			$arguments['groups']         = ! empty( $groups['groups'] ) ? $groups['groups'] : array();
			$arguments['exclude_groups'] = array();
			foreach ( $arguments['groups'] as $group ) {
				$arguments['exclude_groups'][ 'exclude-buddypress-group-' . $group->slug ] = $group->name;
			}
		}

		$wp_roles                   = new \WP_Roles();
		$wp_roles                   = $wp_roles->get_names();
		$wp_roles                   = $wp_roles ? $wp_roles : array();
		$arguments['exclude_roles'] = array();
		foreach ( $wp_roles as $key => $label ) {
			$arguments['exclude_roles'][ 'exclude-profile-role-' . $key ] = $label;
		}

		return $arguments;
	}

	/**
	 * Crawler settings validation.
	 *
	 * @param array $input  Raw input.
	 * @param array $result Result this far.
	 *
	 * @return array
	 */
	private function validate_crawler_settings( $input, $result ) {
		$result = $this->sanitize_crawler_emails( $input, $result );

		if ( empty( $input['crawler-cron-enable'] ) || empty( $result['sitemap-email-recipients'] ) ) {
			$result['crawler-cron-enable'] = false;

			return $result;
		} else {
			$result['crawler-cron-enable'] = true;
		}

		$frequency                   = ! empty( $input['crawler-frequency'] )
			? Cron::get()->get_valid_frequency( $input['crawler-frequency'] )
			: Cron::get()->get_default_frequency();
		$result['crawler-frequency'] = $frequency;

		$result['crawler-dow'] = $this->validate_dow(
			(int) \smartcrawl_get_array_value( $input, 'crawler-dow' )
		);

		$result['crawler-dom'] = $this->validate_dom(
			(int) \smartcrawl_get_array_value( $input, 'crawler-dom' )
		);

		$tod                   = isset( $input['crawler-tod'] ) && is_numeric( $input['crawler-tod'] )
			? (int) $input['crawler-tod']
			: 0;
		$result['crawler-tod'] = in_array( $tod, range( 0, 23 ), true ) ? $tod : 0;

		return $result;
	}

	/**
	 * Crawler Auto Update settings validation.
	 *
	 * @param array $input  Raw input.
	 * @param array $result Result this far.
	 *
	 * @return array
	 */
	private function validate_auto_update_settings( $input, $result ) {
		// Sitemap generation method.
		$result['sitemap-disable-automatic-regeneration'] = (
			// If not empty.
			! empty( $input['sitemap-disable-automatic-regeneration'] ) &&
			// Or if not one of allowed methods.
			in_array( $input['sitemap-disable-automatic-regeneration'], array( 'auto', 'manual', 'scheduled' ), true )
		)
			? $input['sitemap-disable-automatic-regeneration'] : 'manual';

		$result['sitemap-update-frequency'] = ! empty( $input['sitemap-update-frequency'] )
			? Cron::get()->get_valid_frequency( $input['sitemap-update-frequency'], array( 'monthly' ) )
			: Cron::get()->get_default_frequency();

		$result['sitemap-update-dow'] = $this->validate_dow(
			(int) \smartcrawl_get_array_value( $input, 'sitemap-update-dow' )
		);

		$tod                          = isset( $input['sitemap-update-tod'] ) && is_numeric( $input['sitemap-update-tod'] ) ? (int) $input['sitemap-update-tod'] : 0;
		$result['sitemap-update-tod'] = in_array( $tod, range( 0, 23 ), true ) ? $tod : 0;

		return $result;
	}

	/**
	 * Initializes the handlers.
	 */
	public function init() {
		$this->option_name = 'wds_sitemap_options';
		$this->name        = Settings::COMP_SITEMAP;
		$this->slug        = Settings::TAB_SITEMAP;
		$this->action_url  = admin_url( 'options.php' );
		$this->page_title  = sprintf(
		/* translators: %s: plugin title */
			__( '%s Wizard: Sitemap', 'wds' ),
			\smartcrawl_get_plugin_title()
		);

		add_action( 'smartcrawl_component_activated_sitemap', array( $this, 'trigger_crawl_after_activation' ) );
		add_action( 'all_admin_notices', array( $this, 'add_crawl_status_message' ), 10 );
		add_filter( 'sanitize_option_wds_sitemap_options', array( $this, 'sanitize_option' ), 10, 3 );

		parent::init();

		remove_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ), 97 );
	}

	/**
	 * Returns this module's title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Sitemaps', 'wds' );
	}

	/**
	 * Triggers crawl after activation.
	 *
	 * @return void
	 */
	public function trigger_crawl_after_activation() {
		$service = Service::get( Service::SERVICE_SEO );
		$service->start();
	}

	/**
	 * Processes Crawler run request.
	 *
	 * @return bool Whether the crawl was started successfully.
	 */
	public function process_run_action() {
		if ( isset( $_GET['_wds_nonce'], $_GET['run-crawl'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wds_nonce'] ) ), 'wds-crawl-nonce' ) ) {
			// Simple presence switch, no value.
			$this->run_crawl();

			return true;
		}

		return false;
	}

	/**
	 * Retrieves the URL for crawling.
	 *
	 * @return string
	 */
	public static function crawl_url() {
		$crawl_url = Admin_Settings::admin_url( Settings::TAB_SITEMAP );

		return esc_url_raw(
			add_query_arg(
				array(
					'run-crawl'  => 'yes',
					'_wds_nonce' => wp_create_nonce( 'wds-crawl-nonce' ),
				),
				$crawl_url
			)
		);
	}

	/**
	 * Runs the crawl process.
	 *
	 * @return void
	 */
	public function run_crawl() {
		$message     = '';
		$in_progress = false;

		if ( current_user_can( 'manage_options' ) ) {
			$service = Service::get( Service::SERVICE_SEO );

			$remaining_minutes = $service->get_cooldown_remaining();

			if ( ! $remaining_minutes ) {
				$response = $service->start();

				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} elseif ( false !== $response ) {
					$in_progress = true;
				}
			}
		} else {
			$message = __( 'You don\'t have permission to run crawl.', 'wds' );
		}

		$url = add_query_arg(
			array(
				'tab'               => 'tab_url_crawler',
				'crawl-in-progress' => $in_progress ? '1' : '0',
				'message'           => $message,
			),
			Admin_Settings::admin_url( Settings::TAB_SITEMAP )
		);

		wp_safe_redirect( esc_url_raw( $url ) );
		die;
	}

	/**
	 * Adds a crawler status message.
	 */
	public function add_crawl_status_message() {
		$crawl_in_progress = \smartcrawl_get_array_value( $_GET, 'crawl-in-progress' ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( is_null( $crawl_in_progress ) ) {
			return;
		}

		$crawl_in_progress = (bool) $crawl_in_progress;

		if ( ! $crawl_in_progress ) {
			$message = (string) \smartcrawl_get_array_value( $_GET, 'message' ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( ! empty( $message ) ) {
				add_settings_error( $this->option_name, 'wds-crawl-not-started', wp_strip_all_tags( $message ) );
			}
		}
	}

	/**
	 * Outputs the content for this page.
	 */
	public function options_page() {
		parent::options_page();

		$arguments = array(
			'post_types'         => array(),
			'taxonomies'         => array(),
			'checkbox_options'   => array(
				'yes' => __( 'Yes', 'wds' ),
			),
			'verification_pages' => array(
				''     => __( 'All pages', 'wds' ),
				'home' => __( 'Home page', 'wds' ),
			),
		);

		foreach ( $this->get_post_types_options() as $opt => $post_type ) {
			$arguments['post_types'][ $opt ] = $post_type;
		}
		foreach ( $this->get_taxonomies_options() as $opt => $taxonomy ) {
			$arguments['taxonomies'][ $opt ] = $taxonomy;
		}

		$arguments['smartcrawl_buddypress'] = $this->get_buddyress_template_values();

		$arguments['active_tab'] = $this->get_active_tab( 'tab_sitemap' );

		$extra_urls = Utils::get_extra_urls();
		if ( is_array( $extra_urls ) ) {
			$arguments['extra_urls'] = ! empty( $extra_urls )
				? implode( "\n", $extra_urls )
				: '';
		}

		$ignore_urls = Utils::get_ignore_urls();
		if ( is_array( $ignore_urls ) ) {
			$arguments['ignore_urls'] = ! empty( $ignore_urls )
				? implode( "\n", $ignore_urls )
				: '';
		}

		$ignore_post_ids = Utils::get_ignore_ids();
		if ( is_array( $ignore_post_ids ) ) {
			$arguments['ignore_post_ids'] = ! empty( $ignore_post_ids )
				? implode( ',', $ignore_post_ids )
				: '';
		}
		$arguments['override_native'] = Utils::override_native();

		wp_enqueue_script( Assets::SITEMAPS_PAGE_JS );

		$this->render_page( 'sitemap/sitemap-settings', $arguments );
	}

	/**
	 * Default settings.
	 */
	public function defaults() {
		$this->options = get_option( $this->option_name, array() );

		if ( ! is_array( $this->options ) ) {
			$this->options = array();
		}

		$dir  = wp_upload_dir();
		$path = trailingslashit( $dir['basedir'] );

		if ( empty( $this->options['wds_sitemap-setup'] ) ) {
			if ( ! isset( $this->options['sitemap-stylesheet'] ) ) {
				$this->options['sitemap-stylesheet'] = 1;
			}
		}

		if ( empty( $this->options['sitemappath'] ) ) {
			$this->options['sitemappath'] = $path . 'sitemap.xml';
		}

		if ( empty( $this->options['sitemapurl'] ) ) {
			$this->options['sitemapurl'] = get_bloginfo( 'url' ) . '/sitemap.xml';
		}

		if ( empty( $this->options['sitemap-images'] ) ) {
			$this->options['sitemap-images'] = 0;
		}

		if ( empty( $this->options['sitemap-stylesheet'] ) ) {
			$this->options['sitemap-stylesheet'] = 0;
		}

		if ( empty( $this->options['sitemap-dashboard-widget'] ) ) {
			$this->options['sitemap-dashboard-widget'] = 0;
		}

		if ( empty( $this->options['sitemap-disable-automatic-regeneration'] ) ) {
			$this->options['sitemap-disable-automatic-regeneration'] = 'auto';
		}
		if ( ! isset( $this->options['sitemap-update-frequency'] ) ) {
			$this->options['sitemap-update-frequency'] = Cron::get()->get_default_frequency();
		}
		if ( ! isset( $this->options['sitemap-update-dow'] ) ) {
			$this->options['sitemap-update-dow'] = wp_rand( 0, 6 );
		}
		if ( ! isset( $this->options['sitemap-update-tod'] ) ) {
			$this->options['sitemap-update-tod'] = wp_rand( 0, 23 );
		}

		if ( empty( $this->options['verification-pages'] ) ) {
			$this->options['verification-pages'] = '';
		}

		if ( empty( $this->options['sitemap-buddypress-groups'] ) ) {
			$this->options['sitemap-buddypress-groups'] = 0;
		}

		if ( empty( $this->options['sitemap-buddypress-profiles'] ) ) {
			$this->options['sitemap-buddypress-profiles'] = 0;
		}

		if ( empty( $this->options['verification-google-meta'] ) ) {
			$this->options['verification-google-meta'] = '';
		}
		if ( empty( $this->options['verification-bing-meta'] ) ) {
			$this->options['verification-bing-meta'] = '';
		}
		if ( empty( $this->options['additional-metas'] ) ) {
			$this->options['additiona-metas'] = array();
		}

		if ( ! isset( $this->options['crawler-cron-enable'] ) ) {
			$this->options['crawler-cron-enable'] = false;
		}
		if ( ! isset( $this->options['crawler-frequency'] ) ) {
			$this->options['crawler-frequency'] = Cron::get()->get_default_frequency();
		}
		if ( ! isset( $this->options['crawler-dom'] ) ) {
			$this->options['crawler-dom'] = wp_rand( 1, 28 );
		}
		if ( ! isset( $this->options['crawler-dow'] ) ) {
			$this->options['crawler-dow'] = wp_rand( 0, 6 );
		}
		if ( ! isset( $this->options['crawler-tod'] ) ) {
			$this->options['crawler-tod'] = wp_rand( 0, 23 );
		}
		if ( ! isset( $this->options['items-per-sitemap'] ) ) {
			$this->options['items-per-sitemap'] = Utils::DEFAULT_ITEMS_PER_SITEMAP;
		}
		if ( ! isset( $this->options['override-native'] ) ) {
			$this->options['override-native'] = true;
		}
		if ( ! isset( $this->options['enable-news-sitemap'] ) ) {
			$this->options['enable-news-sitemap'] = false;
		}
		if ( ! isset( $this->options['news-publication'] ) ) {
			$this->options['news-publication'] = get_bloginfo( 'name' );
		}
		if ( ! isset( $this->options['news-sitemap-included-post-types'] ) ) {
			$this->options['news-sitemap-included-post-types'] = array( 'post' );
		}
		if ( ! isset( $this->options['news-sitemap-excluded-post-ids'] ) ) {
			$this->options['news-sitemap-excluded-post-ids'] = array();
		}

		update_option( $this->option_name, $this->options );
	}

	/**
	 * Retrieves the default view options.
	 *
	 * @return array The default view options.
	 */
	protected function get_view_defaults() {
		return $this->populate_view_defaults();
	}

	/**
	 * Populates the view defaults with crawl report data.
	 *
	 * @return array
	 */
	protected function populate_view_defaults() {
		$args = parent::get_view_defaults();

		$view                 = \smartcrawl_get_array_value( $args, '_view' );
		$view                 = empty( $view ) ? array() : $view;
		$seo_service          = Service::get( Service::SERVICE_SEO );
		$view['crawl_report'] = $seo_service->get_report();

		return array( '_view' => $view );
	}

	/**
	 * Validates the day of the week value.
	 *
	 * @param int $dow The day of the week value.
	 *
	 * @return int The validated day of the week value, or 0 if invalid.
	 */
	private function validate_dow( $dow ) {
		return in_array( $dow, range( 0, 6 ), true ) ? $dow : 0;
	}

	/**
	 * Validates the date of month value.
	 *
	 * @param int $dom The date of month value.
	 *
	 * @return int The validated value, or 1 if invalid.
	 */
	private function validate_dom( $dom ) {
		return in_array( $dom, range( 1, 28 ), true ) ? $dom : 1;
	}

	/**
	 * Retrieves a list of email recipients for sitemap notifications.
	 *
	 * @return array
	 */
	public static function get_email_recipients() {
		$options           = Settings::get_component_options( self::COMP_SITEMAP );
		$recipients        = \smartcrawl_get_array_value( $options, 'sitemap-email-recipients' );
		$dash_profile_data = \smartcrawl_get_dash_profile_data();

		if ( is_null( $recipients ) && $dash_profile_data ) {
			return array(
				array(
					'name'  => $dash_profile_data->user_login,
					'email' => $dash_profile_data->user_email,
				),
			);
		}

		return $recipients ? $recipients : array();
	}

	/**
	 * Checks if a recipient exists in the recipient array.
	 *
	 * @param array $recipient The recipient to check.
	 * @param array $recipient_array The array of recipients.
	 *
	 * @return bool Whether the recipient exists or not.
	 */
	private static function recipient_exists( $recipient, $recipient_array ) {
		$emails = array_column( $recipient_array, 'email' );
		$needle = (string) \smartcrawl_get_array_value( $recipient, 'email' );

		return in_array( $needle, $emails, true );
	}

	/**
	 * Sanitizes crawler emails.
	 *
	 * @param array $input  Input.
	 * @param array $result Result.
	 *
	 * @return array
	 */
	private function sanitize_crawler_emails( $input, $result ) {
		$email_recipients = \smartcrawl_get_array_value( $input, 'sitemap-email-recipients' );

		if ( ! empty( $email_recipients ) ) {
			$sanitized_recipients = array();
			foreach ( $email_recipients as $recipient ) {
				$recipient_name  = \smartcrawl_get_array_value( $recipient, 'name' );
				$recipient_email = \smartcrawl_get_array_value( $recipient, 'email' );

				if (
					$recipient_name && $recipient_email
					&& sanitize_text_field( $recipient_name ) === $recipient_name
					&& sanitize_email( $recipient_email ) === $recipient_email
					&& ! self::recipient_exists( $recipient, $sanitized_recipients )
				) {
					$sanitized_recipients[] = $recipient;
				} else {
					add_settings_error(
						$this->option_name,
						'email-recipients-invalid',
						esc_html__( 'Some email recipients could not be saved.', 'wds' )
					);
				}
			}
			$result['sitemap-email-recipients'] = $sanitized_recipients;
		}

		return $result;
	}

	/**
	 * Sanitizes sitemap options.
	 *
	 * @since 3.7.0
	 *
	 * @param mixed  $value          The sanitized option value.
	 * @param string $option         The option name.
	 * @param mixed  $original_value The original value passed to the function.
	 */
	public function sanitize_option( $value, $option, $original_value ) {
		if ( isset( $original_value['troubleshoot-count'] ) ) {
			$value['troubleshoot-count'] = (int) $original_value['troubleshoot-count'];
		}

		return $value;
	}
}