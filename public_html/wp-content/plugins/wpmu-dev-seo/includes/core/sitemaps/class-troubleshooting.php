<?php
/**
 * Troubleshooting class for handling sitemap-related troubleshooting in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

use SmartCrawl\Models\User;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Services\Service;
use SmartCrawl\Controllers;
use SmartCrawl\Admin\Settings\Admin_Settings;

/**
 * Class Troubleshooting
 *
 * Handles various troubleshooting tasks for sitemaps.
 */
class Troubleshooting extends Controllers\Controller {

	use Singleton;

	const SITEMAP_INVALID              = 0;
	const SITEMAP_VALID                = 1;
	const SITEMAP_FOREIGN              = 2;
	const SITEMAP_PHYSICAL             = 3;
	const SITEMAP_FAULTY_PERMALINK     = 4;
	const PERMALINKS_SETTING_PLAIN     = 5;
	const SITEMAP_HAS_WHITESPACE       = 6;
	const SITEMAP_UNAUTHORIZED_REQUEST = 7;
	const SITEMAP_REQUEST_ERROR        = 8;
	const EVENT_HOOK                   = 'wds_sitemap_validity_check';
	const ERRORS_FOUND_OPTION_ID       = 'wds_sitemap_errors_found';

	/**
	 * Sub sitemap URL.
	 *
	 * @var string
	 */
	private $sub_sitemap_url;

	/**
	 * WP Error object.
	 *
	 * @var \WP_Error|null
	 */
	private $wp_error = null;

	/**
	 * Determines if the troubleshooting should run.
	 *
	 * @return bool
	 */
	public function should_run() {
		return Settings::get_setting( 'sitemap' )
			&& Admin_Settings::is_tab_allowed( Settings::TAB_SITEMAP );
	}

	/**
	 * Initializes the troubleshooting process.
	 *
	 * @return void
	 */
	protected function init() {
		add_action(
			'wp_ajax_wds_troubleshoot_sitemap',
			array(
				$this,
				'troubleshoot_sitemap',
			)
		);
		add_action(
			'wp_ajax_wds_recheck_sitemaps',
			array(
				$this,
				'recheck_sitemaps',
			)
		);
		add_action( 'init', array( $this, 'schedule_cron' ) );
		add_action(
			self::EVENT_HOOK,
			array(
				$this,
				'do_sitemap_validity_check_cron',
			)
		);
		add_action( 'all_admin_notices', array( $this, 'show_notice' ) );
	}

	/**
	 * Stops the troubleshooting process.
	 *
	 * @return bool
	 */
	public function stop() {
		wp_clear_scheduled_hook( self::EVENT_HOOK );

		return parent::stop();
	}

	/**
	 * Terminates the troubleshooting process.
	 *
	 * @return void
	 */
	protected function terminate() {
		remove_action(
			'wp_ajax_wds_troubleshoot_sitemap',
			array(
				$this,
				'troubleshoot_sitemap',
			)
		);
		remove_action(
			'wp_ajax_wds_recheck_sitemaps',
			array(
				$this,
				'recheck_sitemaps',
			)
		);
		remove_action( 'init', array( $this, 'schedule_cron' ) );

		remove_action(
			self::EVENT_HOOK,
			array(
				$this,
				'do_sitemap_validity_check_cron',
			)
		);
		remove_action( 'all_admin_notices', array( $this, 'show_notice' ) );
	}

	/**
	 * Shows a notice related to the troubleshooting process.
	 *
	 * @return void
	 */
	public function show_notice() {
		$key                  = self::ERRORS_FOUND_OPTION_ID;
		$dismissed_messages   = get_user_meta( get_current_user_id(), 'wds_dismissed_messages', true );
		$is_message_dismissed = \smartcrawl_get_array_value( $dismissed_messages, $key ) === true;
		$errors_found         = get_option( $key );
		if (
			$is_message_dismissed
			|| ! current_user_can( 'manage_options' )
			|| ! $errors_found
		) {
			return;
		}

		$message = sprintf(
			/* translators: 1: User display name, 2,3: strong tag, 4: plugin title */
			esc_html__( 'Hey, %1$s! A problem on your site is preventing sitemaps from functioning properly. Identify and resolve any issues with %2$s%4$s%3$s’s Sitemap Troubleshooting feature.', 'wds' ),
			User::current()->get_display_name(),
			'<strong>',
			'</strong>',
			\smartcrawl_get_plugin_title()
		);
		$action_url = Admin_Settings::admin_url( Settings::TAB_SITEMAP ) . '&tab=tab_settings#wds-troubleshooting-sitemap-placeholder';

		\smartcrawl_print_admin_notice( $key, false, $message, $action_url, esc_html__( 'Troubleshoot Sitemap', 'wds' ) );
	}

