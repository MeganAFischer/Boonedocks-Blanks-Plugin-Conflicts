<?php
/**
 * Handles cron jobs for Maxmind.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Integration\Maxmind;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Controllers;
use SmartCrawl\Logger;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * Cron Controller
 */
class Cron extends Controllers\Controller {

	use Singleton;

	const EVENT_HOOK = 'wds_cron_download_geodb';

	/**
	 * Can we add meta box.
	 *
	 * @return bool
	 */
	public function should_run() {
		return true;
	}

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'admin_init', array( $this, 'schedule_cron' ) );
		add_action( self::EVENT_HOOK, array( $this, 'cron_download_geodb' ) );
	}

	/**
	 * Unschedules cron jobs.
	 *
	 * @return bool
	 */
	public function stop() {
		wp_clear_scheduled_hook( self::EVENT_HOOK );

		return parent::stop();
	}

	/**
	 * Removes action hooks when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {
		remove_action( 'admin_init', array( $this, 'schedule_cron' ) );
		remove_action( self::EVENT_HOOK, array( $this, 'cron_download_geodb' ) );
	}

	/**
	 * Schedule cron event.
	 *
	 * @return void
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( self::EVENT_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::EVENT_HOOK );
		}
	}

	/**
	 * Save the moz data.
	 *
	 * @return void
	 */
	public function cron_download_geodb() {
		$license_key = GeoDB::get()->get_license( false );

		$tmp = GeoDB::get()->download_url( $license_key );

		if ( is_wp_error( $tmp ) ) {
			Logger::error( 'Error in Cron to download DB from MaxMind: ' . $tmp->get_error_message() );
			return;
		}

		$db_path = GeoDB::get()->extract_db( $tmp );

		if ( is_wp_error( $db_path ) ) {
			Logger::error( 'Error in Cron to extract DB from MaxMind: ' . $db_path->get_error_message() );
			return;
		}

		Logger::debug( 'Downloading MaxMind DB in Cron is done.' );
	}
}