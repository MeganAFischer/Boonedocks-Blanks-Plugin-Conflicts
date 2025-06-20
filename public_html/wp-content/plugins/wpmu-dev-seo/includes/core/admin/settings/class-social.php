<?php
/**
 * Control Social component settings.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin\Settings;

use SmartCrawl\Controllers\Assets;
use SmartCrawl\Schema\Type_Constants;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * Social component settings.
 */
class Social extends Admin_Settings {

	use Singleton;

	/**
	 * Validate submitted options
	 *
	 * @param array $input Raw input.
	 *
	 * @return array Validated input
	 */
	public function validate( $input ) {
		$result = array();

		if ( ! empty( $input['wds_social-setup'] ) ) {
			$result['wds_social-setup'] = true;
		}

		$result['disable-schema'] = $this->disable_schema( $input );

		$urls = array(
			'facebook_url',
			'instagram_url',
			'linkedin_url',
			'pinterest_url',
			'youtube_url',
		);
		foreach ( $urls as $type ) {
			if ( empty( $input[ $type ] ) ) {
				continue;
			}
			$social_url = trim( $input[ $type ] );
			if ( ! preg_match( '/^https?:\/\//', $social_url ) ) {
				add_settings_error(
					$this->option_name,
					'social_url_invalid',
					esc_html__( 'Some social URLs could not be saved. Please try again.', 'wds' )
				);
				continue;
			}
			$result[ $type ] = $social_url;
		}

		if ( ! empty( $input['sitename'] ) ) {
			$result['sitename'] = sanitize_text_field( $input['sitename'] );
		}
		if ( ! empty( $input['override_name'] ) ) {
			$result['override_name'] = sanitize_text_field( $input['override_name'] );
		}
		if ( ! empty( $input['organization_name'] ) ) {
			$result['organization_name'] = sanitize_text_field( $input['organization_name'] );
		}
		if ( ! empty( $input['organization_logo'] ) ) {
			$result['organization_logo'] = sanitize_text_field( $input['organization_logo'] );
		}
		if ( ! empty( $input['schema_type'] ) ) {
			$result['schema_type'] = sanitize_text_field( $input['schema_type'] );
		}
		if ( ! empty( $input['twitter_username'] ) ) {
			$result['twitter_username'] = sanitize_text_field( $input['twitter_username'] );
		}
		if ( ! empty( $input['twitter-card-type'] ) ) {
			$result['twitter-card-type'] = sanitize_text_field( $input['twitter-card-type'] );
		}
		if ( ! empty( $input['fb-app-id'] ) ) {
			$result['fb-app-id'] = sanitize_text_field( $input['fb-app-id'] );
		}

		$result['og-enable']           = ! empty( $input['og-enable'] );
		$result['twitter-card-enable'] = ! empty( $input['twitter-card-enable'] );

		$this->toggle_og_globally(
			$result['og-enable']
		);

		$this->toggle_twitter_cards_globally(
			$result['twitter-card-enable']
		);

		if ( ! empty( $input['pinterest-verify'] ) ) {
			$pin                                     = \SmartCrawl\Social\Pinterest_Printer::get();
			$raw                                     = trim( $input['pinterest-verify'] );
			$tag                                     = $pin->get_verified_tag( $raw );
			$result['pinterest-verify']              = str_replace( ' ', '', $raw ) === str_replace( ' ', '', $tag ) ? $tag : false;
			$result['pinterest-verification-status'] = str_replace( ' ', '', $raw ) === str_replace( ' ', '', $tag ) ? '' : 'fail';
		} else {
			$result['pinterest-verification-status'] = false;
		}

		return $result;
	}

	/**
	 * Toggle OpenGraph activation status.
	 *
	 * @param bool $new_value OpenGraph status.
	 *
	 * @return void
	 */
	private function toggle_og_globally( $new_value ) {
		$this->toggle_setting_globally( 'og-active', $new_value );
	}

	/**
	 * Toggle section activation status.
	 *
	 * @param string $prefix Section prefix.
	 * @param bool   $new_value Section status.
	 *
	 * @return void
	 */
	private function toggle_setting_globally( $prefix, $new_value ) {
		$existing_settings = Settings::get_specific_options( 'wds_onpage_options' );
		$strings           = array(
			'home',
			'author',
			'date',
			'search',
			'404',
			'category',
			'post_tag',
			'bp_groups',
			'bp_profile',
		);

		foreach ( get_taxonomies( array( '_builtin' => false ), 'objects' ) as $taxonomy ) {
			$strings[] = $taxonomy->name;
		}

		foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
			$strings[] = $post_type;
		}

