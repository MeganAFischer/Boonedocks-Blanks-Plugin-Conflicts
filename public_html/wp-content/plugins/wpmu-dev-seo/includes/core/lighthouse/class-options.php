<?php
/**
 * Manage lighthouse options
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse;

use SmartCrawl\Controllers\Cron;
use SmartCrawl\Settings;

/**
 * Options class
 */
class Options {

	const DASHBOARD_WIDGET_DEVICE     = 'lighthouse-dashboard-widget-device';
	const CRON_ENABLE                 = 'lighthouse-cron-enable';
	const REPORTING_FREQUENCY         = 'lighthouse-frequency';
	const REPORTING_DOM               = 'lighthouse-dom';
	const REPORTING_DOW               = 'lighthouse-dow';
	const REPORTING_TOD               = 'lighthouse-tod';
	const RECIPIENTS                  = 'lighthouse-recipients';
	const REPORTING_CONDITION_ENABLED = 'lighthouse-reporting-condition-enabled';
	const REPORTING_CONDITION         = 'lighthouse-reporting-condition';
	const REPORTING_DEVICE            = 'lighthouse-reporting-device';
	const OPTION_ID                   = 'wds_lighthouse_options';

	/**
	 * Return dashboard widget device.
	 *
	 * @return string
	 */
	public static function dashboard_widget_device() {
		return \smartcrawl_get_array_value( self::get_options(), self::DASHBOARD_WIDGET_DEVICE );
	}

	/**
	 * Return whether reporting is enabled or not.
	 *
	 * @return bool
	 */
	public static function is_cron_enabled() {
		return (bool) \smartcrawl_get_array_value( self::get_options(), self::CRON_ENABLE );
	}

	/**
	 * Return email recipients.
	 *
	 * @return array
	 */
	public static function email_recipients() {
		$recipients = \smartcrawl_get_array_value( self::get_options(), self::RECIPIENTS );

		return empty( $recipients )
			? array()
			: $recipients;
	}

	/**
	 * Return reporting frequency.
	 *
	 * @return string
	 */
	public static function reporting_frequency() {
		return \smartcrawl_get_array_value( self::get_options(), self::REPORTING_FREQUENCY );
	}

	/**
	 * Return reporting date of month.
	 *
	 * @return string
	 */
	public static function reporting_dom() {
		return \smartcrawl_get_array_value( self::get_options(), self::REPORTING_DOM );
	}

	/**
	 * Return reporting day of week.
	 *
	 * @return string
	 */
	public static function reporting_dow() {
		return \smartcrawl_get_array_value( self::get_options(), self::REPORTING_DOW );
	}

	/**
	 * Return reporting time of day.
	 *
	 * @return string
	 */
	public static function reporting_tod() {
		return \smartcrawl_get_array_value( self::get_options(), self::REPORTING_TOD );
	}

	/**
	 * Return reporting device.
	 *
	 * @return string
	 */
	public static function reporting_device() {
		return \smartcrawl_get_array_value( self::get_options(), self::REPORTING_DEVICE );
	}

	/**
	 * Return whether the reporting condition is enabled or not.
	 *
	 * @return bool
	 */
	public static function reporting_condition_enabled() {
		return (bool) \smartcrawl_get_array_value( self::get_options(), self::REPORTING_CONDITION_ENABLED );
	}

	/**
	 * Return reporting condition.
	 *
	 * @return int
	 */
	public static function reporting_condition() {
		return (int) \smartcrawl_get_array_value( self::get_options(), self::REPORTING_CONDITION );
	}

	/**
	 * Save default options.
	 */
	public static function save_defaults() {
		$options  = Settings::get_specific_options( self::OPTION_ID );
		$options  = is_array( $options ) ? $options : array();
		$defaults = array_merge(
			self::get_defaults(),
			array(
				self::RECIPIENTS    => array( self::get_email_recipient() ),
				self::REPORTING_DOM => wp_rand( 1, 28 ),
				self::REPORTING_DOW => wp_rand( 0, 6 ),
				self::REPORTING_TOD => wp_rand( 0, 23 ),
			)
		);
		foreach ( $defaults as $opt => $default ) {
			if ( ! isset( $options[ $opt ] ) ) {
				$options[ $opt ] = $default;
			}
		}
		Settings::update_specific_options( self::OPTION_ID, $options );
	}

