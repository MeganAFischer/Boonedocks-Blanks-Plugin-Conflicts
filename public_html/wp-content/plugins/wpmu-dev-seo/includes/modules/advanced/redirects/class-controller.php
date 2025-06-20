<?php
/**
 * Manages Redirects functionality.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Redirects;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Controllers;
use SmartCrawl\Integration\Maxmind;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\SmartCrawl;
use SmartCrawl\String_Utils;

/**
 * Redirects Controller.
 */
class Controller extends Controllers\Submodule_Controller {

	use Singleton;

	const REDIRECTION_NOTICE = 'smartcrawl_redirection_notice';

	/**
	 * Redirects table.
	 *
	 * @var Database_Table
	 */
	private $redirects_table;

	/**
	 * Redirects utility.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Maxmind controllers.
	 *
	 * @var Controllers\Controller[]
	 */
	private $submodules;

	/**
	 * Constructor.
	 *
	 * @since 3.3.0
	 */
	protected function __construct() {
		parent::__construct();

		$this->submodules = array(
			Maxmind\Controller::get(),
			Maxmind\Cron::get(),
		);

		$this->module_title = __( 'URL Redirection', 'wds' );
		$this->event_name   = 'URL Redirection';
		$this->utils        = Utils::get();
	}

	/**
	 * Includes methods that runs always.
	 *
	 * @return void
	 */
	protected function always() {
		parent::always();

		if ( $this->should_run() ) {
			return;
		}

		foreach ( $this->submodules as $submodule ) {
			$submodule->stop();
		}
	}

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	protected function init() {
		parent::init();

		$this->redirects_table = Database_Table::get();

		add_action( 'wp', array( $this, 'intercept' ) );
		add_action( 'wp', array( $this, 'redirect_post' ), 99 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wds_plugin_update', array( $this, 'upgrade_table' ), 10, 2 );
		add_action( 'wp_ajax_smartcrawl_save_redirect', array( $this, 'save_redirect' ) );
		add_action( 'wp_ajax_smartcrawl_delete_redirect', array( $this, 'delete_redirect' ) );
		add_action( 'wp_ajax_smartcrawl_bulk_update_redirects', array( $this, 'bulk_update_redirects' ) );
		add_action( 'wp_ajax_smartcrawl_import_redirects', array( $this, 'import_redirects' ) );
		add_action( 'wp_ajax_smartcrawl_export_redirects', array( $this, 'export_redirects' ) );

		add_action( 'wp_trash_post', array( $this, 'manage_trash_post_notice' ) );
		add_action( 'before_delete_post', array( $this, 'manage_delete_post_notice' ) );

		if ( ! empty( $this->options['attachments'] ) ) {
			add_action( 'template_redirect', array( $this, 'redirect_attachments' ) );
		}

		foreach ( $this->submodules as $submodule ) {
			$submodule->run();
		}
	}

	/**
	 * Includes methods when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {
		parent::terminate();

		remove_action( 'wp', array( $this, 'intercept' ) );
		remove_action( 'wp', array( $this, 'redirect_post' ), 99 );
		remove_action( 'admin_notices', array( $this, 'admin_notices' ) );
		remove_action( 'wds_plugin_update', array( $this, 'upgrade_table' ), 10, 2 );
		remove_action( 'wp_ajax_smartcrawl_save_redirect', array( $this, 'save_redirect' ) );
		remove_action( 'wp_ajax_smartcrawl_delete_redirect', array( $this, 'delete_redirect' ) );
		remove_action( 'wp_ajax_smartcrawl_bulk_update_redirects', array( $this, 'bulk_update_redirects' ) );
		remove_action( 'wp_ajax_smartcrawl_import_redirects', array( $this, 'import_redirects' ) );
		remove_action( 'wp_ajax_smartcrawl_export_redirects', array( $this, 'export_redirects' ) );

		remove_action( 'wp_trash_post', array( $this, 'manage_trash_post_notice' ), 10, 2 );
		remove_action( 'before_delete_post', array( $this, 'manage_delete_post_notice' ) );
		remove_action( 'template_redirect', array( $this, 'redirect_attachments' ) );
	}

	/**
	 * Manages admin notices.
	 *
	 * @return void
	 */
	public function admin_notices() {
		self::display_upgrade_notice();
		self::update_redirect_notices();
	}

	/**
	 * Displays upgrade notice.
	 *
	 * @return void
	 */
	public function display_upgrade_notice() {
		$key                  = 'wds_redirect_upgrade_217';
		$redirects_admin_url  = Admin_Settings::admin_url( Settings::ADVANCED_MODULE ) . '&tab=' . $this->module_name;
		$dismissed_messages   = get_user_meta( get_current_user_id(), 'wds_dismissed_messages', true );
		$is_message_dismissed = \smartcrawl_get_array_value( $dismissed_messages, $key ) === true;
		$is_version_218       = version_compare( SMARTCRAWL_VERSION, '2.18.0', '=' );
		if (
			$is_message_dismissed ||
			! $is_version_218 ||
			! current_user_can( 'manage_options' )
		) {
			return;
		}
		?>
		<div
			class="notice-info notice is-dismissible wds-native-dismissible-notice"
			data-message-key="<?php echo esc_attr( $key ); ?>"
		>
			<p>
				<strong>
					<?php
					printf(
						/* translators: %s: plugin title */
						esc_html__( '%s URL redirects have been upgraded', 'wds' ),
						esc_html( \smartcrawl_get_plugin_title() )
					);
					?>
				</strong>
			</p>
			<p style="margin-bottom: 15px;">
				<?php esc_html_e( "We've changed how URL redirects are stored, and your existing redirects have been upgraded accordingly. Please check your existing redirects to ensure they work as expected.", 'wds' ); ?>
			</p>
			<a
				href="<?php echo esc_attr( $redirects_admin_url ); ?>"
				class="button button-primary"
			>
				<?php esc_html_e( 'Go to Redirects', 'wds' ); ?>
			</a>
			<a href="#" class="wds-native-dismiss"><?php esc_html_e( 'Dismiss', 'wds' ); ?></a>
			<p></p>
		</div>
		<?php
	}

	/**
	 * Displays an admin notice for redirects required after post statuse transitioning.
	 *
	 * @return void
	 */
	public function update_redirect_notices() {
		$screen = get_current_screen();

		if ( ! $screen || 'edit' !== $screen->base || empty( $screen->post_type ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['trashed'] ) ) {
			return;
		}

		$ids = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ids = array_filter(
			explode( ',', $ids ),
			function ( $id ) {
				return ! empty( $id );
			}
		);

		if ( empty( $ids ) ) {
			return;
		}

		$post_type_label = $this->get_post_type_label( $screen->post_type );

		foreach ( $ids as $post_id ) {
			$this->display_redirection_update_notice( $post_type_label, $post_id );
		}
	}

	/**
	 * Manages a post trash and adds a notice.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function manage_trash_post_notice( $post_id ) {
		$post_status = get_post_status( $post_id );

		if ( 'publish' !== $post_status ) {
			return;
		}

		$this->update_redirection_notice_transient( $post_id, 'trash' );
	}

	/**
	 * Manages a post delete and adds a notice.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function manage_delete_post_notice( $post_id ) {
		$post_status = get_post_status( $post_id );

		if ( 'publish' !== $post_status ) {
			return;
		}

		$this->update_redirection_notice_transient( $post_id, 'delete' );
	}

	/**
	 * Stores status transitioning in transient just before a post is trashed or deleted.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  Post status transitioning action. Eg: trash or delete.
	 */
	protected function update_redirection_notice_transient( $post_id, $action ) {
		$post_type_object = get_post_type_object( get_post_type( $post_id ) );

		if ( ! $post_type_object->public ) {
			return;
		}

		$parsed_url = wp_parse_url( get_permalink( $post_id ) );

		if ( ! $parsed_url ) {
			return;
		}

		$url = rtrim( $parsed_url['path'], '/' );

		if ( isset( $parsed_url['query'] ) ) {
			$url .= '?' . $parsed_url['query'];
		}

		if ( isset( $parsed_url['fragment'] ) ) {
			$url .= '#' . $parsed_url['fragment'];
		}

		$url = apply_filters( 'smartcrawl_redirection_notice_transient', $url, $post_id, $action );

		if ( empty( $url ) ) {
			return;
		}

		set_transient(
			self::REDIRECTION_NOTICE . '_' . get_current_user_id() . '_' . $post_id,
			wp_json_encode(
				array(
					'action' => $action,
					'url'    => $url,
				)
			),
			24 * HOUR_IN_SECONDS
		);
	}

	/**
	 * Retrieves the singular post type label.
	 *
	 * @param string $post_type Post type name.
	 *
	 * @return string
	 */
	protected function get_post_type_label( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object ) {
			$post_type_object = get_post_type_object( 'post' );
		}

		return $post_type_object->labels->singular_name;
	}

