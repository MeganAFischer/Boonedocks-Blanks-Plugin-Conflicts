<?php
/**
 * Lighthouse service class.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Services;

use SmartCrawl\Logger;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Lighthouse\Options;
use SmartCrawl\Lighthouse\Report;

/**
 * Lighthouse class
 */
class Lighthouse extends Service {

	const OPTION_ID_START_TIME = 'wds-lighthouse-seo-start-timestamp';

	const OPTION_ID_LAST_REPORT = 'wds-lighthouse-seo-last-report';

	const VERB_SEO_CHECK = 'site/seo-check';

	const VERB_SEO_RESULT = 'site/seo-result/latest';

	/**
	 * Get Hub's base url.
	 *
	 * @return string
	 */
	public function get_service_base_url() {
		$base_url = 'https://wpmudev.com/';
		if ( defined( 'WPMUDEV_CUSTOM_API_SERVER' ) && WPMUDEV_CUSTOM_API_SERVER ) {
			$base_url = trailingslashit( WPMUDEV_CUSTOM_API_SERVER );
		}

		$api = apply_filters( $this->get_filter( 'api-endpoint' ), 'api' );

		$namespace = apply_filters( $this->get_filter( 'api-namespace' ), 'performance/v2' );

		return trailingslashit( $base_url ) . trailingslashit( $api ) . trailingslashit( $namespace );
	}

	/**
	 * Get know verbs which are used to send remote request.
	 *
	 * @return array
	 */
	public function get_known_verbs() {
		return array( self::VERB_SEO_CHECK, self::VERB_SEO_RESULT );
	}

	/**
	 * Check if this verb is cacheable.
	 *
	 * @param string $verb Verb for remote request.
	 *
	 * @return false
	 */
	public function is_cacheable_verb( $verb ) {
		return false;
	}

	/**
	 * Retrieve request url.
	 *
	 * @param string $verb Verb for remote request.
	 *
	 * @return false|string
	 */
	public function get_request_url( $verb ) {
		if ( empty( $verb ) ) {
			return false;
		}

		$domain = apply_filters(
			$this->get_filter( 'domain' ),
			site_url()
		);
		if ( empty( $domain ) ) {
			return false;
		}

		$query_url = http_build_query(
			array(
				'domain' => $domain,
			)
		);
		$query_url = $query_url && preg_match( '/^\?/', $query_url ) ? $query_url : "?$query_url";

		return trailingslashit( $this->get_service_base_url() ) . $verb . $query_url;
	}

	/**
	 * Retrieve request arguments.
	 *
	 * @param string $verb Verb for remote request.
	 *
	 * @return array
	 */
	public function get_request_arguments( $verb ) {
		$args = array();

		if ( self::VERB_SEO_CHECK === $verb ) {
			$args = array(
				'method'    => 'POST',
				'blocking'  => false,
				'sslverify' => false,
				'timeout'   => 1,
			);
		}

		if ( self::VERB_SEO_RESULT === $verb ) {
			$args = array(
				'method'    => 'GET',
				'timeout'   => $this->get_timeout(),
				'sslverify' => false,
			);
		}

		$key = $this->get_dashboard_api_key();
		if ( $key ) {
			$args['headers']['Authorization'] = "Basic $key";
		}

		return apply_filters( $this->get_filter( 'lighthouse-args' ), $args, $verb );
	}

	/**
	 * Handle error response.
	 *
	 * @param int|string $response The response code as an integer. Empty string if incorrect parameter given.
	 * @param string     $verb Verb for remote request.
	 *
	 * @return false
	 */
	public function handle_error_response( $response, $verb ) {
		return false;
	}

	/**
	 * Get SEO report started time as timestamp.
	 *
	 * @return int
	 */
	public function get_start_time() {
		return get_option( self::OPTION_ID_START_TIME, false );
	}

	/**
	 * Start the SEO testing.
	 */
	public function start() {
		update_option(
			self::OPTION_ID_START_TIME,
			current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			false
		);

		$this->request( self::VERB_SEO_CHECK );
	}

	/**
	 * Stop the SEO testing.
	 *
	 * @return void
	 */
	public function stop() {
		delete_option( self::OPTION_ID_START_TIME );
	}

	/**
	 * Set error when SEO testing is failed.
	 *
	 * @param string $code Error code as string.
	 * @param string $message Error message.
	 *
	 * @return bool
	 */
	public function set_error( $code, $message ) {
		return update_option(
			self::OPTION_ID_LAST_REPORT,
			array(
				'error'   => true,
				'code'    => $code,
				'message' => $message,
			),
			false
		);
	}

	/**
	 * Refresh SEO report.
	 *
	 * @return bool
	 */
	public function refresh_report() {
		$results = $this->request( self::VERB_SEO_RESULT );

		if ( ! $results ) {
			$this->set_error(
				'network-error',
				esc_html__( 'We were unable to connect to the API server.', 'wds' )
			);

			return false;
		}

		update_option( self::OPTION_ID_LAST_REPORT, $results, false );

		return true;
	}

