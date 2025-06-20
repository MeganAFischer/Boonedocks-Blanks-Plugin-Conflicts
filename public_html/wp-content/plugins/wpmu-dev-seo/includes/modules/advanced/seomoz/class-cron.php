<?php
/**
 * Class Cron
 *
 * @package    SmartCrawl
 * @subpackage Seomoz
 */

namespace SmartCrawl\Modules\Advanced\Seomoz;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Controllers;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * Class Cron
 */
class Cron extends Controllers\Controller {

	use Singleton;

	const EVENT_HOOK = 'wds_daily_moz_data_hook';

	const OPTION_ID = 'wds-moz-data';

	/**
	 * Can we add meta box.
	 *
	 * @return bool
	 */
	public function should_run() {
		return ! empty( $this->options['access_id'] ) && ! empty( $this->options['secret_key'] );
	}

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'admin_init', array( $this, 'schedule_moz_data_event' ) );
		add_action( self::EVENT_HOOK, array( $this, 'save_moz_data' ) );
	}

	/**
	 * Terminates cron jobs.
	 *
	 * @return bool
	 */
	public function stop() {
		wp_clear_scheduled_hook( self::EVENT_HOOK );

		return parent::stop();
	}

	/**
	 * Includes methods when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {
		remove_action( 'admin_init', array( $this, 'schedule_moz_data_event' ) );
		remove_action( self::EVENT_HOOK, array( $this, 'save_moz_data' ) );
	}

	/**
	 * Schedule cron event.
	 *
	 * @return void
	 */
	public function schedule_moz_data_event() {
		if ( ! wp_next_scheduled( self::EVENT_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::EVENT_HOOK );
		}
	}

	/**
	 * Save the moz data.
	 *
	 * @return void
	 */
	public function save_moz_data() {
		$access_id  = $this->options['access_id'];
		$secret_key = $this->options['secret_key'];

		if ( empty( $access_id ) || empty( $secret_key ) ) {
			wp_clear_scheduled_hook( self::EVENT_HOOK );
			return;
		}

		$target_url = preg_replace( '!http(s)?:\/\/!', '', home_url() );
		$api        = new API( $access_id, $secret_key );
		$urlmetrics = $api->urlmetrics( $target_url );

		$data           = get_option( self::OPTION_ID, array() );
		$data           = empty( $data ) || ! is_array( $data )
			? array()
			: $data;
		$data[ time() ] = $urlmetrics;
		update_option( self::OPTION_ID, $data );
	}
}