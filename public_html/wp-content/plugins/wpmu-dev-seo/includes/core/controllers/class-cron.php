<?php
/**
 * Periodical execution module
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Logger;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Services;
use SmartCrawl\Lighthouse;
use SmartCrawl\Sitemaps\Utils;

/**
 * Cron controller
 *
 * TODO: make sure emails are sent from the plugin and are only sent when scans are triggered via cron
 */
class Cron {

	use Singleton;

	const ACTION_CRAWL = 'wds-cron-start_service';

	const ACTION_LIGHTHOUSE = 'wds-cron-start_lighthouse';

	const ACTION_LIGHTHOUSE_RESULT = 'wds-cron-lighthouse_result';

	const ACTION_SITEMAP_UPDATE = 'sitemap_update';

	/**
	 * Controller actively running flag
	 *
	 * @var bool
	 */
	private $is_running = false;

	/**
	 * Boots controller interface
	 *
	 * @return bool
	 */
	public function run() {
		if ( ! $this->is_running() ) {
			$this->add_hooks();
		}

		return $this->is_running();
	}

	/**
	 * Check whether controller interface is active
	 *
	 * @return bool
	 */
	public function is_running() {
		return ! ! $this->is_running;
	}

	/**
	 * Sets up controller listening interface
	 *
	 * Also sets up controller running flag approprietly.
	 *
	 * @return void
	 */
	private function add_hooks() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule_intervals' ) );

		if ( Settings::get_setting( 'sitemap' ) && Admin_Settings::is_tab_allowed( Settings::TAB_SITEMAP ) ) {
			$copts = Settings::get_component_options( Settings::COMP_SITEMAP );
			if ( ! empty( $copts['crawler-cron-enable'] ) ) {
				add_action( $this->get_filter( self::ACTION_CRAWL ), array( $this, 'start_crawl' ) );
			}
		}

		// Sitemap updates cron.
		if ( Utils::scheduled_regeneration_enabled() ) {
			add_action( $this->get_filter( self::ACTION_SITEMAP_UPDATE ), array( $this, 'start_sitemap_update' ) );
		}

		if ( Lighthouse\Options::is_cron_enabled() ) {
			add_action( $this->get_filter( self::ACTION_LIGHTHOUSE ), array( $this, 'start_lighthouse' ) );
			add_action(
				$this->get_filter( self::ACTION_LIGHTHOUSE_RESULT ),
				array(
					$this,
					'check_lighthouse_result',
				)
			);
		}

