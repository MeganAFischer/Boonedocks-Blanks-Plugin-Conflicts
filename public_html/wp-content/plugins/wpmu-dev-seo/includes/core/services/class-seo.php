<?php
/**
 * Seo class for handling SEO services in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Services;

use SmartCrawl\Logger;
use SmartCrawl\Models\Ignores;
use SmartCrawl\Seo_Report;

/**
 * TODO: Change the name to crawler
 */
class Seo extends Service {

	const ERR_BASE_API_ISSUE = 40;

	const ERR_BASE_CRAWL_RUN = 51;

	const ERR_BASE_COOLDOWN = 52;

	const ERR_BASE_CRAWL_ERR = 53;

	const ERR_BASE_GENERIC = 59;

	const OPTION_COOLDOWN_REMAINING = 'wds-crawl-cooldown';

	/**
	 * Retrieves known verbs.
	 *
	 * @return array The known verbs.
	 */
	public function get_known_verbs() {
		return array( 'start', 'status', 'result', 'sync', 'emails' );
	}

	/**
	 * Checks if the verb is cacheable.
	 *
	 * @param string $verb The verb to check.
	 *
	 * @return bool False as no verb is cacheable.
	 */
	public function is_cacheable_verb( $verb ) {
		return false;
	}

	/**
	 * Retrieves the request URL for the given verb.
	 *
	 * @param string $verb The verb to get the request URL for.
	 *
	 * @return string|false The request URL or false if the verb is empty.
	 */
	public function get_request_url( $verb ) {
		if ( empty( $verb ) ) {
			return false;
		}

		$domain = apply_filters(
			$this->get_filter( 'domain' ),
			network_site_url()
		);
		if ( empty( $domain ) ) {
			return false;
		}

		$query_url = http_build_query(
			array(
				'domain' => $domain,
			)
		);
		$query_url = $query_url && preg_match( '/^\?/', $query_url ) ? $query_url : "?{$query_url}";

		return trailingslashit( $this->get_service_base_url() ) . $verb . $query_url;
	}

	/**
	 * Retrieves the service base URL.
	 *
	 * @return string The service base URL.
	 */
	public function get_service_base_url() {
		$base_url = 'https://wpmudev.com/';

		$api = apply_filters( $this->get_filter( 'api-endpoint' ), 'api' );

		$namespace = apply_filters( $this->get_filter( 'api-namespace' ), 'seo-audit/v1' );

		if ( defined( 'WPMUDEV_CUSTOM_API_SERVER' ) && WPMUDEV_CUSTOM_API_SERVER ) {
			$base_url = trailingslashit( WPMUDEV_CUSTOM_API_SERVER );
		}

		return trailingslashit( $base_url ) . trailingslashit( $api ) . trailingslashit( $namespace );
	}

	/**
	 * Retrieves the request arguments for the given verb.
	 *
	 * @param string $verb The verb to get the request arguments for.
	 *
	 * @return array|false The request arguments or false if the domain or key is empty.
	 */
	public function get_request_arguments( $verb ) {
		$domain = apply_filters(
			$this->get_filter( 'domain' ),
			network_site_url()
		);
		if ( empty( $domain ) ) {
			return false;
		}

		$key = $this->get_dashboard_api_key();
		if ( empty( $key ) ) {
			return false;
		}

		$args = array(
			'method'    => 'GET',
			'timeout'   => $this->get_timeout(),
			'sslverify' => false,
			'headers'   => array(
				'Authorization' => "Basic {$key}",
			),
		);

		if ( 'emails' === $verb ) {
			$args['method'] = 'POST';
			$args['body']   = array(
				'emails' => \SmartCrawl\Admin\Settings\Sitemap::get_email_recipients(),
			);
		}

		if ( 'sync' === $verb ) {
			$ignores        = new Ignores();
			$args['method'] = 'POST';
			$args['body']   = array(
				'ignored_issue_ids' => wp_json_encode( $ignores->get_all() ),
			);
		}

		return $args;
	}

	/**
	 * Local ignores list sync handler
	 *
	 * @return bool Status
	 */
	public function sync_ignores() {
		Logger::debug( 'Start syncing the ignore list' );

		return $this->request( 'sync' );
	}