	/**
	 * Save form data.
	 *
	 * @param array $input Form data to save.
	 */
	public static function save_form_data( $input ) {
		$result                     = array();
		$email_recipients           = \smartcrawl_get_array_value( $input, self::RECIPIENTS );
		$sanitized_recipients       = \smartcrawl_sanitize_recipients( $email_recipients );
		$result[ self::RECIPIENTS ] = $sanitized_recipients;

		if ( empty( $sanitized_recipients ) ) {
			$result[ self::RECIPIENTS ] = array( self::get_email_recipient() );
		}

		if ( empty( $input[ self::CRON_ENABLE ] ) || empty( $sanitized_recipients ) ) {
			$result[ self::CRON_ENABLE ] = false;
		} else {
			$result[ self::CRON_ENABLE ] = true;
		}

		$frequency                           = ! empty( $input[ self::REPORTING_FREQUENCY ] )
			? Cron::get()->get_valid_frequency( $input[ self::REPORTING_FREQUENCY ] )
			: Cron::get()->get_default_frequency();
		$result[ self::REPORTING_FREQUENCY ] = $frequency;

		$result[ self::REPORTING_DOW ] = self::validate_dow(
			(int) \smartcrawl_get_array_value( $input, self::REPORTING_DOW )
		);

		$result[ self::REPORTING_DOM ] = self::validate_dom(
			(int) \smartcrawl_get_array_value( $input, self::REPORTING_DOM )
		);

		$tod                           = isset( $input[ self::REPORTING_TOD ] ) && is_numeric( $input[ self::REPORTING_TOD ] )
			? (int) $input[ self::REPORTING_TOD ]
			: 0;
		$result[ self::REPORTING_TOD ] = in_array( $tod, range( 0, 23 ), true ) ? $tod : 0;
		$result[ self::REPORTING_CONDITION_ENABLED ] = ! empty( $input[ self::REPORTING_CONDITION_ENABLED ] );
		$result[ self::REPORTING_CONDITION ]         = (int) \smartcrawl_get_array_value( $input, self::REPORTING_CONDITION );
		$result[ self::REPORTING_DEVICE ]            = sanitize_text_field(
			(string) \smartcrawl_get_array_value( $input, self::REPORTING_DEVICE )
		);

		$result[ self::DASHBOARD_WIDGET_DEVICE ] = empty( $input[ self::DASHBOARD_WIDGET_DEVICE ] )
			? 'desktop'
			: sanitize_text_field( $input[ self::DASHBOARD_WIDGET_DEVICE ] );

		Settings::update_specific_options( self::OPTION_ID, $result );
	}

	/**
	 * Validate day of week.
	 *
	 * @param string $dow Day of week.
	 *
	 * @return int|mixed
	 */
	private static function validate_dow( $dow ) {
		return in_array( $dow, range( 0, 6 ), true ) ? $dow : 0;
	}

	/**
	 * Validate date of month.
	 *
	 * @param string $dom Date of month.
	 *
	 * @return int|mixed
	 */
	private static function validate_dom( $dom ) {
		return in_array( $dom, range( 1, 28 ), true ) ? $dom : 1;
	}

	/**
	 * Get lighthouse options.
	 *
	 * @return array
	 */
	public static function get_options() {
		$options = Settings::get_specific_options( self::OPTION_ID );

		return array_merge(
			self::get_defaults(),
			empty( $options ) ? array() : $options
		);
	}

	/**
	 * Get default options.
	 *
	 * @return array
	 */
	private static function get_defaults() {
		return array(
			self::DASHBOARD_WIDGET_DEVICE     => 'desktop',
			self::CRON_ENABLE                 => false,
			self::REPORTING_FREQUENCY         => 'weekly',
			self::REPORTING_DOM               => 1,
			self::REPORTING_DOW               => 0,
			self::REPORTING_TOD               => 0,
			self::RECIPIENTS                  => array(),
			self::REPORTING_CONDITION_ENABLED => false,
			self::REPORTING_CONDITION         => 90,
			self::REPORTING_DEVICE            => 'both',
		);
	}

	/**
	 * Get email recipients.
	 *
	 * @return array
	 */
	private static function get_email_recipient() {
		$user = \SmartCrawl\Models\User::owner();

		return array(
			'name'  => $user->get_display_name(),
			'email' => $user->get_email(),
		);
	}
}