		$this->is_running = true;
	}

	/**
	 * Gets prefixed filter action
	 *
	 * @param string $what Filter action suffix.
	 *
	 * @return string Full filter action
	 */
	public function get_filter( $what ) {
		return 'wds-controller-cron-' . $what;
	}

	/**
	 * Gets next scheduled event time
	 *
	 * @param string $event Optional event name, defaults to service start.
	 *
	 * @return int|bool UNIX timestamp or false if no next event
	 */
	public function get_next_event( $event ) {
		$event = ! empty( $event ) ? $event : self::ACTION_CRAWL;

		return wp_next_scheduled( $this->get_filter( $event ) );
	}

	/**
	 * Unschedules a particular event
	 *
	 * @param string $event Optional event name, defaults to service start.
	 *
	 * @return bool
	 */
	public function unschedule( string $event = '' ) {
		$event = ! empty( $event ) ? $event : self::ACTION_CRAWL;
		Logger::info( "Unscheduling event {$event}" );
		$tstamp = $this->get_next_event( $event );
		if ( $tstamp ) {
			Logger::debug( "Found next event {$event} at {$tstamp}" );
			wp_unschedule_event( $tstamp, $this->get_filter( $event ) );
		}

		wp_clear_scheduled_hook( $this->get_filter( $event ) );

		return true;
	}

	/**
	 * Controller interface stop
	 *
	 * @return bool
	 */
	public function stop() {
		if ( $this->is_running() ) {
			$this->remove_hooks();
		}

		return $this->is_running();
	}

	/**
	 * Tears down controller listening interface
	 *
	 * Also sets up controller running flag approprietly.
	 *
	 * @return void
	 */
	private function remove_hooks() {

		remove_action( $this->get_filter( self::ACTION_CRAWL ), array( $this, 'start_crawl' ) );
		remove_action( $this->get_filter( self::ACTION_SITEMAP_UPDATE ), array( $this, 'start_sitemap_update' ) );
		remove_filter( 'cron_schedules', array( $this, 'add_cron_schedule_intervals' ) );

		$this->is_running = false;
	}

	/**
	 * Checks whether we have a next event scheduled
	 *
	 * @param string $event Optional event name, defaults to service start.
	 *
	 * @return bool
	 */
	public function has_next_event( $event ) {
		return ! ! $this->get_next_event( $event );
	}

	/**
	 * Sets up overall schedules
	 *
	 * @uses Cron::set_up_crawler_schedule()
	 * @return void
	 */
	public function set_up_schedule() {
		Logger::debug( 'Setting up schedules' );
		$this->set_up_crawler_schedule();
		$this->set_up_lighthouse_schedule();
		$this->set_up_sitemap_update_schedule();
	}

	/**
	 * Setup sitemap update schedule.
	 *
	 * @since 3.5.0
	 *
	 * @return bool
	 */
	public function set_up_sitemap_update_schedule() {
		Logger::debug( 'Setting up sitemap update schedule' );

		$options = Settings::get_component_options( Settings::COMP_SITEMAP );

		if ( ! Utils::scheduled_regeneration_enabled() ) {
			Logger::debug( 'Disabling sitemap update cron' );
			$this->unschedule( self::ACTION_SITEMAP_UPDATE );

			return false;
		}

		$current   = $this->get_next_event( self::ACTION_SITEMAP_UPDATE );
		$now       = time();
		$frequency = $this->get_valid_frequency(
			( ! empty( $options['sitemap-update-frequency'] ) ? $options['sitemap-update-frequency'] : array() ),
			array( 'monthly' )
		);

		$tod = $this->validate_tod( (int) \smartcrawl_get_array_value( $options, 'sitemap-update-tod' ) );
		$dow = 'weekly' === $frequency ? $this->validate_dow( (int) \smartcrawl_get_array_value( $options, 'sitemap-update-dow' ) ) : 0;

		$next = $this->get_estimated_next_event( $now, $frequency, $dow, $tod );

		$msg = sprintf( "Attempt rescheduling sitemap update ({$frequency},{$dow},{$tod}): {$next} (%s)", gmdate( 'Y-m-d@H:i', $next ) );
		if ( ! empty( $current ) ) {
			$msg .= sprintf( " by replacing {$current} (%s)", gmdate( 'Y-m-d@H:i', $current ) );
		}
		Logger::debug( $msg );

		$diff = abs( $current - $next );

		if ( $diff > 5 * 60 ) {
			Logger::info(
				sprintf(
					"Rescheduling sitemap update from {$current} (%s) to {$next} (%s)",
					gmdate( 'Y-m-d@H:i', $current ),
					gmdate( 'Y-m-d@H:i', $next )
				)
			);
			$this->schedule( self::ACTION_SITEMAP_UPDATE, $next, $frequency );
		} else {
			Logger::info( 'Currently scheduled sitemap update matches our next sync estimate, leaving it alone' );
		}

		return true;
	}

	/**
	 * Sets up crawl service schedule
	 *
	 * @return bool
	 */
	public function set_up_crawler_schedule() {
		Logger::debug( 'Setting up crawler schedule' );

		$options = Settings::get_component_options( Settings::COMP_SITEMAP );

		if ( empty( $options['crawler-cron-enable'] ) ) {
			Logger::debug( 'Disabling crawler cron' );
			$this->unschedule( self::ACTION_CRAWL );

			return false;
		}

		$current   = $this->get_next_event( self::ACTION_CRAWL );
		$now       = time();
		$frequency = $this->get_valid_frequency(
			( ! empty( $options['crawler-frequency'] ) ? $options['crawler-frequency'] : array() )
		);

		$day_offset = 0;

		if ( 'monthly' === $frequency ) {
			$day_offset = $this->validate_dom( (int) \smartcrawl_get_array_value( $options, 'crawler-dom' ) );
		} elseif ( 'weekly' === $frequency ) {
			$day_offset = $this->validate_dow( (int) \smartcrawl_get_array_value( $options, 'crawler-dow' ) );
		}

		$tod  = $this->validate_tod( (int) \smartcrawl_get_array_value( $options, 'crawler-tod' ) );
		$next = $this->get_estimated_next_event( $now, $frequency, $day_offset, $tod );

		$msg = sprintf( "Attempt rescheduling crawl start ({$frequency},{$day_offset},{$tod}): {$next} (%s)", gmdate( 'Y-m-d@H:i', $next ) );
		if ( ! empty( $current ) ) {
			$msg .= sprintf( " by replacing {$current} (%s)", gmdate( 'Y-m-d@H:i', $current ) );
		}
		Logger::debug( $msg );

		$diff = abs( $current - $next );

		if ( $diff > 5 * 60 ) {
			Logger::info(
				sprintf(
					"Rescheduling crawl start from {$current} (%s) to {$next} (%s)",
					gmdate( 'Y-m-d@H:i', $current ),
					gmdate( 'Y-m-d@H:i', $next )
				)
			);
			$this->schedule( self::ACTION_CRAWL, $next, $frequency );
		} else {
			Logger::info( 'Currently scheduled crawl matches our next sync estimate, leaving it alone' );
		}

		return true;
	}

	/**
	 * Gets estimated next event time based on parameters
	 *
	 * @param int    $pivot     Pivot time - base estimation relative to this (UNIX timestamp).
	 * @param string $frequency Valid frequency interval.
	 * @param int    $day_offset Date offset from start of week or month.
	 * @param int    $tod       Time of day (0-23).
	 *
	 * @return int Estimated next event time as UNIX timestamp
	 */
	public function get_estimated_next_event( $pivot, $frequency, $day_offset, $tod ) {
		$start                = $this->get_initial_pivot_time( $pivot, $frequency );
		$offset               = $start + ( $day_offset * DAY_IN_SECONDS );
		$gmt_offset           = get_option( 'gmt_offset' );
		$time                 = strtotime( gmdate( "Y-m-d {$tod}:00", $offset ) ) - $gmt_offset * 3600;
		$current_month_length = (int) gmdate( 'd', strtotime( 'last day of this month' ) );
		$freqs                = array(
			'hourly'  => HOUR_IN_SECONDS,
			'daily'   => DAY_IN_SECONDS,
			'weekly'  => 7 * DAY_IN_SECONDS,
			'monthly' => $current_month_length * DAY_IN_SECONDS,
		);

		if ( $time > $pivot ) {
			return $time;
		}

		$exclude = 'hourly' === $frequency ? array( 'monthly' ) : array( 'hourly' );

		$freq = $freqs[ $this->get_valid_frequency( $frequency, $exclude ) ];

		return $time + $freq;
	}

	/**
	 * Gets primed pivot time for a given frequency value
	 *
	 * @param int    $pivot     Raw pivot UNIX timestamp.
	 * @param string $frequency Frequency interval.
	 *
	 * @return int Zeroed pivot time for given frequency interval
	 */
	public function get_initial_pivot_time( $pivot, $frequency ) {
		$exclude   = 'hourly' === $frequency ? array( 'monthly' ) : array( 'hourly' );
		$frequency = $this->get_valid_frequency( $frequency, $exclude );

		if ( 'hourly' === $frequency ) {
			return strtotime( gmdate( 'Y-m-d H:i:s', $pivot ) );
		}

		if ( 'daily' === $frequency ) {
			return strtotime( gmdate( 'Y-m-d 00:00', $pivot ) );
		}

		if ( 'weekly' === $frequency ) {
			$monday = strtotime( 'this monday', $pivot );
			if ( $monday > $pivot ) {
				return $monday - ( 7 * DAY_IN_SECONDS );
			}

			return $monday;
		}

		if ( 'monthly' === $frequency ) {
			$day   = (int) gmdate( 'd', $pivot );
			$today = strtotime( gmdate( 'Y-m-d H:i', $pivot ) );

			return $today - ( $day * DAY_IN_SECONDS );
		}

		return $pivot;
	}

	/**
	 * Gets validated frequency interval
	 *
	 * @param string $freq    Raw frequency string.
	 * @param array  $exclude Excluded frequencies (Hourly is excluded by default).
	 *
	 * @return string
	 */
	public function get_valid_frequency( $freq, $exclude = array( 'hourly' ) ) {
		if ( in_array( $freq, array_keys( $this->get_frequencies( $exclude ) ), true ) ) {
			return $freq;
		}

		return $this->get_default_frequency();
	}

	/**
	 * Gets a list of frequency intervals
	 *
	 * @param array $exclude Excluded frequencies (Hourly is excluded by default).
	 *
	 * @return array
	 */
	public function get_frequencies( $exclude = array( 'hourly' ) ) {
		$frequencies = array(
			'hourly'  => __( 'Hourly', 'wds' ),
			'daily'   => __( 'Daily', 'wds' ),
			'weekly'  => __( 'Weekly', 'wds' ),
			'monthly' => __( 'Monthly', 'wds' ),
		);

		// Exclude items if required.
		if ( ! empty( $exclude ) ) {
			foreach ( $exclude as $frequency ) {
				unset( $frequencies[ $frequency ] );
			}
		}

		return $frequencies;
	}

	/**
	 * Gets default frequency interval (fallback)
	 *
	 * @return string
	 */
	public function get_default_frequency() {
		return 'weekly';
	}

	/**
	 * Schedules a particular event
	 *
	 * @param string $event      Event name.
	 * @param int    $time       UNIX timestamp.
	 * @param string $recurrence Event recurrence.
	 *
	 * @return bool
	 */
	public function schedule( $event, $time, $recurrence ) {
		Logger::info( "Start scheduling new {$recurrence} event {$event}" );

		$this->unschedule( $event );
		$exclude    = 'hourly' === $recurrence ? array( 'monthly' ) : array( 'hourly' );
		$recurrence = $this->get_valid_frequency( $recurrence, $exclude );
		$now        = time();

		while ( $time < $now ) {
			Logger::debug( "Time in the past, applying offset for {$recurrence} recurrence" );
			$offset = HOUR_IN_SECONDS;
			if ( 'daily' === $recurrence ) {
				$offset = DAY_IN_SECONDS;
			} elseif ( 'weekly' === $recurrence ) {
				$offset = WEEK_IN_SECONDS;
			} elseif ( 'monthly' === $recurrence ) {
				$offset = MONTH_IN_SECONDS;
			}

			$time += $offset;
		}

		// Make the time not round.
		if ( 'hourly' !== $recurrence ) {
			$time += wp_rand( 0, 5 ) * 60;
		}

		Logger::debug( sprintf( "Adding new {$recurrence} event {$event} at {$time} (%s)", gmdate( 'Y-m-d@H:i', $time ) ) );

		$result = wp_schedule_event( $time, $recurrence, $this->get_filter( $event ) ) !== false;

		if ( $result ) {
			Logger::info( "New {$recurrence} event {$event} added at {$time}" );
		} else {
			Logger::warning( "Failed adding new {$recurrence} event {$event} at {$time}" );
		}

		return $result;
	}

	/**
	 * Validates time of day value and returns correct time.
	 *
	 * @param int $tod Time of day value between 0 ~ 23.
	 *
	 * @return int
	 */
	private function validate_tod( $tod ) {
		return in_array( $tod, range( 0, 23 ), true ) ? $tod : 0;
	}

	/**
	 * Validates day of week value and returns correct one.
	 *
	 * @param int $dow Day of week as number.
	 *
	 * @return int
	 */
	private function validate_dow( $dow ) {
		return in_array( $dow, range( 0, 6 ), true ) ? $dow : 0;
	}

	/**
	 * Validates date of month value and returns correct one.
	 *
	 * @param int $dom Date of month as number.
	 *
	 * @return int
	 */
	private function validate_dom( $dom ) {
		return in_array( $dom, range( 1, 28 ), true ) ? $dom : 1;
	}

	/**
	 * Starts crawl.
	 */
	public function start_crawl() {
		Logger::debug( 'Triggered automated crawl start action' );

		$service = Services\Service::get( Services\Service::SERVICE_SEO );
		$result  = $service->start();

		if ( true === $result ) {
			Logger::debug( 'Successfully started a crawl' );
		} else {
			Logger::warning( 'Automated crawl start action failed' );
		}
	}

	/**
	 * Starts sitemap regeneration.
	 *
	 * @since 3.5.0
	 */
	public function start_sitemap_update() {
		Logger::debug( 'Triggered automated sitemap update action' );

		// Delete cache.
		\SmartCrawl\Sitemaps\Controller::get()->invalidate_sitemap_cache();
		// Regenerate.
		Utils::prime_cache( true );

		Logger::debug( 'Successfully updated sitemap' );
	}

	/**
	 * Set up cron schedule intervals
	 *
	 * @param array $intervals Intervals known this far.
	 *
	 * @return array
	 */
	public function add_cron_schedule_intervals( $intervals ) {
		if ( ! is_array( $intervals ) ) {
			return $intervals;
		}

		if ( ! isset( $intervals['hourly'] ) ) {
			$intervals['hourly'] = array(
				/* translators: %s: plugin title */
				'display'  => sprintf( __( '%s Hourly', 'wds' ), \smartcrawl_get_plugin_title() ),
				'interval' => HOUR_IN_SECONDS,
			);
		}

		if ( ! isset( $intervals['daily'] ) ) {
			$intervals['daily'] = array(
				/* translators: %s: plugin title */
				'display'  => sprintf( __( '%s Daily', 'wds' ), \smartcrawl_get_plugin_title() ),
				'interval' => DAY_IN_SECONDS,
			);
		}

		if ( ! isset( $intervals['weekly'] ) ) {
			$intervals['weekly'] = array(
				/* translators: %s: plugin title */
				'display'  => sprintf( __( '%s Weekly', 'wds' ), \smartcrawl_get_plugin_title() ),
				'interval' => 7 * DAY_IN_SECONDS,
			);
		}

		if ( ! isset( $intervals['monthly'] ) ) {
			$intervals['monthly'] = array(
				/* translators: %s: plugin title */
				'display'  => sprintf( __( '%s Monthly', 'wds' ), \smartcrawl_get_plugin_title() ),
				'interval' => 30 * DAY_IN_SECONDS,
			);
		}

		return $intervals;
	}

	/**
	 * Clone
	 */
	private function __clone() {
	}

	/**
	 * Sets up lighthouse schedule.
	 *
	 * @return bool
	 */
	public function set_up_lighthouse_schedule() {
		Logger::debug( 'Setting up lighthouse schedule' );

		if ( ! Lighthouse\Options::is_cron_enabled() ) {
			Logger::debug( 'Disabling lighthouse cron' );
			$this->unschedule( self::ACTION_LIGHTHOUSE );
			$this->unschedule( self::ACTION_LIGHTHOUSE_RESULT );

			return false;
		}

		$current   = $this->get_next_event( self::ACTION_LIGHTHOUSE );
		$now       = time();
		$frequency = $this->get_valid_frequency(
			Lighthouse\Options::reporting_frequency()
		);

		$day_offset = 0;

		if ( 'monthly' === $frequency ) {
			$day_offset = $this->validate_dom( (int) Lighthouse\Options::reporting_dom() );
		} elseif ( 'weekly' === $frequency ) {
			$day_offset = $this->validate_dow( (int) Lighthouse\Options::reporting_dow() );
		}

		$tod  = $this->validate_tod( (int) Lighthouse\Options::reporting_tod() );
		$next = $this->get_estimated_next_event( $now, $frequency, $day_offset, $tod );

		$msg = sprintf( "Attempt rescheduling lighthouse start ({$frequency},{$day_offset},{$tod}): {$next} (%s)", gmdate( 'Y-m-d@H:i', $next ) );
		if ( ! empty( $current ) ) {
			$msg .= sprintf( " by replacing {$current} (%s)", gmdate( 'Y-m-d@H:i', $current ) );
		}
		Logger::debug( $msg );

		$diff = abs( $current - $next );
		if ( $diff > 5 * 60 ) {
			Logger::info(
				sprintf(
					"Rescheduling lighthouse start from {$current} (%s) to {$next} (%s)",
					gmdate( 'Y-m-d@H:i', $current ),
					gmdate( 'Y-m-d@H:i', $next )
				)
			);
			$this->schedule( self::ACTION_LIGHTHOUSE, $next, $frequency );
		} else {
			Logger::info( 'Currently scheduled lighthouse matches our next sync estimate, leaving it alone' );
		}

		return true;
	}

	/**
	 * Handles to start lighthouse seo service.
	 *
	 * @return void
	 */
	public function start_lighthouse() {
		Logger::debug( 'Triggered automated lighthouse start action' );

		/**
		 * Light house service.
		 *
		 * @var Services\Lighthouse $service
		 */
		$service = Services\Service::get( Services\Service::SERVICE_LIGHTHOUSE );
		$service->start();

		Logger::debug( 'Successfully started a lighthouse check' );
		$this->schedule_lighthouse_result_check();
	}

	/**
	 * Schedules lighthouse result check.
	 *
	 * @return void
	 */
	public function schedule_lighthouse_result_check() {
		wp_schedule_single_event(
			time() + 30,
			$this->get_filter( self::ACTION_LIGHTHOUSE_RESULT ),
			array( wp_rand() )
		);
	}

	/**
	 * Checks lighthouse result.
	 *
	 * @return void
	 */
	public function check_lighthouse_result() {
		Logger::debug( 'Triggered lighthouse results check' );

		/**
		 * Light house service.
		 *
		 * @var Services\Lighthouse $service
		 */
		$service = Services\Service::get( Services\Service::SERVICE_LIGHTHOUSE );
		$service->stop();
		$report_refreshed = $service->refresh_report();

		if ( $report_refreshed ) {
			$service->maybe_send_emails();
		}
	}
}