	/**
	 * Schedules a cron job for sitemap validity check.
	 *
	 * @return void
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( self::EVENT_HOOK ) ) {
			wp_schedule_event( time(), 'twicedaily', self::EVENT_HOOK );
		}
	}

	/**
	 * Executes the sitemap validity check cron job.
	 *
	 * @return void
	 */
	public function do_sitemap_validity_check_cron() {
		$result = $this->check_all_sitemaps();
		$status = (int) \smartcrawl_get_array_value( $result, 'status' );
		if ( self::SITEMAP_VALID === $status ) {
			$this->clear_errors_found_option();
		} else {
			update_option( self::ERRORS_FOUND_OPTION_ID, 1 );
		}
	}

	/**
	 * Troubleshoots the sitemap.
	 *
	 * @return void
	 */
	public function troubleshoot_sitemap() {
		$data = $this->get_request_data();
		if ( empty( $data ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Session expired. Please reload the page and try again.', 'wds' ),
				)
			);
		}

		// Check all sitemaps.
		$result = $this->check_all_sitemaps();
		$status = (int) \smartcrawl_get_array_value( $result, 'status' );

		// Let's do whatever we can to make the sitemap work.
		Cache::get()->invalidate();
		flush_rewrite_rules();

		// Prime the caches but don't block the current thread.
		$this->prime_all_sitemap_caches();

		$this->increase_troubleshoot_count();