	/**
	 * Get last report.
	 *
	 * @param string $device Device.
	 *
	 * @return Report
	 */
	public function get_last_report( $device = 'desktop' ) {
		$report      = new Report( $device );
		$last_report = get_option( self::OPTION_ID_LAST_REPORT, false );
		if ( empty( $last_report ) ) {
			return $report;
		}

		if ( ! empty( $last_report['error'] ) ) {
			$report->set_error(
				\smartcrawl_get_array_value( $last_report, 'code' ),
				\smartcrawl_get_array_value( $last_report, 'message' ),
				\smartcrawl_get_array_value( $last_report, 'data' )
			);

			return $report;
		}

		$device_report = \smartcrawl_get_array_value( $last_report, array( 'data', $device ) );
		if ( ! $device_report ) {
			$report->set_error(
				'unexpected-error',
				esc_html__( 'An unexpected error occurred', 'wds' )
			);

			return $report;
		}

		$time = \smartcrawl_get_array_value( $last_report, array( 'data', 'time' ) );
		$report->set_timestamp( $time );
		$report->populate( $device_report );

		return $report;
	}

	/**
	 * Check requirements and send emails if condition is meet.
	 */
	public function maybe_send_emails() {
		if ( ! $this->is_member() || ! Options::is_cron_enabled() ) {
			return;
		}

		$desktop_report = $this->get_last_report();
		if ( ! $desktop_report->has_data() || $desktop_report->has_errors() ) {
			Logger::debug( 'Not sending Lighthouse emails because a valid report is not available.' );

			return;
		}

		if ( ! $desktop_report->is_fresh() ) {
			Logger::debug( 'Not sending Lighthouse emails because the latest report is not fresh.' );

			return;
		}

		$reporting_condition = Options::reporting_condition();
		$mobile_report       = $this->get_last_report( 'mobile' );
		if (
			Options::reporting_condition_enabled()
			&& $reporting_condition
		) {
			$reporting_device            = Options::reporting_device();
			$score_higher_than_condition = true;

			if ( 'both' === $reporting_device || 'desktop' === $reporting_device ) {
				$score_higher_than_condition = $desktop_report->get_score() >= $reporting_condition;
			}

			if ( 'both' === $reporting_device || 'mobile' === $reporting_device ) {
				$score_higher_than_condition = $score_higher_than_condition && $mobile_report->get_score() >= $reporting_condition;
			}

			if ( $score_higher_than_condition ) {
				Logger::debug( 'Not sending Lighthouse emails because the required score condition is not met.' );

				return;
			}
		}

		Logger::debug( 'Sending Lighthouse emails.' );
		$this->send_emails();
	}

	/**
	 * Handler to send email.
	 */
	private function send_emails() {
		$recipients     = Options::email_recipients();
		$desktop_report = $this->get_last_report();
		$mobile_report  = $this->get_last_report( 'mobile' );

		foreach ( $recipients as $recipient ) {
			$recipient_name   = \smartcrawl_get_array_value( $recipient, 'name' );
			$recipient_email  = \smartcrawl_get_array_value( $recipient, 'email' );
			$reporting_device = Options::reporting_device();
			if ( 'desktop' === $reporting_device || 'mobile' === $reporting_device ) {
				$subject = sprintf(
					/* translators: 1: Site url, 2: Score */
					esc_html__( 'SEO Report for %1$s - Score %2$s', 'wds' ),
					site_url(),
					'desktop' === $reporting_device
						? $desktop_report->get_score()
						: $mobile_report->get_score()
				);
			} else {
				$subject = sprintf(
					/* translators: 1: Site url, 2: Score */
					esc_html__( 'SEO Report for %1$s - Desktop score %2$s / Mobile score %3$s', 'wds' ),
					site_url(),
					$desktop_report->get_score(),
					$mobile_report->get_score()
				);
			}
			$email_content  = Simple_Renderer::load(
				'emails/email-body',
				array(
					'email_template'      => 'emails/lighthouse-email',
					'email_template_args' => array(
						'desktop_report' => $desktop_report,
						'mobile_report'  => $mobile_report,
						'username'       => $recipient_name,
						'device'         => $reporting_device,
					),
				)
			);
			$email_content  = stripslashes( $email_content );
			$no_reply_email = 'noreply@' . wp_parse_url( get_site_url(), PHP_URL_HOST );
			$headers        = array(
				sprintf(
					/* translators: %s: plugin title */
					'From: %s <' . $no_reply_email . '>',
					\smartcrawl_get_plugin_title()
				),
				'Content-Type: text/html; charset=UTF-8',
			);

			wp_mail( $recipient_email, $subject, $email_content, $headers );
		}
	}

	/**
	 * Clear last report.
	 *
	 * @return bool
	 */
	public function clear_last_report() {
		return delete_option( self::OPTION_ID_LAST_REPORT );
	}
}