	/**
	 * Returns transition post status notice.
	 *
	 * @param string $post_type_label Post type. Eg: post or page.
	 * @param int    $post_id         Relative url to the post which will be used as redirection source.
	 */
	protected function display_redirection_update_notice( $post_type_label, $post_id ) {
		$transient_name = self::REDIRECTION_NOTICE . '_' . get_current_user_id() . '_' . $post_id;
		$transient_data = get_transient( $transient_name );

		if ( ! $transient_data ) {
			return;
		}

		$transient_data = json_decode( $transient_data, true );

		// Removes transient data.
		delete_transient( $transient_name );

		$redirect = $this->find_plain_redirect( $transient_data['url'] );

		if ( ! $redirect ) {
			$redirect = $this->find_regex_redirect( $transient_data['url'] );
		}

		if ( $redirect ) {
			return;
		}

		?>
		<div class="notice-warning notice is-dismissible wds-native-dismissible-notice">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
					/* translators: 1, 2: strong tag, 3: post type label, 4: post stats transition action, 5, 6: Link to redirection page, 7: Redirection source url. */
						esc_html__( '%1$sSEO Notice:%2$s A published %3$s(%8$s%7$s%9$s) has been moved to %4$s. %5$sAdd redirection%6$s to improve SEO.', 'wds' ),
						'<strong>',
						'</strong>',
						strtolower( $post_type_label ),
						$transient_data['action'],
						sprintf(
						/* translators: 1: Advanced Tools page url, 2: Redirection source url */
							'<a href="%1$s&tab=%2$s&add_redirect=1&source=%3$s">',
							Admin_Settings::admin_url( Settings::ADVANCED_MODULE ),
							$this->module_name,
							$transient_data['url']
						),
						'</a>',
						$transient_data['url'],
						'<code>',
						'</code>'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles to create database table if it's not existing.
	 *
	 * @return void
	 */
	public function maybe_create_table() {
		$db_table = Database_Table::get();
		$db_table->create_table();
	}

	/**
	 * Upgrades pre v3.8.0 redirection table to latest structure.
	 *
	 * @since 3.8.0
	 *
	 * @param string       $new_version New version.
	 * @param string|false $old_version Old version.
	 *
	 * @return void
	 */
	public function upgrade_table( $new_version, $old_version ) {
		if ( ! $old_version || version_compare( $old_version, '3.8.0', '<' ) ) {
			Database_Table::get()->create_table();
		}

		if ( $old_version && version_compare( $old_version, '3.9.0', '<' ) ) {
			Database_Table::get()->upgrade_table();
		}
	}

	/**
	 * Returns localize script data.
	 *
	 * @return array
	 */
	public function localize_script_args() {
		return array(
			'active'             => ! empty( $this->options['active'] ),
			'non_redirect_types' => $this->utils->get_non_redirect_types(),
			'redirects'          => $this->get_redirects(),
			'maxmind_license'    => Maxmind\GeoDB::get()->get_license(),
			'options'            => array(
				'attachments'  => ! empty( $this->options['attachments'] ),
				'images_only'  => ! empty( $this->options['images_only'] ),
				'default_type' => isset( $this->options['default_type'] ) ? $this->options['default_type'] : $this->utils->get_default_type(),
			),
		);
	}

	/**
	 * Outputs submodule content to dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_content() {
		$total_cnt  = Database_Table::get()->get_count();
		$module_url = Admin_Settings::admin_url( $this->parent->module_name ) . '&tab=' . $this->module_name . '&add_redirect=1';
		?>

		<div class="wds-separator-top wds-draw-left-padded">
			<small><strong><?php esc_html_e( 'URL Redirects', 'wds' ); ?></strong></small>

			<?php if ( $this->should_run() ) : ?>

				<?php if ( empty( $total_cnt ) ) : ?>
					<p>
						<small><?php esc_html_e( 'Automatically redirect traffic from one URL to another.', 'wds' ); ?></small>
					</p>
					<a
						href="<?php echo esc_attr( $module_url ); ?>"
						class="sui-button sui-button-blue"
					>
						<?php esc_html_e( 'Add Redirect', 'wds' ); ?>
					</a>
				<?php else : ?>
					<span class="wds-right"><small><?php echo esc_html( $total_cnt ); ?></small></span>
				<?php endif; ?>

			<?php else : ?>
				<p>
					<small><?php esc_html_e( 'Automatically redirect traffic from one URL to another.', 'wds' ); ?></small>
				</p>
				<button
					type="button"
					data-module="<?php echo esc_attr( $this->parent->module_id ); ?>"
					data-submodule="<?php echo esc_attr( $this->module_id ); ?>"
					class="wds-activate-submodule wds-disabled-during-request sui-button sui-button-blue">

					<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>
			<?php endif; ?>
		</div>

		<?php
	}

	/**
	 * Sanitizes submitted options
	 *
	 * @param array $input Raw input.
	 *
	 * @return bool True if sanitized successfully, otherwise false.
	 */
	public function sanitize_options( $input ) {
		$old_options = $this->options;

		if ( isset( $input['active'] ) ) {
			$active = boolval( $input['active'] );

			if ( empty( $this->options['active'] ) || $active !== $this->options['active'] ) {
				$this->options['active'] = $active;

				do_action_deprecated(
					'smartcrawl_after_save_redirects',
					array( $old_options, $this->options ),
					'6.4.2',
					"smartcrawl_after_sanitize_{$this->module_id}",
					/* translators: %s: Module ID. */
					sprintf( __( 'Please use our new hook `smartcrawl_after_sanitize_%s` in SmartCrawl.', 'wds' ), $this->module_id )
				);

				return true;
			}

			unset( $input['active'] );
		}

		if ( empty( $input ) ) {
			return true;
		}

		$this->options['attachments']  = (bool) $input['attachments'];
		$this->options['images_only']  = (bool) $input['images_only'];
		$this->options['default_type'] = (int) $input['default_type'];

		do_action_deprecated(
			'smartcrawl_after_save_redirects',
			array( $old_options, $this->options ),
			'6.4.2',
			"smartcrawl_after_sanitize_{$this->module_id}",
			/* translators: %s: Module ID. */
			sprintf( __( 'Please use our new hook `smartcrawl_after_sanitize_%s` in SmartCrawl.', 'wds' ), $this->module_id )
		);

		return true;
	}

	/**
	 * Retrieves current path.
	 *
	 * @return string
	 */
	private function get_current_path() {
		return $this->utils->source_to_path( $this->get_current_url() );
	}

	/**
	 * Retrieves current url.
	 *
	 * @return string|false
	 */
	private function get_current_url() {
		if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
			return false;
		}

		// We don't need to escape here as we are using esc_url_raw later.
		$url  = is_ssl() ? 'https://' : 'http://';
		$url .= wp_unslash( $_SERVER['HTTP_HOST'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			// We don't need to escape here as we are using esc_url_raw later.
			$url .= wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		return esc_url_raw( $url );
	}

	/**
	 * Intercepts the page and redirects if needs be.
	 */
	public function intercept() {
		$redirect = $this->find_plain_redirect( $this->get_current_path() );

		if ( $redirect ) {
			$destination = $redirect->get_absolute_destination();
		} else {
			$redirect    = $this->find_regex_redirect( $this->get_current_url() );
			$destination = $this->find_regex_destination( $redirect );
		}

		if ( ! $redirect ) {
			return;
		}

		$type = $redirect->get_type();

		if ( empty( $type ) ) {
			return;
		}

		nocache_headers();

		if ( $type > 400 ) {
			global $is_IIS, $wp_query;

			$type = apply_filters( 'wp_redirect_status', $type );

			if ( ! $is_IIS && 'cgi-fcgi' !== PHP_SAPI ) {
				status_header( $type ); // This causes problems on IIS and some FastCGI setups.
			}

			status_header( "X-Redirect-By: 'SmartCrawl'" );

			$wp_query->set_404();

			return;
		}

		if ( Maxmind\GeoDB::get()->get_license() ) {
			$geo_rules = $redirect->get_rules();

			if ( ! empty( $geo_rules ) ) {
				$country         = Maxmind\GeoDB::get()->get_country_by_ip();
				$geo_destination = $this->find_geo_destination( $geo_rules, $country );

				if ( $geo_destination ) {
					$destination = $geo_destination;
				}
			}
		}

		// We're here, so redirect.
		if ( $destination && Utils::get()->get_full_url( $this->get_current_path() ) !== Utils::get()->get_full_url( $destination ) ) {
			// We can't use wp_safe_redirect because we also need to have external redirect.
			wp_redirect( $this->to_safe_redirection( $destination, $type ), $type, 'SmartCrawl' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			die();
		}
	}

	/**
	 * Finds plain redirect from path.
	 *
	 * @param string $path Path to be used for the search.
	 *
	 * @return false|Item
	 */
	private function find_plain_redirect( $path ) {
		$redirects = $this->redirects_table->get_redirects_by_path( $path );
		if ( empty( $redirects ) ) {
			return false;
		}

		$redirect = $this->find_match( $redirects );
		if ( ! $redirect ) {
			return false;
		}

		return $redirect;
	}

	/**
	 * Finds regex redirect from source.
	 *
	 * @param string $source Source.
	 *
	 * @return Item
	 */
	private function find_regex_redirect( $source ) {
		$redirects = $this->redirects_table->get_redirects_by_source_regex( $source );
		if ( ! empty( $redirects ) && is_array( $redirects ) ) {
			// We need to weed out partial matches and look for an exact match.
			foreach ( $redirects as $redirect ) {
				$pattern = $redirect->get_source();
				if ( ! String_Utils::starts_with( $pattern, '^' ) ) {
					$pattern = "^{$pattern}";
				}
				if ( ! String_Utils::ends_with( $pattern, '$' ) ) {
					$pattern = "{$pattern}$";
				}
				$pattern = str_replace( '~', '\~', $pattern );
				if ( preg_match( "~$pattern~", $source ) ) {
					return $redirect;
				}
			}
		}

		return null;
	}

	/**
	 * Finds regex destination from redirect.
	 *
	 * @param Item $redirect Redirect item.
	 *
	 * @return string | false
	 */
	private function find_regex_destination( $redirect ) {
		if ( ! $redirect ) {
			return false;
		}

		$pattern = str_replace( '~', '\~', $redirect->get_source() );

		return preg_replace(
			"~$pattern~",
			$redirect->get_absolute_destination(),
			$this->get_current_url()
		);
	}

	/**
	 * Finds Geolocation destination from redirect.
	 *
	 * @param array  $rules   Geolocation rules.
	 * @param string $country Country ISO code.
	 *
	 * @return string|false
	 */
	private function find_geo_destination( $rules, $country ) {
		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				$rule = (array) $rule;
			}

			if (
				( empty( $rule['indicate'] ) && in_array( $country, $rule['countries'], true ) ) ||
				( ! empty( $rule['indicate'] ) && ! in_array( $country, $rule['countries'], true ) )
			) {
				$destination = $rule['url'];

				if ( ! empty( $destination['id'] ) ) {
					$destination = get_permalink( $destination['id'] );

					if ( ! $destination ) {
						return false;
					}
				}

				return $destination;
			}
		}

		return false;
	}

	/**
	 * Retrieves query vars from url.
	 *
	 * @param string $url Url to be used.
	 *
	 * @return array
	 */
	private function get_url_query_vars( $url ) {
		parse_str(
			wp_parse_url( $url, PHP_URL_QUERY ) || '',
			$query_vars
		);

		return $query_vars;
	}

	/**
	 * Finds match from redirects.
	 *
	 * @param Item[] $redirects Redirect items.
	 *
	 * @return Item|null
	 */
	public function find_match( $redirects ) {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return null;
		}

		$current_query_vars = $this->get_url_query_vars( rawurldecode( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );

		foreach ( $redirects as $redirect ) {
			$redirect_query_vars = $this->get_url_query_vars( $redirect->get_source() );
			if ( \smartcrawl_arrays_same( $redirect_query_vars, $current_query_vars ) ) {
				return $redirect;
			}
		}

		return null;
	}

	/**
	 * Converts the redirection to a safe one
	 *
	 * @param string $destination Raw URL.
	 * @param int    $type        Type.
	 *
	 * @return string Safe redirection URL
	 */
	private function to_safe_redirection( $destination, $type ) {
		$fallback = apply_filters( 'wp_safe_redirect_fallback', home_url(), $type );
		$filter   = $this->allowed_hosts_filter( $destination );

		add_filter( 'allowed_redirect_hosts', $filter );

		$destination = wp_sanitize_redirect( $destination );
		$destination = wp_validate_redirect( $destination, $fallback );

		remove_filter( 'allowed_redirect_hosts', $filter );

		return $destination;
	}

	/**
	 * Redirects attachments to parent post
	 *
	 * If we can't determine parent post type,
	 * we at least throw the noindex header.
	 *
	 * Respects the `redirect-attachment-images-only` sub-option,
	 *
	 * @return void
	 */
	public function redirect_attachments() {
		if ( ! is_attachment() ) {
			return;
		}

		if ( ! empty( $this->options['images-only'] ) ) {
			$type = get_post_mime_type();
			if ( ! preg_match( '/^image\//', $type ) ) {
				return;
			}
		}

		// Get attachment URL.
		$url = wp_get_attachment_url( get_queried_object_id() );

		if ( ! empty( $url ) ) {
			wp_safe_redirect( $url, 301 );
			die;
		}

		// No URL found, let's noindex.
		header( 'X-Robots-Tag: noindex', true );
	}

	/**
	 * Performs page redirect
	 */
	public function redirect_post() {
		global $post;

		// Fixes redirection on archive pages - do not redirect if not singular.
		// Fixes: https://app.asana.com/0/46496453944769/505196129561557/f.
		if ( ! is_singular() || empty( $post->ID ) ) {
			return false;
		}

		// Allows optional filtering out.
		if ( ! apply_filters_deprecated(
			'wds_process_redirect',
			array( true ),
			'6.4.2',
			'smartcrawl_process_redirect',
			__( 'Please use our new filter `smartcrawl_process_redirect` in SmartCrawl.', 'wds' )
		) ) {
			return false;
		}

		if ( ! apply_filters( 'smartcrawl_process_redirect', true ) ) {
			return false;
		}

		$redirect = \smartcrawl_get_value( 'redirect', $post->ID );

		if ( $post && $redirect ) {
			wp_redirect( // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
				$this->sanitize_post_redirect( $redirect ),
				301
			);
			exit;
		}

		return true;
	}

	/**
	 * Sanitizes post redirect.
	 *
	 * @param string $destination Redirect destination.
	 *
	 * @return mixed|string
	 */
	private function sanitize_post_redirect( $destination ) {
		$filter = $this->allowed_hosts_filter( $destination );

		add_filter( 'allowed_redirect_hosts', $filter );

		$destination = wp_sanitize_redirect( $destination );
		$destination = wp_validate_redirect( $destination, home_url() );

		remove_filter( 'allowed_redirect_hosts', $filter );

		return $destination;
	}

	/**
	 * Ajax handler to save redirect.
	 *
	 * @return void
	 */
	public function save_redirect() {
		$data = $this->get_request_data();

		if ( empty( $data ) ) {
			wp_send_json_error( __( 'There is no data to proceed with your request.', 'wds' ) );
		}

		$id          = intval( \smartcrawl_get_array_value( $data, 'id' ) );
		$source      = \smartcrawl_get_array_value( $data, 'source' );
		$destination = \smartcrawl_get_array_value( $data, 'destination', '' );
		$type        = \smartcrawl_get_array_value( $data, 'type', '' );
		$title       = \smartcrawl_get_array_value( $data, 'title', '' );
		$options     = \smartcrawl_get_array_value( $data, 'options', array() );
		$rules       = \smartcrawl_get_array_value( $data, 'rules', array() );

		if ( empty( $source ) ) {
			wp_send_json_error( __( 'The redirect from URL is empty.', 'wds' ) );
		}

		if ( $this->utils->is_non_redirect_type( $type ) ) {
			$destination = '';
			$rules       = array();
		} elseif ( empty( $destination ) && empty( $rules ) ) {
			wp_send_json_error( __( 'The redirect from/to URL is empty.', 'wds' ) );
		}

		$redirect_item = $this->utils->create_redirect_item( $source, $destination, $type, $title, $options, $rules );

		if ( $redirect_item->is_regex() && $this->is_source_regex_invalid( $source ) ) {
			wp_send_json_error( array( 'message' => 'Invalid regex source.' ) );
		}

		if ( $id ) {
			$redirect_item->set_id( $id );
		}

		$table = Database_Table::get();
		$saved = $table->save_redirect( $redirect_item );

		if ( $saved ) {
			$redirect_item->set_id( $saved );

			$data = $redirect_item->deflate();

			wp_send_json_success( $this->populate_destination( $data ) );
		}

		wp_send_json_error();
	}

	/**
	 * Retrieves redirects from database table.
	 *
	 * @return array|false
	 */
	public function get_redirects() {
		if ( ! $this->should_run() ) {
			return false;
		}

		$redirects = Database_Table::get()->get_deflated_redirects();

		if ( ! $redirects ) {
			return false;
		}

		foreach ( $redirects as $key => $redirect ) {
			$redirects[ $key ] = $this->populate_destination( $redirect );
		}

		return $redirects;
	}

	/**
	 * Fills in data into redirect destination.
	 *
	 * @param array $redirect Redirect data.
	 *
	 * @return array
	 */
	private function populate_destination( $redirect ) {
		if ( ! empty( $redirect['destination'] ) ) {
			$redirect['destination'] = $this->format_destination( $redirect['destination'] );
		}

		if ( empty( $redirect['rules'] ) ) {
			$redirect['rules'] = array();
		}

		foreach ( $redirect['rules'] as $index => $rule ) {
			$rule['url'] = $this->format_destination( $rule['url'] );

			$redirect['rules'][ $index ] = $rule;
		}

		return $redirect;
	}

	/**
	 * Formatts destination.
	 *
	 * @param array $destination Destination data.
	 *
	 * @return array
	 */
	private function format_destination( $destination ) {
		if ( empty( $destination ) ) {
			return $destination;
		}

		if ( empty( $destination['id'] ) ) {
			$destination = array(
				'url'  => $destination,
				'type' => __( 'Url', 'wds' ),
			);
		} else {
			$post_id  = $destination['id'];
			$post_url = get_permalink( $post_id );

			if ( $post_url ) {
				$destination['url'] = str_replace( home_url(), '', $post_url );

				$post_type     = get_post_type( $post_id );
				$post_type_obj = get_post_type_object( $post_type );

				if ( $post_type_obj ) {
					$destination['title'] = get_the_title( $post_id );
					$destination['type']  = $post_type_obj->labels->singular_name;
					$destination['_type'] = $post_type;
				} else {
					$destination['type'] = __( 'Url', 'wds' );
				}
			}
		}

		return $destination;
	}

	/**
	 * Checks if a source regex is invalid.
	 *
	 * @param string $source Regex source.
	 *
	 * @return bool
	 */
	private function is_source_regex_invalid( $source ) {
		$with_escaped_delimiter = str_replace( '~', '\~', $source );

		return @preg_match( "~$with_escaped_delimiter~", null ) === false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Ajax handler to delete a redirect.
	 *
	 * @return void
	 */
	public function delete_redirect() {
		$data = $this->get_request_data();

		if ( empty( $data ) ) {
			wp_send_json_error( array( 'mesage' => __( 'Request data is not valid.', 'wds' ) ) );
		}

		$ids     = \smartcrawl_get_array_value( $data, 'ids' );
		$table   = Database_Table::get();
		$deleted = $table->delete_redirects( $ids );

		if ( $deleted ) {
			wp_send_json_success();
		}

		wp_send_json_error( array( 'message' => __( 'Something was wrong.', 'wds' ) ) );
	}

	/**
	 * Ajax handler to bulk update redirects.
	 *
	 * @return void
	 */
	public function bulk_update_redirects() {
		$data = $this->get_request_data();

		if ( empty( $data ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Failed to retrieve data.', 'wds' ) )
			);
		}

		$ids = \smartcrawl_get_array_value( $data, 'ids' );

		if ( empty( $ids ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Not found redirects.', 'wds' ) )
			);
		}

		$table     = Database_Table::get();
		$redirects = $table->get_redirects( $ids );

		if ( ! $redirects ) {
			wp_send_json_error(
				array( 'message' => __( 'Failed to get redirects by given IDs.', 'wds' ) )
			);
		}

		$destination = \smartcrawl_get_array_value( $data, 'destination', '' );
		$type        = \smartcrawl_get_array_value( $data, 'type', '' );
		$rules       = \smartcrawl_get_array_value( $data, 'rules', array() );

		$response = array();

		foreach ( $ids as $id ) {
			$redirect = \smartcrawl_get_array_value( $redirects, $id );

			if ( ! $redirect ) {
				wp_send_json_error(
					array(
						'message' => sprintf(
							/* translators: %s: Redirect id */
							__( 'Failed to get redirect by given ID: %s.', 'wds' ),
							$id
						),
					)
				);
			}

			if ( ! empty( $type ) ) {
				$redirect->set_type( $type );
			}

			if ( $this->utils->is_non_redirect_type( $type ) ) {
				$redirect->set_destination( '' );
				$redirect->set_rules( $rules );
			} else {
				if ( ! empty( $destination ) ) {
					$redirect->set_destination( $destination );
				}

				if ( ! empty( $rules ) ) {
					$redirect->set_rules( $rules );
				}
			}

			$response[ $id ] = $this->populate_destination( $redirect->deflate() );
		}

		$is_updated = $table->update_redirects( $redirects );

		if ( false === $is_updated ) {
			wp_send_json_error(
				array( 'message' => __( 'Failed to update redirects.', 'wds' ) )
			);
		}

		wp_send_json_success( $response );
	}

	/**
	 * Ajax handler to import redirects from JSON.
	 *
	 * Supports both JSON and CSV files for import. But only JSON will support
	 * location based redirects import. CSV file imports are kept only for the
	 * backward compatibility.
	 *
	 * @return void
	 */
	public function import_redirects() {
		$data = $this->get_request_data();

		if ( empty( $data ) ) {
			wp_send_json_error();
		}

		// File size can not exceed 1MB.
		$file_size = \smartcrawl_get_array_value( $_FILES, array( 'file', 'size' ) );

		if ( $file_size > 1000000 ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Please select a file under 1MB.', 'wds' ),
				)
			);
		}

		$redirects = array();

		$file_name = \smartcrawl_get_array_value( $_FILES, array( 'file', 'tmp_name' ) );
		$file_type = \smartcrawl_get_array_value( $_FILES, array( 'file', 'type' ) );

		// For backward compatibility only.
		if ( in_array( $file_type, \smartcrawl_csv_mime_types(), true ) ) {
			$redirects = $this->get_redirects_from_csv( $file_name, $errors );
		} elseif ( 'application/json' === $file_type ) {
			// Exports are only in json. So this is the correct format now.
			$redirects = $this->get_redirects_from_json( $file_name, $errors );
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Only JSON or CSV files are supported.', 'wds' ),
				)
			);
		}

		if ( $errors ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Some entries have invalid values. Please try again!', 'wds' ),
				)
			);
		}

		if ( empty( $redirects ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'No valid redirects were found, please check your file.', 'wds' ),
				)
			);
		}

		$inserted = $this->redirects_table->insert_redirects( $redirects );
		if ( ! $inserted ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'An error occurred while inserting CSV data into the database.', 'wds' ),
				)
			);
		}

		$redirects = $this->redirects_table->get_deflated_redirects();

		foreach ( $redirects as $key => $redirect ) {
			$redirects[ $key ] = $this->populate_destination( $redirect );
		}

		wp_send_json_success(
			array(
				'count'     => $inserted,
				'redirects' => $redirects,
			)
		);
	}

	/**
	 * Get redirects from CSV.
	 *
	 * @param string $file_name File path name.
	 * @param bool   $errors Is error?.
	 *
	 * @return array
	 */
	private function get_redirects_from_csv( $file_name, &$errors = false ) {
		$file = fopen( $file_name, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

		if ( ! $file ) {
			wp_send_json_error();
		}

		$redirects = array();

		while ( $redirect_data = fgetcsv( $file ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			list( $source, $destination, $type, $regex, $title, $rules ) = $redirect_data;

			if ( empty( $source ) ) {
				continue;
			}

			$options       = empty( $regex ) ? array() : array( 'regex' );
			$redirect_item = $this->utils->create_redirect_item( $source, $destination, $type, $title, $options, $rules );
			if ( $redirect_item->is_regex() && $this->is_source_regex_invalid( $source ) ) {
				$errors = true;
			} else {
				$redirects[] = $redirect_item;
			}
		}

		fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return $redirects;
	}

	/**
	 * Get redirects from JSON.
	 *
	 * @param string $file_name File path name.
	 * @param bool   $errors Is error?.
	 *
	 * @return array
	 */
	private function get_redirects_from_json( $file_name, &$errors = false ) {
		$redirects_data = file_get_contents( $file_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( empty( $redirects_data ) ) {
			wp_send_json_error();
		}

		$errors    = false;
		$redirects = array();

		$redirects_data = json_decode( $redirects_data, true );

		// Should be a valid json first.
		if ( empty( $redirects_data ) || ! is_array( $redirects_data ) ) {
			return array();
		}

		foreach ( $redirects_data as $redirect ) {
			// Required items.
			if ( ! isset( $redirect['source'], $redirect['type'], $redirect['path'] ) ) {
				continue;
			}

			// Format redirect data.
			$redirect = wp_parse_args(
				$redirect,
				array(
					'title'       => '',
					'source'      => '',
					'path'        => '',
					'destination' => '',
					'type'        => '',
					'options'     => array(),
					'rules'       => array(),
				)
			);

			$redirect_item = $this->utils->create_redirect_item(
				$redirect['source'],
				$redirect['destination'],
				$redirect['type'],
				$redirect['title'],
				$redirect['options'],
				$redirect['rules']
			);
			if ( $redirect_item->is_regex() && $this->is_source_regex_invalid( $redirect['source'] ) ) {
				$errors = true;
			} else {
				$redirects[] = $redirect_item;
			}
		}

		return $redirects;
	}

	/**
	 * Ajax handler to export JSON.
	 *
	 * @return void
	 */
	public function export_redirects() {
		ob_start();

		$redirects = $this->redirects_table->get_redirects();

		if ( ! $redirects ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Please save some redirects first.', 'wds' ),
				)
			);
		}

		$file = array();

		foreach ( $redirects as $redirect ) {
			$file[] = array(
				'title'       => $redirect->get_title(),
				'source'      => $redirect->get_source(),
				'path'        => $redirect->get_path(),
				'destination' => $redirect->get_destination(),
				'type'        => $redirect->get_type(),
				'options'     => $redirect->get_options(),
				'rules'       => $redirect->get_rules(),
			);
		}

		$json = wp_json_encode( $file );

		header( 'Content-disposition: attachment; filename=smartcrawl-redirects-' . time() . '.json' );
		header( 'Content-type: application/json' );

		echo( $json ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		wp_send_json_success(
			array(
				'content' => ob_get_clean(),
			)
		);
	}

	/**
	 * Retrieves HTTP Request data.
	 *
	 * @return array|mixed
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-admin-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}

	/**
	 * Retrieves only allowed hosts.
	 *
	 * @param string $destination Destination.
	 *
	 * @return \Closure
	 */
	private function allowed_hosts_filter( $destination ) {
		return function ( $allowed_hosts ) use ( $destination ) {
			$host = \smartcrawl_get_array_value(
				wp_parse_url( $destination ),
				'host'
			);
			if ( empty( $host ) || ! is_array( $allowed_hosts ) ) {
				return $allowed_hosts;
			}

			return array_unique(
				array_merge(
					$allowed_hosts,
					array( $host )
				)
			);
		};
	}
}