<?php
/**
 * Hub connector for Pro version.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Logger;
use SmartCrawl\Services\Service;
use SmartCrawl\Singleton;
use SmartCrawl\Models\Ignores;
use SmartCrawl\Sitemaps\Utils;

/**
 * Hub Class for Pro version.
 */
class Hub extends Hub_Abstract {

	use Singleton;

	/**
	 * Determins if the class's action hooks are already run.
	 *
	 * @var bool
	 */
	private $is_running = false;

	/**
	 * Boots controller listeners only once.
	 *
	 * @return bool
	 */
	public static function serve() {
		$me = self::get();
		if ( $me->is_running() ) {
			return false;
		}

		$me->add_hooks();

		return true;
	}

	/**
	 * Checks if we already have the actions bound.
	 *
	 * @return bool Status
	 */
	public function is_running() {
		return $this->is_running;
	}

	/**
	 * Binds listening actions.
	 */
	private function add_hooks() {
		add_filter( 'wdp_register_hub_action', array( $this, 'register_hub_actions' ) );

		$this->is_running = true;
	}

	/**
	 * Registers HUB actions.
	 *
	 * @param array $actions The hub actions array.
	 *
	 * @return array The modified hub actions array.
	 */
	public function register_hub_actions( $actions ) {
		if ( ! is_array( $actions ) ) {
			return $actions;
		}

		$actions['wds-sync-ignores']  = array( $this, 'ajax_sync_ignores' );
		$actions['wds-purge-ignores'] = array( $this, 'ajax_purge_ignores' );

		$actions['wds-sync-extras']  = array( $this, 'ajax_sync_extras' );
		$actions['wds-purge-extras'] = array( $this, 'ajax_purge_extras' );

		$actions['wds-audit-data'] = array( $this, 'ajax_receive_audit_data' );

		$actions['wds-seo-summary'] = array( $this, 'ajax_seo_summary' );
		$actions['wds-run-crawl']   = array( $this, 'ajax_run_crawl' );

		$actions['wds-refresh-lighthouse-report'] = array( $this, 'ajax_refresh_lighthouse_report' );

		$actions['wds-apply-config']  = array( $this, 'ajax_apply_config' );
		$actions['wds-export-config'] = array( $this, 'ajax_export_config' );

		return $actions;
	}

	/**
	 * Convert an object to an array
	 *
	 * @param object $data The object to convert to an array.
	 *
	 * @return array The converted array.
	 */
	public function obj_to_array( $data ) {
		return json_decode(
			wp_json_encode( $data ),
			true
		);
	}

	/**
	 * Receives the SEO Audit data pushes from the Hub
	 *
	 * Updates the crawl state.
	 *
	 * @param object $params Hub-provided parameters.
	 * @param string $action Action called.
	 */
	public function ajax_receive_audit_data( $params, $action = '' ) {
		$service = Service::get( Service::SERVICE_SEO );
		$data    = $this->obj_to_array( $params );

		$service->set_result( $data );

		$in_progress = empty( $data['end'] );

		$service->set_progress_flag( $in_progress );
		$service->set_last_run_timestamp();

		if ( ! $in_progress ) {
			$service->after_done();
		}

		Logger::debug( 'Received sitemap crawl data from remote' );

		wp_send_json_success();
	}

	/**
	 * Freshes ignores from the Hub action handler
	 *
	 * Updates local ignores list when the Hub storage is updated.
	 *
	 * @param object $params Hub-provided parameters.
	 * @param string $action Action called.
	 */
	public function ajax_sync_ignores( $params, $action = '' ) {
		Logger::info( 'Received ignores syncing request' );

		$status = $this->sync_ignores( (array) $params, $action );

		! empty( $status ) ? wp_send_json_success() : wp_send_json_error();
	}

	/**
	 * Freshes ignores from the Hub action handler
	 *
	 * Updates local ignores list when the Hub storage is updated.
	 *
	 * @param array  $params Hub-provided parameters.
	 * @param string $action Action called.
	 *
	 * @return bool Status
	 */
	public function sync_ignores( $params, $action = '' ) {
		$ignores = new Ignores();

		$data = stripslashes_deep( $params );

		if ( empty( $data['issue_ids'] ) || ! is_array( $data['issue_ids'] ) ) {
			return false;
		}

		$status = true;

		foreach ( $data['issue_ids'] as $issue_id ) {
			$tmp = $ignores->set_ignore( $issue_id );
			if ( ! $tmp ) {
				$status = false;
			}
		}

		return $status;
	}

	/**
	 * Purges ignores from the Hub action handler.
	 * Purges local ignores list when the Hub storage is purged.
	 */
	public function ajax_purge_ignores() {
		Logger::info( 'Received ignores purging request' );

		$status = $this->purge_ignores();

		! empty( $status ) ? wp_send_json_success() : wp_send_json_error();
	}

	/**
	 * Purges ignores from the Hub action handler
	 *
	 * Purges local ignores list when the Hub storage is purged.
	 *
	 * @return bool Status
	 */
	public function purge_ignores() {
		$ignores = new Ignores();

		return $ignores->clear();
	}

	/**
	 * Freshes extras from the Hub action handler
	 *
	 * Updates local extra URLs list when the Hub storage is updated.
	 *
	 * @param object $params Hub-provided parameters.
	 * @param string $action Action called.
	 */
	public function ajax_sync_extras( $params, $action = '' ) {
		Logger::info( 'Received extras syncing request' );

		$status = $this->sync_extras( (array) $params, $action );

		! empty( $status ) ? wp_send_json_success() : wp_send_json_error();
	}

	/**
	 * Freshes extras from the Hub action handler
	 *
	 * Updates local extra URLs list when the Hub storage is updated.
	 *
	 * @param array  $params Hub-provided parameters.
	 * @param string $action Action called.
	 *
	 * @return bool Status
	 */
	public function sync_extras( $params, $action = '' ) {
		$data = stripslashes_deep( (array) $params );

		if ( empty( $data['urls'] ) || ! is_array( $data['urls'] ) ) {
			return false;
		}

		$existing = Utils::get_extra_urls();

		foreach ( $data['urls'] as $url ) {
			$existing[] = esc_url( $url );
		}

		return Utils::set_extra_urls( $existing );
	}

	/**
	 * Purges extras from the Hub action handler.
	 * Purges local extra URLs list when the Hub storage is updated.
	 */
	public function ajax_purge_extras() {
		$status = $this->purge_extras();

		! empty( $status ) ? wp_send_json_success() : wp_send_json_error();
	}

	/**
	 * Purges extras from the Hub action handler
	 *
	 * Purges local extra URLs list when the Hub storage is updated.
	 *
	 * @return bool Status
	 */
	public function purge_extras() {
		return Utils::set_extra_urls( array() );
	}
}