		wp_send_json_success(
			array(
				'status' => $status,
			)
		);
	}

	/**
	 * Rechecks the sitemaps.
	 *
	 * @return bool|null
	 */
	public function recheck_sitemaps() {
		$data = $this->get_request_data();
		if ( empty( $data ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Session expired. Please reload the page and try again.', 'wds' ),
				)
			);
		}

		$status      = ! isset( $data['status'] ) ? self::SITEMAP_VALID : (int) $data['status'];
		$sitemap_url = empty( $data['sitemap'] ) ? '' : $data['sitemap'];

		// Let's check again, did we make a difference?
		$recheck_result    = $this->check_all_sitemaps();
		$recheck_status    = (int) \smartcrawl_get_array_value( $recheck_result, 'status' );
		$rechecked_sitemap = (string) \smartcrawl_get_array_value( $recheck_result, 'sitemap', $sitemap_url );

		if ( self::SITEMAP_VALID === $recheck_status ) {
			$this->clear_errors_found_option();

			if ( self::SITEMAP_VALID === $status ) {
				// There was no error, there is no error, hurray!
				return $this->send_response( true );
			} else {
				// We fixed something without user intervention, hurray!
				list( $fixed_issue, , $fixed_message ) = $this->get_issue_details( $status );

				return $this->send_response(
					true,
					$fixed_issue,
					$this->include_sitemap_name( $fixed_message, $rechecked_sitemap )
				);
			}
		}

		// Our fix didn't make any difference. Let's ask the user to intervene.
		list( $issue, $message, , $action_text, $action_url ) = $this->get_issue_details( $recheck_status );

		return $this->send_response(
			false,
			$issue,
			$this->include_sitemap_name( $message, $rechecked_sitemap ),
			$action_text,
			$action_url
		);
	}

	/**
	 * Sends a response after troubleshooting.
	 *
	 * @param bool   $fixed       Whether the issue was fixed.
	 * @param string $issue       The issue encountered.
	 * @param string $message     The message to send.
	 * @param string $action_text The action text.
	 * @param string $action_url  The action URL.
	 *
	 * @return bool
	 */
	private function send_response( $fixed, $issue = '', $message = '', $action_text = '', $action_url = '' ) {
		$data = array(
			'fixed'       => $fixed,
			'issue'       => $issue,
			'message'     => $message,
			'action_text' => $action_text,
			'action_url'  => $action_url,
		);

		do_action( 'smartcrawl_before_recheck_sitemaps', $data );

		wp_send_json_success( $data );

		return true;
	}

	/**
	 * Checks if the server is Nginx.
	 *
	 * @return bool
	 */
	private function is_nginx_server() {
		$server_software = \smartcrawl_get_array_value( $_SERVER, 'SERVER_SOFTWARE' );
		if ( empty( $server_software ) || ! is_array( $server_software ) ) {
			return false;
		}

		return in_array( 'nginx', array_map( 'strtolower', $server_software ), true );
	}

	/**
	 * Gets the issue details based on status.
	 *
	 * @param int $status The status code.
	 *
	 * @return array
	 */
	private function get_issue_details( $status ) {
		$service     = Service::get( Service::SERVICE_SITE );
		$support_url = $service->is_member()
			? 'https://wpmudev.com/hub2/support'
			: 'https://wordpress.org/support/plugin/smartcrawl-seo/';

		$wp_error_message = $this->wp_error ? $this->wp_error->get_error_message() : '';
		$request_error    = $wp_error_message
			/* translators: %s: Error message */
			? __( 'Our troubleshooter was not able to access your sitemap for testing. We received the following error: %s', 'wds' )
			: __( 'Our troubleshooter was not able to access your sitemap for testing.', 'wds' );
		$request_error = sprintf( $request_error, "<br/><code>$wp_error_message</code>" );

		switch ( $status ) {
			case self::SITEMAP_VALID:
				return array();

			case self::SITEMAP_FOREIGN:
				return array(
					__( 'Plugin Conflict', 'wds' ),
					sprintf(
						/* translators: 1,2: strong tag, 3: plugin title */
						__( 'You have another sitemap plugin conflicting with %1$s%3$s%2$s\'s sitemap###SITEMAP_URL###. Please deactivate the conflicting plugin and try again.', 'wds' ),
						'<strong>',
						'</strong>',
						\smartcrawl_get_plugin_title()
					),
					'',
					__( 'Go to the Plugins Screen', 'wds' ),
					admin_url( 'plugins.php' ),
				);

			case self::SITEMAP_PHYSICAL:
				return array(
					__( 'File Conflict', 'wds' ),
					sprintf(
						/* translators: 1,2: strong tag, 3: plugin title */
						esc_html__( 'You have a physical file named###SITEMAP_URL### on your server that is conflicting with %1$s%3$s%2$s. Please delete the file and try again.', 'wds' ),
						'<strong>',
						'</strong>',
						esc_html( \smartcrawl_get_plugin_title() )
					),
					'',
					'',
					'',
				);

			case self::SITEMAP_FAULTY_PERMALINK:
				return array(
					__( 'Permalink Problem', 'wds' ),
					$this->is_nginx_server()
						? __( "Pretty permalinks are not working for your sitemap###SITEMAP_URL###. Since you are hosting your website on an Nginx server, you may have to manually include some rewrite rules to your server's configuration files. Check our documentation for details on how to fix this issue.", 'wds' )
						: sprintf(
								/* translators: 1,2: strong tag, 3: plugin title */
							__( 'Pretty permalinks are not working for your sitemap###SITEMAP_URL###. You may have to manually include some rewrite rules to your server\'s configuration files. Visit %1$s%3$s%2$s\'s documentation for details on how to fix this issue.', 'wds' ),
							'<strong>',
							'</strong>',
							\smartcrawl_get_plugin_title()
						),
					__( 'Pretty permalinks were not working for your sitemap. Flushing the rewrite rules fixed the issue.', 'wds' ),
					__( 'Visit Documentation', 'wds' ),
					'https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/#additional-troubleshooting-options-sitemap',
				);

			case self::PERMALINKS_SETTING_PLAIN:
				return array(
					__( 'Incorrect Permalink Settings', 'wds' ),
					sprintf(
						/* translators: 1,2: strong tag, 3: plugin title */
						__( 'You are using <code>plain</code> permalinks on this site. Change your permalink structure to anything else for the %1$s%3$s%2$s sitemap to work.', 'wds' ),
						'<strong>',
						'</strong>',
						\smartcrawl_get_plugin_title()
					),
					'',
					__( 'Go to Permalink Settings', 'wds' ),
					admin_url( 'options-permalink.php' ),
				);

			case self::SITEMAP_HAS_WHITESPACE:
				return array(
					__( 'Unwanted Whitespace Character', 'wds' ),
					__( 'Your XML sitemap is invalid because it has an empty whitespace at the beginning. The cause most often is an empty line at the beginning (before the <?php line) or end of the <code>wp-config.php</code> or <code>functions.php</code> file. If there is no empty line or space in these files, we highly recommend running a conflict check to identify what outputs the empty whitespace.', 'wds' ),
					'',
					'',
					'',
				);

			case self::SITEMAP_UNAUTHORIZED_REQUEST:
				return array(
					__( 'Authorization Error', 'wds' ),
					__( "Our troubleshooter was not able to access your sitemap for testing. Your server's security software might be blocking requests sent by the troubleshooter. If this is the case, whitelisting your server's IP might resolve the issue.", 'wds' ),
					'',
					'',
					'',
				);

			case self::SITEMAP_REQUEST_ERROR:
				return array(
					__( 'Request Failed', 'wds' ),
					$request_error,
					'',
					'',
					'',
				);

			default:
			case self::SITEMAP_INVALID:
				return array(
					__( 'Unknown Error', 'wds' ),
					__( "We found an issue with your sitemap###SITEMAP_URL###, but unfortunately, we couldn't fix it. Please contact our support.", 'wds' ),
					__( 'We found an unknown issue with your sitemap###SITEMAP_URL###, but clearing the cache seems to have fixed it.', 'wds' ),
					__( 'Contact Support', 'wds' ),
					$support_url,
				);
		}
	}

	/**
	 * Includes the sitemap name in the message.
	 *
	 * @param string $message      The message.
	 * @param string $sitemap_name The sitemap name.
	 *
	 * @return string
	 */
	private function include_sitemap_name( $message, $sitemap_name ) {
		return str_replace( '###SITEMAP_URL###', empty( $sitemap_name ) ? " <code>$sitemap_name</code>" : '', $message );
	}

	/**
	 * Gets the sitemap XML from the response.
	 *
	 * @param mixed $response The response.
	 *
	 * @return string
	 */
	private function get_sitemap_xml( $response ) {
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Gets the sitemap response.
	 *
	 * @param string $sitemap_url The sitemap URL.
	 *
	 * @return mixed
	 */
	private function get_sitemap_response( $sitemap_url ) {
		return wp_remote_get(
			$sitemap_url,
			array(
				'timeout' => 300,
			)
		);
	}

	/**
	 * Checks if the XML has whitespace.
	 *
	 * @param string $sitemap_xml The sitemap XML.
	 *
	 * @return bool
	 */
	private function xml_has_whitespace( $sitemap_xml ) {
		return $this->is_xml_valid( trim( $sitemap_xml ) );
	}

	/**
	 * Checks if the XML is valid.
	 *
	 * @param string $sitemap_xml The sitemap XML.
	 *
	 * @return bool
	 */
	private function is_xml_valid( $sitemap_xml ) {
		return \SmartCrawl\String_Utils::starts_with( $sitemap_xml, '<?xml' );
	}

	/**
	 * Primes all sitemap caches.
	 *
	 * @return void
	 */
	private function prime_all_sitemap_caches() {
		$sitemap_urls = $this->get_sitemap_urls();

		foreach ( $sitemap_urls as $sitemap_url ) {
			wp_remote_get(
				$sitemap_url['pretty'],
				array(
					'blocking' => false,
					'timeout'  => 1,
				)
			);
		}
	}

	/**
	 * Checks all sitemaps.
	 *
	 * @return array|int[]
	 */
	private function check_all_sitemaps() {
		return $this->check_sitemaps( $this->get_sitemap_urls() );
	}

	/**
	 * Gets the sitemap URLs.
	 *
	 * @return array[]
	 */
	private function get_sitemap_urls() {
		$sitemap_urls = array(
			array(
				'pretty' => \smartcrawl_get_sitemap_url(),
				'plain'  => \smartcrawl_get_plain_sitemap_url(),
			),
			array(
				'pretty' => home_url( 'post-sitemap1.xml' ),
				'plain'  => \smartcrawl_get_plain_sitemap_url( 'post' ),
			),
		);

		$news_sitemap_enabled = Utils::get_sitemap_option( 'enable-news-sitemap' );
		if ( $news_sitemap_enabled ) {
			$sitemap_urls[] = array(
				'pretty' => \smartcrawl_get_news_sitemap_url(),
				'plain'  => \smartcrawl_get_plain_news_sitemap_url(),
			);
		}

		return $sitemap_urls;
	}

	/**
	 * Checks the sitemaps.
	 *
	 * @param array $sitemaps The sitemaps.
	 *
	 * @return array|int[]
	 */
	private function check_sitemaps( $sitemaps ) {
		foreach ( $sitemaps as $sitemap_urls ) {
			$pretty_permalink = $sitemap_urls['pretty'];
			$plain_permalink  = $sitemap_urls['plain'];

			$sitemap_name = $this->get_sitemap_name( $pretty_permalink );

			$status = $this->check_sitemap(
				$pretty_permalink,
				$plain_permalink
			);
			if ( self::SITEMAP_VALID !== $status ) {
				return array(
					'sitemap' => $sitemap_name,
					'status'  => $status,
				);
			}
		}

		return array(
			'status' => self::SITEMAP_VALID,
		);
	}

	/**
	 * Validates the sitemap response.
	 *
	 * @param mixed $sitemap_response The sitemap response.
	 *
	 * @return bool|int
	 */
	private function validate_sitemap_response( $sitemap_response ) {
		if ( empty( $sitemap_response ) ) {
			return self::SITEMAP_INVALID;
		}

		if ( is_wp_error( $sitemap_response ) ) {
			$this->wp_error = $sitemap_response;

			return self::SITEMAP_REQUEST_ERROR;
		}

		if ( $this->is_unauthorized_response( $sitemap_response ) ) {
			return self::SITEMAP_UNAUTHORIZED_REQUEST;
		}

		if (
			wp_remote_retrieve_response_code( $sitemap_response ) !== 200
			|| empty( wp_remote_retrieve_body( $sitemap_response ) )
		) {
			return self::SITEMAP_INVALID;
		}

		return true;
	}

	/**
	 * Checks the sitemap.
	 *
	 * @param string $pretty_url The pretty URL.
	 * @param string $plain_url  The plain URL.
	 *
	 * @return bool|int
	 */
	private function check_sitemap( $pretty_url, $plain_url ) {
		$sitemap_name = $this->get_sitemap_name( $pretty_url );
		if ( $this->physical_sitemap_file_exists( $sitemap_name ) ) {
			return self::SITEMAP_PHYSICAL;
		}

		$sitemap_response          = $this->get_sitemap_response( $pretty_url );
		$is_valid_sitemap_response = $this->validate_sitemap_response( $sitemap_response );
		if ( true !== $is_valid_sitemap_response ) {
			return $is_valid_sitemap_response;
		}

		$sitemap_xml = $this->get_sitemap_xml( $sitemap_response );
		$xml_valid   = $this->is_xml_valid( $sitemap_xml );
		if ( $xml_valid ) {
			return $this->is_foreign_sitemap( $sitemap_xml )
				? self::SITEMAP_FOREIGN
				: self::SITEMAP_VALID;
		} elseif ( $this->xml_has_whitespace( $sitemap_xml ) ) {
			return self::SITEMAP_HAS_WHITESPACE;
		} else {
			$sitemap_plain_response  = $this->get_sitemap_response( $plain_url );
			$is_valid_plain_response = $this->validate_sitemap_response( $sitemap_plain_response );
			if ( true !== $is_valid_plain_response ) {
				return $is_valid_plain_response;
			}

			$sitemap_plain_xml = $this->get_sitemap_xml( $sitemap_plain_response );
			$plain_xml_valid   = $this->is_xml_valid( $sitemap_plain_xml );
			if ( $plain_xml_valid ) {
				if ( empty( get_option( 'permalink_structure' ) ) ) {
					return self::PERMALINKS_SETTING_PLAIN;
				} else {
					return self::SITEMAP_FAULTY_PERMALINK;
				}
			} else {
				return self::SITEMAP_INVALID;
			}
		}
	}

	/**
	 * Checks if the response is unauthorized.
	 *
	 * @param mixed $response The response.
	 *
	 * @return bool
	 */
	private function is_unauthorized_response( $response ) {
		$response_code = wp_remote_retrieve_response_code( $response );

		return in_array( $response_code, array( 401, 403 ), true );
	}

	/**
	 * Gets the sitemap name from the URL.
	 *
	 * @param string $sitemap_url The sitemap URL.
	 *
	 * @return mixed|string|null
	 */
	private function get_sitemap_name( $sitemap_url ) {
		$url_parts = explode( '/', $sitemap_url );

		return array_pop( $url_parts );
	}

	/**
	 * Checks if the physical sitemap file exists.
	 *
	 * @param string $sitemap_name The sitemap name.
	 *
	 * @return bool
	 */
	private function physical_sitemap_file_exists( $sitemap_name ) {
		return file_exists( trailingslashit( ABSPATH ) . $sitemap_name );
	}

	/**
	 * Checks if the sitemap is foreign.
	 *
	 * @param string $sitemap_xml The sitemap XML.
	 *
	 * @return bool
	 */
	private function is_foreign_sitemap( $sitemap_xml ) {
		return strpos( $sitemap_xml, Utils::SITEMAP_VERIFICATION_TOKEN ) === false;
	}

	/**
	 * Gets the request data.
	 *
	 * @return array|mixed
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}

	/**
	 * Clears the errors found option.
	 *
	 * @return void
	 */
	private function clear_errors_found_option() {
		delete_option( self::ERRORS_FOUND_OPTION_ID );
	}

	/**
	 * Increase troubleshoot count.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	private function increase_troubleshoot_count() {
		$troubleshoot_count = Utils::get_sitemap_option( 'troubleshoot-count' );

		if ( empty( $troubleshoot_count ) ) {
			$troubleshoot_count = 0;
		}

		Utils::set_sitemap_option( 'troubleshoot-count', intval( $troubleshoot_count ) + 1 );
	}
}