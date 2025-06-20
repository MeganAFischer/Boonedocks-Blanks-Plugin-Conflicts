<?php
/**
 * Class to handle mixpanel sitemap events functionality.
 *
 * @since   3.7.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Mixpanel;

use SmartCrawl\Singleton;
use SmartCrawl\Settings;
use SmartCrawl\Sitemaps\Utils;
use SmartCrawl\Seo_Report;

/**
 * Class Sitemap.
 */
class Sitemap extends Events {

	use Singleton;

	/**
	 * Initialize class.
	 *
	 * @since 3.7.0
	 */
	protected function init() {
		add_action( 'update_option_wds_sitemap_options', array( $this, 'intercept_settings_update' ) );
		add_action( 'update_option_wds_sitemap_options', array( $this, 'intercept_crawler_report' ), 10, 2 );
		add_action( 'update_option_wds_sitemap_options', array( $this, 'intercept_auto_regeneration' ) );
		add_action( 'smartcrawl_before_recheck_sitemaps', array( $this, 'intercept_sitemap_troubleshoot' ) );
		add_action( 'smartcrawl_sitemap_after_crawl_done', array( $this, 'intercept_seo_crawl_result' ), 10, 2 );
		add_action( 'update_option_wds_sitemap_options', array( $this, 'intercept_sitemap_switch' ), 10, 2 );
		add_action( 'update_option_wds_sitemap_options', array( $this, 'intercept_news_sitemap' ), 10, 2 );
	}

	/**
	 * Handle sitemap settings update.
	 *
	 * @since 3.7.0
	 *
	 * @param array $old_value The old option value.
	 *
	 * @return void
	 */
	public function intercept_settings_update( $old_value ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		// Latest values (avoid using new values from hook).
		$new_value = Settings::get_component_options( Settings::COMP_SITEMAP );

		$track = false;

		foreach (
			array(
				'items-per-sitemap',
				'ping-google',
			)
			as $field
		) {
			$old = $this->get_value( $field, $old_value );
			$new = $this->get_value( $field, $new_value );
			if ( $old !== $new ) {
				$track = true;
				break;
			}
		}

		if ( $track ) {
			$ping_google = $this->get_value( 'ping-google', $new_value );

			$this->tracker()->track(
				'SMA - Sitemap Settings',
				array(
					'sitemap_structure_links' => $this->get_value( 'items-per-sitemap', $new_value, 0 ),
					'notify_search_engines'   => $ping_google ? 'Automatic' : 'Manual',
				)
			);
		}
	}

