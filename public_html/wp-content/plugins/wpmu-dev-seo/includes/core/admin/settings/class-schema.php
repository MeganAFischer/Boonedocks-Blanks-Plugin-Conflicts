<?php
/**
 * Schema settings class
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin\Settings;

use SmartCrawl\Controllers\Assets;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Youtube_Data_Fetcher;

/**
 * Schema
 */
class Schema extends Admin_Settings {

	use Singleton;

	/**
	 * Schema page title.
	 *
	 * @var string
	 */
	public $page_title;

	/**
	 * Validate and save input data and return validated options.
	 *
	 * @param array $input Input data.
	 *
	 * @return array
	 */
	public function validate( $input ) {
		$input = $this->validate_and_save_social_options( $input );

		$validated = array();
		foreach ( $input as $setting_key => $setting_value ) {
			$validated[ $setting_key ] = $this->validate_setting( $setting_key, $setting_value );
		}

		foreach ( $this->get_toggle_settings() as $toggle_setting ) {
			if ( ! isset( $validated[ $toggle_setting ] ) ) {
				$validated[ $toggle_setting ] = false;
			}
		}

		do_action_deprecated(
			'smartcrawl_before_save_schema',
			array( $input, $validated ),
			'6.4.2',
			'smartcrawl_after_sanitize_schema',
			__( 'Please use our new hook `smartcrawl_after_sanitize_schema` in SmartCrawl.', 'wds' ),
		);

		/**
		 * Action hook to trigger after schema data is validated and before save.
		 *
		 * @since 3.7.0
		 *
		 * @param array $input     Input data.
		 * @param array $validated Validated input.
		 */
		do_action( 'smartcrawl_after_sanitize_schema', $input, $validated );

		return $validated;
	}

	/**
	 * Get title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Schema', 'wds' );
	}

	/**
	 * Return default options.
	 *
	 * @return void
	 */
	public function defaults() {
		$options = Settings::get_component_options( $this->name );

		foreach ( $this->get_default_options() as $opt => $default ) {
			if ( ! isset( $options[ $opt ] ) ) {
				$options[ $opt ] = $default;
			}
		}

		Settings::update_component_options( $this->name, $options );
	}