	/**
	 * Public wrapper for start service method call
	 *
	 * @return mixed Service response hash on success, (bool) on failure
	 */
	public function start() {
		if ( $this->in_progress() ) {
			Logger::debug( 'Crawl already in progress. Doing nothing.' );

			return true; // Already in progress.
		}

		Logger::debug( 'Starting a new crawl' );

		$response = $this->request( 'start' );

		if ( $response ) {
			if ( ! empty( $response['data']['cooldown_remaining'] ) ) {
				update_option( self::OPTION_COOLDOWN_REMAINING, $response['data']['cooldown_remaining'] );
			}

			// Let's check if we're all good here first!
			if ( ! empty( $response['data']['status'] ) && (int) $response['data']['status'] > 399 ) {
				// So we had an error API side that's been handled. We're not progressing anymore.
				// Also, let's preserve previous results.
				$this->stop();
				Logger::debug( 'API-side issues, properly handled API side: ' . $response['data']['status'] );

				return new \WP_Error(
					'crawl-api-issue',
					(string) \smartcrawl_get_array_value( $response, 'message' )
				);
			} else {
				// Also, preserve last crawl time if there isn't one.
				$this->set_last_run_timestamp();

				// So crawl start successfully sent.
				// Clear previous results in anticipation.
				// and mark ourselves as ready to receive status updates.
				$this->clear_result();
				$this->set_progress_flag( true );
				update_option( $this->get_filter( 'seo-service-start' ), true );
				update_option( $this->get_filter( 'seo-service-trigger' ), isset( $_REQUEST['wpmudev-hub'] ) ? 'hub' : 'admin' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				Logger::debug( 'Crawl started' );

				return true;
			}
		} else {
			$this->stop();

			return new \WP_Error(
				'crawl-not-started',
				'Crawl could not be started'
			);
		}
	}

	/**
	 * Retrieves cool down timer.
	 *
	 * @return string|false
	 */
	public function get_cooldown_remaining() {
		$timestamp          = $this->get_last_run_timestamp();
		$cooldown_remaining = get_option( self::OPTION_COOLDOWN_REMAINING, 0 );

		$seconds = $cooldown_remaining - time() + $timestamp + 5;

		if ( $seconds < 0 ) {
			return false;
		}

		$formatted = array();

		$hours = (int) floor( $seconds / 3600 );

		if ( $hours ) {
			$formatted[] = sprintf(
				/* translators: 1: remaining hours, 2: remaining minutes */
				_n(
					'%1$s hour',
					'%1$s hours',
					$hours,
					'wds'
				),
				$hours
			);
		}

		$minutes = (int) ceil( ceil( $seconds / 60 ) % 60 );

		if ( $minutes ) {
			$formatted[] = sprintf(
				/* translators: %s: remaining minutes */
				_n(
					'%s minute',
					'%s minutes',
					$minutes,
					'wds'
				),
				$minutes
			);
		}

		return implode( __( ' and ', 'wds' ), $formatted );
	}

	/**
	 * Checks if the service is started.
	 *
	 * @return bool True if the service is started, false otherwise.
	 */
	public function is_started() {
		return (bool) get_option( $this->get_filter( 'seo-service-start' ), false );
	}

	/**
	 * Checks whether a call is currently being processed
	 *
	 * @return bool
	 */
	public function in_progress() {
		if ( ! $this->is_started() ) {
			return false;
		}

		$flag           = $this->get_progress_flag();
		$update_timeout = HOUR_IN_SECONDS;

		$expected_timeout = intval( $flag ) + $update_timeout;
		if ( ! empty( $flag ) && is_numeric( $flag ) && time() > $expected_timeout ) {
			// Over timeout threshold, clear flag forcefully.
			$this->set_progress_flag( false );
		}

		return (bool) $flag;
	}

	/**
	 * Gets progress flag state
	 *
	 * @return int|bool
	 */
	public function get_progress_flag() {
		return get_option( $this->get_filter( 'seo-progress' ), false );
	}

	/**
	 * Stops expecting response
	 *
	 * @return bool
	 */
	public function stop() {
		delete_option( $this->get_filter( 'seo-service-start' ) );
		$this->set_progress_flag( false );

		return true;
	}

	/**
	 * Sets progress flag state.
	 *
	 * @param bool $flag Whether the service check is in progress.
	 *
	 * @return bool
	 */
	public function set_progress_flag( $flag ) {
		if ( ! empty( $flag ) ) {
			$flag = time();
		}

		return (bool) update_option( $this->get_filter( 'seo-progress' ), $flag );
	}

	/**
	 * Sets service last run time
	 *
	 * Attempts to use embedded result, and falls back
	 * to current timestamp
	 *
	 * @return bool
	 */
	public function set_last_run_timestamp() {
		$raw       = $this->get_result();
		$timestamp = ! empty( $raw['end'] ) ? (int) $raw['end'] : 0;
		if ( empty( $timestamp ) && ! empty( $raw['issues']['previous']['timestamp'] ) ) {
			$timestamp = (int) $raw['issues']['previous']['timestamp'];
		}

		if ( empty( $timestamp ) ) {
			$timestamp = time();
		}

		return (bool) update_option( $this->get_filter( 'seo-service-last_runtime' ), $timestamp );
	}

	/**
	 * Public result getter
	 *
	 * @return mixed result
	 */
	public function get_result() {
		return get_option( $this->get_filter( 'seo-service-result' ), array() );
	}

	/**
	 * Retrieves the report.
	 *
	 * @return Seo_Report
	 */
	public function get_report() {
		// Start with an empty report.
		$report = new Seo_Report();
		if ( ! $this->is_member() ) {
			return $report;
		}

		// Call result first so it can perform cleanup in case of timeout.
		$result = $this->result();
		if ( $this->in_progress() ) {
			$report->set_in_progress( true );
			$report->set_progress(
				empty( $result['percentage'] ) ? 0 : $result['percentage']
			);
			$report->set_start_timestamp(
				empty( $result['start'] ) ? 0 : $result['start']
			);
		} else {
			$report->build( $result );
		}

		return $report;
	}

	/**
	 * Clears the result.
	 *
	 * @return bool True if the result was cleared, false otherwise.
	 */
	private function clear_result() {
		return (bool) delete_option( $this->get_filter( 'seo-service-result' ) );
	}

	/**
	 * Public wrapper for status service method call
	 *
	 * @return mixed Service response hash on success, (bool)false on failure
	 */
	public function status() {
		return $this->result();
	}

	/**
	 * Public wrapper for result service method call
	 *
	 * @return mixed Service response hash on success, (bool)false on failure
	 */
	public function result() {
		$result = $this->get_result();

		if ( $this->in_progress() ) {
			$percentage           = empty( $result['percentage'] )
				? 0
				: $result['percentage'];
			$result['percentage'] = $percentage;
		} elseif ( $this->is_started() && empty( $result['end'] ) ) {
			// Force timeout.
			$result = array(
				'issues' => array(
					'messages' => array(
						__( 'The crawl timed out', 'wds' ),
					),
				),
				'end'    => time(),
			);
			$this->set_result( $result );
			$this->stop();
			$this->set_last_run_timestamp();
			Logger::debug( 'Forced timeout on sitemap crawl' );
		}

		return $result;
	}

	/**
	 * Sets result to new value.
	 *
	 * Sets both cache and permanent result.
	 *
	 * @param array $result Result.
	 *
	 * @return bool
	 */
	public function set_result( $result ) {
		$updated = update_option( $this->get_filter( 'seo-service-result' ), $result );

		if ( $updated ) {
			/**
			 * Action hook to run after crawl result set.
			 *
			 * @since 3.7.0
			 *
			 * @param array $result Crawl result.
			 */
			do_action( 'smartcrawl_sitemap_after_crawl_result_set', $result );
		}

		return $updated;
	}

	/**
	 * Returns last service run time
	 *
	 * Returns either time embedded in results, or the timestamp
	 * from the results service, whichever is greater.
	 *
	 * @return int UNIX timestamp
	 */
	public function get_last_run_timestamp() {
		$recorded = (int) get_option( $this->get_filter( 'seo-service-last_runtime' ), 0 );

		$raw      = $this->get_result();
		$embedded = ! empty( $raw['end'] ) ? (int) $raw['end'] : 0;
		if ( empty( $embedded ) && ! empty( $raw['issues']['previous']['timestamp'] ) ) {
			$embedded = (int) $raw['issues']['previous']['timestamp'];
		}

		return max( $recorded, $embedded );
	}

	/**
	 * Handles the error response.
	 *
	 * @param array  $response The response to handle.
	 * @param string $verb     The verb that caused the error.
	 *
	 * @return bool True if the error was handled, false otherwise.
	 */
	public function handle_error_response( $response, $verb ) {
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( empty( $body ) || empty( $data ) ) {
			$this->set_error_message( __( 'Unspecified error', 'wds' ) );

			return true;
		}

		$msg = '';
		if ( ! empty( $data['message'] ) ) {
			$msg = $data['message'];
		}

		if ( ! empty( $data['data']['manage_link'] ) ) {
			$url = esc_url( $data['data']['manage_link'] );

			$msg .= ' <a href="' . $url . '">' . __( 'Manage', 'wds' ) . '</a>';
		}

		if ( ! empty( $msg ) ) {
			$this->set_error_message( $msg );
		}

		return true;
	}

	/**
	 * Finish crawl.
	 *
	 * @return void
	 */
	public function after_done() {
		$this->request( 'emails' );

		// Get crawl data.
		$result = $this->get_result();
		// Get trigger source.
		$trigger = get_option( $this->get_filter( 'seo-service-trigger' ), 'admin' );
		if ( ! empty( $trigger ) ) {
			$trigger = delete_option( $this->get_filter( 'seo-service-trigger' ) );
		}

		/**
		 * Action hook to run after crawl completed.
		 *
		 * @since 3.7.0
		 *
		 * @param array  $result  Crawl result.
		 * @param string $trigger Trigger source.
		 */
		do_action( 'smartcrawl_sitemap_after_crawl_done', $result, $trigger );
	}
}