		foreach ( \smartcrawl_get_archive_post_types() as $archive_post_type ) {
			$strings[] = $archive_post_type;
		}

		foreach ( $strings as $string ) {
			$existing_settings[ sprintf( '%s-%s', $prefix, $string ) ] = $new_value;
		}

		Settings::update_specific_options( 'wds_onpage_options', $existing_settings );
	}

	/**
	 * Toggle Twitter Card activation status.
	 *
	 * @param bool $new_value Twitter Card status.
	 *
	 * @return void
	 */
	private function toggle_twitter_cards_globally( $new_value ) {
		$this->toggle_setting_globally( 'twitter-active', $new_value );
	}

	/**
	 * Initialize class.
	 *
	 * @return void
	 */
	public function init() {
		$this->option_name = 'wds_social_options';
		$this->name        = Settings::COMP_SOCIAL;
		$this->slug        = Settings::TAB_SOCIAL;
		$this->action_url  = admin_url( 'options.php' );
		$this->page_title  = sprintf(
		/* translators: %s: plugin title */
			__( '%s Wizard: Social', 'wds' ),
			\smartcrawl_get_plugin_title()
		);

		add_action( 'wp_ajax_wds_change_social_status', array( $this, 'change_social_component_status' ) );

		parent::init();

		remove_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ), 96 );
	}

	/**
	 * Get title of Social component.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Social', 'wds' );
	}

	/**
	 * Add admin settings page
	 */
	public function options_page() {
		parent::options_page();

		$options = Settings::get_component_options( $this->name );
		$options = wp_parse_args( $options, $this->get_default_options() );

		$arguments               = array(
			'options' => $options,
		);
		$arguments['active_tab'] = $this->get_active_tab( 'tab_open_graph' );
		wp_enqueue_script( Assets::SOCIAL_PAGE_JS );
		wp_enqueue_media();

		$this->render_page( 'social/social-settings', $arguments );
	}

	/**
	 * Gets default options set and their initial values
	 *
	 * @return array
	 */
	public function get_default_options() {
		return array(
			// Accounts.
			'sitename'            => get_bloginfo( 'name' ),
			'disable-schema'      => false,
			'schema_type'         => Type_Constants::TYPE_ORGANIZATION,
			'override_name'       => '',
			'organization_name'   => '',
			'organization_logo'   => '',
			'twitter_username'    => '',
			'facebook_url'        => '',
			'instagram_url'       => '',
			'linkedin_url'        => '',
			'pinterest_url'       => '',
			'youtube_url'         => '',
			// Twitter.
			'twitter-card-enable' => true,
			'twitter-card-type'   => '',
			// Pinterest.
			'pinterest-verify'    => '',
			// OpenGraph.
			'og-enable'           => true,
			// Facebook-specific.
			'fb-app-id'           => '',
		);
	}

	/**
	 * Default settings
	 */
	public function defaults() {
		$options = Settings::get_component_options( $this->name );

		foreach ( $this->get_default_options() as $opt => $default ) {
			if ( ! isset( $options[ $opt ] ) ) {
				$options[ $opt ] = $default;
			}
		}

		update_option( $this->option_name, $options );
	}

	/**
	 * Disable schema.
	 *
	 * @param array $input Raw input data from request.
	 *
	 * @return bool
	 */
	private function disable_schema( $input ) {
		if ( isset( $input['disable-schema'] ) ) {
			return ! empty( $input['disable-schema'] );
		} else {
			$previous = Settings::get_component_options( $this->name );

			return (bool) \smartcrawl_get_array_value( $previous, 'disable-schema' );
		}
	}

	/**
	 * Change Social component status.
	 */
	public function change_social_component_status() {
		$request_data = $this->get_request_data();

		if ( empty( $request_data ) ) {
			wp_send_json_error();
		}

		$status                       = (bool) \smartcrawl_get_array_value( $request_data, 'status' );
		$options                      = self::get_specific_options( 'wds_settings_options' );
		$options[ self::COMP_SOCIAL ] = ! ! $status;

		self::update_specific_options( 'wds_settings_options', $options );

		wp_send_json_success();
	}

	/**
	 * Get request data.
	 *
	 * @return array
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-social-nonce' )
			? $_POST
			: array();
	}
}