	/**
	 * Initialize settings and hooks.
	 */
	public function init() {
		$this->option_name = 'wds_schema_options';
		$this->name        = Settings::COMP_SCHEMA;
		$this->slug        = Settings::TAB_SCHEMA;
		$this->action_url  = admin_url( 'options.php' );
		$this->page_title  = sprintf(
		/* translators: %s: plugin title */
			__( '%s Wizard: Schema', 'wds' ),
			\smartcrawl_get_plugin_title()
		);

		add_action( 'wp_ajax_wds-change-schema-status', array( $this, 'change_schema_component_status' ) );
		add_action( 'wp_ajax_wds-authorize-yt-api-key', array( $this, 'authorize_youtube_api_key' ) );
		add_action( 'wp_ajax_wds-search-post-meta', array( $this, 'search_schema_post_meta' ) );
		add_action( 'wp_ajax_wds-format-schema-location', array( $this, 'format_schema_location' ) );

		parent::init();

		remove_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ), 95 );
	}

	/**
	 * Send a formatted schema location as JSON response back to an Ajax request.
	 */
	public function format_schema_location() {
		$conditions = wp_unslash( $_GET['conditions'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput

		$count        = - 1;
		$summary_item = false;
		$or_texts     = array();
		foreach ( $conditions as $condition_group ) {
			$and_texts = array();
			foreach ( $condition_group as $condition ) {
				++$count;

				$lhs      = \smartcrawl_get_array_value( $condition, 'lhs' );
				$lhs_text = $this->get_lhs_text( $lhs );

				$operator      = \smartcrawl_get_array_value( $condition, 'operator' );
				$operator_text = '=' === $operator ? '=' : '≠';

				$rhs      = \smartcrawl_get_array_value( $condition, 'rhs' );
				$rhs_text = $this->get_rhs_text( $lhs, $rhs );

				if ( 'show_globally' === $lhs || 'homepage' === $lhs ) {
					$and_texts[] = $lhs_text;
					if ( ! $summary_item ) {
						$summary_item = $lhs_text;
					}
				} else {
					$and_texts[] = sprintf( '%s %s %s', $lhs_text, $operator_text, $rhs_text );
					if ( ! $summary_item ) {
						$summary_item = $rhs_text;
					}
				}
			}

			$and_text   = implode( ' & ', $and_texts );
			$or_texts[] = $and_text;
		}

		$full_text    = join( ' OR ', $or_texts );
		$summary_text = $summary_item;
		if ( $count ) {
			$summary_text = sprintf( '%s, +%d more', $summary_text, $count );
		}

		wp_send_json(
			array(
				'full'    => $full_text,
				'summary' => $summary_text,
			)
		);
	}

	/**
	 * Get the text of a left operand for a condition rule.
	 *
	 * @param string $lhs Left operand value for a condition rule.
	 *
	 * @return string
	 */
	private function get_lhs_text( $lhs ) {
		$texts = array(
			'post_type'     => __( 'Post type', 'wds' ),
			'show_globally' => __( 'Show Globally', 'wds' ),
			'homepage'      => __( 'Homepage', 'wds' ),
			'author_role'   => __( 'Author role', 'wds' ),
			'post_format'   => __( 'Post format', 'wds' ),
			'page_template' => __( 'Page template', 'wds' ),
			'product_type'  => __( 'Product type', 'wds' ),
		);
		if ( isset( $texts[ $lhs ] ) ) {
			return $texts[ $lhs ];
		}

		$post_types = array_map(
			function ( $post_type ) {
				return get_post_type_object( $post_type )->labels->singular_name;
			},
			\smartcrawl_frontend_post_types()
		);
		if ( isset( $post_types[ $lhs ] ) ) {
			return $post_types[ $lhs ];
		}

		$taxonomies = $this->get_taxonomies_singular();
		if ( isset( $taxonomies[ $lhs ] ) ) {
			return $taxonomies[ $lhs ];
		}

		return '';
	}

	/**
	 * Get the text of a right operand for a condition rule.
	 *
	 * @param string     $lhs Left operand value for a condition rule.
	 * @param string|int $rhs Right operand value for a condition rule.
	 *
	 * @return string
	 */
	private function get_rhs_text( $lhs, $rhs ) {
		$product_types = array(
			'WC_Product_Variable' => __( 'Variable Product', 'wds' ),
			'WC_Product_Simple'   => __( 'Simple Product', 'wds' ),
			'WC_Product_Grouped'  => __( 'Grouped Product', 'wds' ),
			'WC_Product_External' => __( 'External Product', 'wds' ),
		);
		switch ( $lhs ) {
			case 'post_type':
				$post_type = get_post_type_object( $rhs );

				return $post_type ? $post_type->labels->singular_name : '';

			case 'show_globally':
			case 'homepage':
				return '';

			case 'author_role':
				return isset( wp_roles()->role_names[ $rhs ] )
					? wp_roles()->role_names[ $rhs ]
					: '';

			case 'post_format':
				return $rhs;

			case 'page_template':
				$page_templates = wp_get_theme()->get_page_templates();

				return isset( $page_templates[ $rhs ] )
					? $page_templates[ $rhs ]
					: '';

			case 'product_type':
				return (string) \smartcrawl_get_array_value( $product_types, $rhs );
		}

		if ( in_array( $lhs, \smartcrawl_frontend_post_types(), true ) ) {
			$post = get_post( $rhs );

			return $post ? $post->post_title : '';
		}

		$taxonomies = $this->get_taxonomies_singular();
		if ( isset( $taxonomies[ $lhs ] ) ) {
			$taxonomy_term = get_term( $rhs, $lhs );

			return $taxonomy_term && ! is_wp_error( $taxonomy_term )
				? $taxonomy_term->name
				: '';
		}

		return '';
	}

	/**
	 * Search and send post meta result as a JSON response back to an Ajax request.
	 */
	public function search_schema_post_meta() {
		$search_query = \smartcrawl_get_array_value( $_GET, 'term' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$results      = array();
		if ( empty( $search_query ) ) {
			wp_send_json( array( 'results' => $results ) );
		}

		global $wpdb;
		$meta_keys = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key from $wpdb->postmeta WHERE meta_key LIKE %s", '%' . $wpdb->esc_like( $search_query ) . '%' ) );
		foreach ( $meta_keys as $meta_key ) {
			$results[] = array(
				'id'   => $meta_key,
				'text' => $meta_key,
			);
		}
		wp_send_json( array( 'results' => $results ) );
	}

	/**
	 * Change schema status.
	 */
	public function change_schema_component_status() {
		$request_data = $this->get_request_data();

		if ( empty( $request_data ) ) {
			return;
		}

		$status                           = (bool) \smartcrawl_get_array_value( $request_data, 'status' );
		$social_options                   = self::get_component_options( self::COMP_SOCIAL );
		$social_options['disable-schema'] = ! $status;
		self::update_component_options( self::COMP_SOCIAL, $social_options );
	}

	/**
	 * Authorize Youtube API key.
	 */
	public function authorize_youtube_api_key() {
		$request_data = $this->get_request_data();

		if ( empty( $request_data ) ) {
			exit();
		}

		$key        = \smartcrawl_get_array_value( $request_data, 'key' );
		$video_info = Youtube_Data_Fetcher::get_video_info( 'https://www.youtube.com/watch?v=FfgT6zx4k3Q', $key );
		if ( $video_info ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Render Schema settings page.
	 */
	public function options_page() {
		$options = Settings::get_component_options( $this->name );
		$options = wp_parse_args( $options, $this->get_default_options() );

		$social_options = Settings::get_component_options( Settings::COMP_SOCIAL );
		$arguments      = array(
			'options'        => $options,
			'social_options' => $social_options,
			'active_tab'     => $this->get_active_tab( 'tab_general' ),
			'post_types'     => $this->get_post_types(),
			'taxonomies'     => $this->get_taxonomies(),
			'pages'          => $this->get_pages(),
		);

		wp_enqueue_script( Assets::SCHEMA_JS );
		wp_enqueue_script( Assets::SCHEMA_TYPES_JS );
		wp_enqueue_media();

		$this->render_page( 'schema/schema-settings', $arguments );
	}

	/**
	 * Get all pages.
	 *
	 * @return array
	 */
	private function get_pages() {
		$pages    = array();
		$wp_posts = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
				'order'          => 'ASC',
				'orderby'        => 'title',
			)
		);

		foreach ( $wp_posts as $page ) {
			/**
			 * Post object.
			 *
			 * @var \WP_Post $page
			 */
			$pages[ $page->ID ] = $page->post_title;
		}

		return $pages;
	}

	/**
	 * Get Taxonomy singular names.
	 *
	 * @return array
	 */
	private function get_taxonomies_singular() {
		return array_map(
			function ( $taxonomy ) {
				return isset( $taxonomy->labels->singular_name )
					? $taxonomy->labels->singular_name
					: $taxonomy->label;
			},
			\smartcrawl_frontend_taxonomies()
		);
	}

	/**
	 * Get all taxonomies.
	 *
	 * @return array
	 */
	private function get_taxonomies() {
		return array_map(
			function ( $taxonomy ) {
				return $taxonomy->label;
			},
			\smartcrawl_frontend_taxonomies()
		);
	}

	/**
	 * Get available post types.
	 *
	 * @return array
	 */
	private function get_post_types() {
		$post_types = array();
		foreach (
			get_post_types(
				array(
					'public'      => true,
					'show_ui'     => true,
					'has_archive' => true,
				)
			) as $post_type
		) {
			if ( in_array( $post_type, array( 'revision', 'nav_menu_item', 'attachment' ), true ) ) {
				continue;
			}
			$pt                       = get_post_type_object( $post_type );
			$post_types[ $post_type ] = isset( $pt->labels->archives ) && $pt->labels->archives !== $pt->label
				? $pt->labels->archives
				: $pt->label . ' ' . esc_html__( 'Archive', 'wds' );
		}

		return $post_types;
	}

	/**
	 * Get default options.
	 *
	 * @return array
	 */
	public function get_default_options() {
		return array(
			'person_job_title'                   => '',
			'person_phone_number'                => '',
			'organization_type'                  => '',
			'organization_contact_type'          => 'customer support',
			'organization_phone_number'          => '',
			'schema_main_navigation_menu'        => '',
			'schema_archive_main_entity_type'    => '',
			'schema_yt_api_key'                  => '',
			'person_brand_name'                  => '',
			'person_bio'                         => '',
			'organization_description'           => '',
			'schema_website_logo'                => 0,
			'person_portrait'                    => 0,
			'person_contact_page'                => 0,
			'organization_contact_page'          => 0,
			'schema_output_page'                 => 0,
			'schema_about_page'                  => 0,
			'schema_contact_page'                => 0,
			'schema_default_image'               => 0,
			'person_brand_logo'                  => 0,
			'sitelinks_search_box'               => true,
			'schema_wp_header_footer'            => false,
			'schema_enable_comments'             => false,
			'schema_enable_author_url'           => true,
			'schema_enable_author_gravatar'      => false,
			'schema_enable_post_type_archives'   => true,
			'schema_disabled_post_type_archives' => array(),
			'schema_enable_author_archives'      => true,
			'schema_enable_search'               => true,
			'schema_enable_date_archives'        => true,
			'schema_enable_taxonomy_archives'    => true,
			'schema_disabled_taxonomy_archives'  => array(),
			'schema_enable_audio'                => false,
			'schema_enable_video'                => false,
			'schema_enable_yt_api'               => false,
			'schema_enable_test_button'          => true,
		);
	}

	/**
	 * Validate a setting.
	 *
	 * @param string $key Setting's key.
	 * @param string $value Setting's value.
	 *
	 * @return mixed
	 */
	private function validate_setting( $key, $value ) {
		$validation_method = $this->get_validation_method( $key );

		return call_user_func( $validation_method, $value );
	}

	/**
	 * Get validation method for a setting.
	 *
	 * @param string $setting Setting's key.
	 *
	 * @return \Closure|string
	 */
	private function get_validation_method( $setting ) {
		$text     = array(
			'sitename',
			'schema_type',
			'override_name',
			'person_job_title',
			'person_phone_number',
			'organization_type',
			'organization_name',
			'organization_contact_type',
			'organization_phone_number',
			'twitter_username',
			'fb-app-id',
			'schema_main_navigation_menu',
			'schema_archive_main_entity_type',
			'schema_yt_api_key',
			'person_brand_name',
		);
		$textarea = array(
			'person_bio',
			'organization_description',
		);
		$int      = array(
			'schema_website_logo',
			'person_portrait',
			'person_contact_page',
			'organization_contact_page',
			'schema_output_page',
			'schema_about_page',
			'schema_contact_page',
			'schema_default_image',
			'person_brand_logo',
		);
		$url      = array(
			'organization_logo',
			'facebook_url',
			'instagram_url',
			'linkedin_url',
			'pinterest_url',
			'youtube_url',
		);

		if ( in_array( $setting, $text, true ) ) {
			return 'sanitize_text_field';
		} elseif ( in_array( $setting, $textarea, true ) ) {
			return 'sanitize_textarea_field';
		} elseif ( in_array( $setting, $url, true ) ) {
			return 'esc_url_raw';
		} elseif ( in_array( $setting, $int, true ) ) {
			return 'intval';
		} elseif ( in_array( $setting, $this->get_toggle_settings(), true ) ) {
			return function ( $value ) {
				return ! ! $value;
			};
		}

		return 'sanitize_text_field';
	}

	/**
	 * Validate and save social options.
	 *
	 * @param array $input Input data.
	 *
	 * @return array
	 */
	private function validate_and_save_social_options( $input ) {
		$settings = Settings::get_specific_options( 'wds_social_options' );

		$social_option_keys = array(
			'sitename',
			'schema_type',
			'override_name',
			'organization_name',
			'organization_logo',
			'twitter_username',
			'fb-app-id',
			'facebook_url',
			'instagram_url',
			'linkedin_url',
			'pinterest_url',
			'youtube_url',
		);
		foreach ( $social_option_keys as $key ) {
			$settings[ $key ] = $this->validate_setting( $key, (string) \smartcrawl_get_array_value( $input, $key ) );

			if ( isset( $input[ $key ] ) ) {
				unset( $input[ $key ] );
			}
		}

		Settings::update_specific_options( 'wds_social_options', $settings );

		return $input;
	}

	/**
	 * Get toggle settings.
	 *
	 * @return array
	 */
	private function get_toggle_settings() {
		return array(
			'sitelinks_search_box',
			'schema_wp_header_footer',
			'schema_enable_comments',
			'schema_enable_author_url',
			'schema_enable_author_gravatar',
			'schema_enable_post_type_archives',
			'schema_disabled_post_type_archives',
			'schema_enable_author_archives',
			'schema_enable_search',
			'schema_enable_date_archives',
			'schema_enable_taxonomy_archives',
			'schema_disabled_taxonomy_archives',
			'schema_enable_audio',
			'schema_enable_video',
			'schema_enable_yt_api',
			'schema_enable_test_button',
		);
	}

	/**
	 * Get request data.
	 *
	 * @return array
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-schema-nonce' )
			? $_POST
			: array();
	}
}