	/**
	 * Handle sitemap crawler report update.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $new_value The new option value.
	 *
	 * @return void
	 *
	 * @since 3.7.0
	 */
	public function intercept_crawler_report( $old_value, $new_value ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		if ( ! $old_value['crawler-cron-enable'] && ! $new_value['crawler-cron-enable'] ) {
			return false;
		}

		$old_fields = array();
		$new_fields = array();

		foreach (
			array(
				'crawler-cron-enable',
				'crawler-frequency',
				'crawler-tod',
				'crawler-dow',
			)
			as $field
		) {
			$old_fields[ $field ] = $this->get_value( $field, $old_value );
			$new_fields[ $field ] = $this->get_value( $field, $new_value );
		}

		// If crawler settings not changed, don't continue.
		if ( $old_fields === $new_fields ) {
			return;
		}

		if ( ! empty( $new_fields['crawler-frequency'] ) && ! empty( $new_fields['crawler-cron-enable'] ) ) {
			$frequency_mapping = array(
				'daily'   => 'Daily',
				'weekly'  => 'Weekly',
				'monthly' => 'Monthly',
			);

			$frequency = $new_fields['crawler-frequency'];

			// We need a valid frequency value.
			if ( ! isset( $frequency_mapping[ $frequency ] ) ) {
				return;
			}

			$properties = array(
				'automatic_crawls' => 'Enabled',
				'schedule_type'    => $frequency_mapping[ $frequency ],
			);

			switch ( $frequency ) {
				case 'daily':
					$tod           = $new_fields['crawler-tod'];
					$tod_formatted = date_i18n( get_option( 'time_format' ), strtotime( 'today' ) + ( $tod * HOUR_IN_SECONDS ) );

					$properties['schedule'] = $tod_formatted;

					break;
				case 'weekly':
					$dow        = $new_fields['crawler-dow'];
					$day_number = date( 'w', strtotime( 'this Monday' ) + ( $dow * DAY_IN_SECONDS ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					$week_days  = array(
						'Sunday',
						'Monday',
						'Tuesday',
						'Wednesday',
						'Thursday',
						'Friday',
						'Saturday',
					);

					$properties['schedule'] = $week_days[ $day_number ];

					break;
				default:
					$properties['schedule'] = $new_fields['crawler-dow'];
			}
		} else {
			$properties = array(
				'automatic_crawls' => 'Disabled',
			);
		}

		$this->tracker()->track( 'SMA - Crawler Report', $properties );
	}

	/**
	 * Handle sitemap automatic sitemap update.
	 *
	 * @since 3.7.0
	 *
	 * @param array $old_value The old option value.
	 *
	 * @return void
	 */
	public function intercept_auto_regeneration( $old_value ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		// Latest values (avoid using new values from hook).
		$new_value = Settings::get_component_options( Settings::COMP_SITEMAP );

		$old_fields = array();
		$new_fields = array();

		foreach (
			array(
				'sitemap-disable-automatic-regeneration',
				'sitemap-update-frequency',
				'sitemap-update-dow',
				'sitemap-update-tod',
			)
			as $field
		) {
			$old_fields[ $field ] = $this->get_value( $field, $old_value );
			$new_fields[ $field ] = $this->get_value( $field, $new_value );
		}

		// If auto generation settings not changed, don't continue.
		if ( $old_fields === $new_fields ) {
			return;
		}

		// Get method.
		$method = $this->get_value( 'sitemap-disable-automatic-regeneration', $new_value, 'auto' );

		$method_mapping = array(
			'auto'      => 'Automatic',
			'manual'    => 'Manual',
			'scheduled' => 'Scheduled',
		);

		// We need a valid method value.
		if ( ! isset( $method_mapping[ $method ] ) ) {
			return;
		}

		$properties = array( 'frequency' => $method_mapping[ $method ] );

		if ( 'scheduled' === $method ) {
			$frequency = $this->get_value( 'sitemap-update-frequency', $new_value );

			$frequency_mapping = array(
				'hourly' => 'Hourly',
				'daily'  => 'Daily',
				'weekly' => 'Weekly',
			);

			// We need a valid frequency value.
			if ( ! isset( $frequency_mapping[ $frequency ] ) ) {
				return;
			}

			$properties['schedule_type'] = $frequency_mapping[ $frequency ];

			switch ( $frequency ) {
				case 'weekly':
					$dow        = $this->get_value( 'sitemap-update-dow', $new_value );
					$day_number = date( 'w', strtotime( 'this Monday' ) + ( $dow * DAY_IN_SECONDS ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					$week_days  = array(
						'Sunday',
						'Monday',
						'Tuesday',
						'Wednesday',
						'Thursday',
						'Friday',
						'Saturday',
					);

					$properties['schedule'] = $week_days[ $day_number ];

					break;
				case 'daily':
					$tod           = $this->get_value( 'sitemap-update-tod', $new_value );
					$tod_formatted = date_i18n( get_option( 'time_format' ), strtotime( 'today' ) + ( $tod * HOUR_IN_SECONDS ) );

					$properties['schedule'] = $tod_formatted;

					break;
				default:
					$properties['schedule'] = 'N/A';
			}
		}

		$this->tracker()->track( 'SMA - Automatic Sitemap Updates', $properties );
	}

	/**
	 * Handle sitemap troubleshooting update.
	 *
	 * @since 3.7.0
	 *
	 * @param array $response Response data for sitemap troubleshooting.
	 *
	 * @return void
	 */
	public function intercept_sitemap_troubleshoot( $response ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		$troubleshoot_count = Utils::get_sitemap_option( 'troubleshoot-count' );

		if ( ! $troubleshoot_count ) {
			return;
		}

		$properties = array(
			'started' => $troubleshoot_count,
			'result'  => $response['fixed'] ? 'No Problem' : 'Error',
		);

		if ( ! $response['fixed'] ) {
			$properties['error_message'] = wp_strip_all_tags( $response['message'] );
		}

		$this->tracker()->track( 'SMA - Troubleshoot Sitemap', $properties );
	}

	/**
	 * Handle SEO crawl result update.
	 *
	 * @since 3.7.0
	 *
	 * @param array  $data    SEO crawl result data.
	 * @param string $trigger Trigger source.
	 *
	 * @return void
	 */
	public function intercept_seo_crawl_result( $data, $trigger ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		// We need timestamps.
		if ( empty( $data['end'] ) || empty( $data['start'] ) ) {
			return;
		}

		$start = (int) $data['start'];
		$end   = (int) $data['end'];

		$report = new Seo_Report();
		$report->build( $data );

		$properties = array(
			'discovered_urls' => $report->get_meta( 'total', 0 ),
			'triggered_from'  => 'hub' === $trigger ? 'Hub' : 'Sitemap',
			'sitemap_issues'  => $report->get_issues_count(),
			'crawl_status'    => $report->has_state_messages() ? 'Error' : 'Complete',
			'total_runtime'   => $end - $start,
		);

		$this->tracker()->track( 'SMA - SEO Crawl', $properties );
	}

	/**
	 * Handle sitemap switch.
	 *
	 * @since 3.7.0
	 *
	 * @param array $old_value The old option value.
	 * @param array $new_value The new option value.
	 *
	 * @return void
	 */
	public function intercept_sitemap_switch( $old_value, $new_value ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		$old_field = $this->get_value( 'override-native', $old_value );
		$new_field = $this->get_value( 'override-native', $new_value );

		if ( $old_field === $new_field ) {
			return;
		}

		$this->tracker()->track(
			'SMA - General Sitemap Switch',
			array(
				'sitemap_type' => $new_field ? 'SmartCrawl' : 'WP Core',
			)
		);
	}

	/**
	 * Handle news sitemap update.
	 *
	 * @since 3.7.0
	 *
	 * @param array $old_value The old option value.
	 * @param array $new_value The new option value.
	 *
	 * @return void
	 */
	public function intercept_news_sitemap( $old_value, $new_value ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		$old_field = $this->get_value( 'enable-news-sitemap', $old_value );
		$new_field = $this->get_value( 'enable-news-sitemap', $new_value );

		if ( $old_field === $new_field ) {
			return;
		}

		$this->tracker()->track( 'SMA - News Sitemap', array( 'action' => $new_field ? 'Enabled' : 'Disabled' ) );
	}
}