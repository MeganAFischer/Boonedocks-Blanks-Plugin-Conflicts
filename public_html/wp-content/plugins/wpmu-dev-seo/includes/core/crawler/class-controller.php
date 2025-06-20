<?php
/**
 * Crawler controller.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Crawler;

use SmartCrawl\Controllers;
use SmartCrawl\Logger;
use SmartCrawl\Models\Ignores;
use SmartCrawl\Redirects;
use SmartCrawl\Services\Service;
use SmartCrawl\Services\Seo;
use SmartCrawl\Singleton;
use SmartCrawl\Sitemaps\Utils;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Crawler Controller.
 */
class Controller extends Controllers\Controller {

	use Singleton;

	/**
	 * An instance of the SEO class.
	 *
	 * @var SEO
	 */
	private $seo_service;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		$this->seo_service = Service::get( Service::SERVICE_SEO );
	}

	/**
	 * Initializes the actions for AJAX requests.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'wp_ajax_smartcrawl_crawl_redirect', array( $this, 'redirect_crawl_item' ) );
		add_action( 'wp_ajax_smartcrawl_crawl_ignore', array( $this, 'ignore_crawl_item' ) );
		add_action( 'wp_ajax_smartcrawl_crawl_restore', array( $this, 'restore_crawl_item' ) );
		add_action( 'wp_ajax_smartcrawl_crawl_restore_all', array( $this, 'restore_all' ) );
		add_action( 'wp_ajax_smartcrawl_sitemap_add_extra', array( $this, 'add_sitemap_extra' ) );
		add_action( 'wp_ajax_smartcrawl_crawl_get_progress', array( $this, 'get_crawl_progress' ) );
	}

	/**
	 * Ajax handler to retrieve the progress of the crawl.
	 *
	 * @return void
	 */
	public function get_crawl_progress() {
		if ( empty( $this->get_request_data() ) ) {
			wp_send_json_error();
		}

		$report = $this->seo_service->get_report();

		wp_send_json_success(
			array(
				'in_progress' => $report->is_in_progress(),
				'progress'    => $report->get_progress(),
			)
		);
	}

	/**
	 * Ajax handler to redirect a crawl item.
	 *
	 * @return void
	 */
	public function redirect_crawl_item() {
		$data = $this->get_request_data();

		if ( empty( $data ) ) {
			wp_send_json_error();
		}

		$source      = (string) \smartcrawl_get_array_value( $data, 'source' );
		$destination = (string) \smartcrawl_get_array_value( $data, 'destination' );

		if ( ! $source || ! $destination ) {
			wp_send_json_error();
		}

		$redirect          = \SmartCrawl\Modules\Advanced\Redirects\Utils::get()->create_redirect_item( $source, $destination );
		$table             = \SmartCrawl\Modules\Advanced\Redirects\Database_Table::get();
		$existing_redirect = $table->get_redirect_by_source( $redirect->get_source() );

		if ( $existing_redirect ) {
			$existing_redirect->set_destination( $destination );
			$table->save_redirect( $existing_redirect );
		} else {
			$table->save_redirect( $redirect );
		}

		$this->send_success_response();
	}

	/**
	 * Ajax handler to ignore the crawl item by changing its status and adding it to the ignores list.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function ignore_crawl_item() {
		$this->change_crawl_item_status(
			function ( $issue ) {
				$ignores = new Ignores();
				$ignores->set_ignore( $issue );
			}
		);
	}

	/**
	 * Ajax handler to restore a crawl item by changing its status and removing it from the ignore list.
	 */
	public function restore_crawl_item() {
		$this->change_crawl_item_status(
			function ( $issue ) {
				$ignores = new Ignores();
				$ignores->unset_ignore( $issue );
			}
		);
	}

	/**
	 * Changes the status of a crawl item and performs an operation on it.
	 *
	 * @param callable $operation The operation to perform on the crawl item.
	 */
	public function change_crawl_item_status( $operation ) {
		$data = $this->get_request_data();
		if ( empty( $data ) ) {
			wp_send_json_error();
		}

		$issue_id = \smartcrawl_get_array_value( $data, 'issue_id' );
		if ( ! $issue_id ) {
			wp_send_json_error();
		}

		$issue_ids = is_array( $issue_id )
			? array_map( 'sanitize_text_field', $issue_id )
			: array( sanitize_text_field( $issue_id ) );

		foreach ( $issue_ids as $issue ) {
			call_user_func( $operation, $issue );
		}

		$this->sync_ignores_with_hub();

		$this->send_success_response();
	}

	/**
	 * Restores all crawl items by clearing the ignore list and synchronizing it with the hub.
	 * Sends a success response if successful.
	 */
	private function restore_all() {
		$data = $this->get_request_data();

		if ( empty( $data ) ) {
			wp_send_json_error();
		}

		$ignores = new Ignores();
		$ignores->clear();

		$this->sync_ignores_with_hub();

		$this->send_success_response();
	}

	/**
	 * Ajax handler to add extra URLs to the sitemap.
	 */
	public function add_sitemap_extra() {
		$data = $this->get_request_data();

		if ( empty( $data ) ) {
			wp_send_json_error();
		}

		$path = \smartcrawl_get_array_value( $data, 'path' );

		if ( ! $path ) {
			wp_send_json_error();
		}

		$paths = is_array( $path )
			? array_map( 'sanitize_text_field', (array) $path )
			: array( sanitize_text_field( $path ) );

		$extras = Utils::get_extra_urls();

		foreach ( $paths as $current_path ) {
			if ( false !== array_search( $current_path, $extras, true ) ) {
				continue;
			}

			$current_path = esc_url( $current_path );
			if ( false !== array_search( $current_path, $extras, true ) ) {
				continue;
			}

			$extras[] = $current_path;
		}

		Utils::set_extra_urls( $extras );

		$this->send_success_response();
	}

	/**
	 * Sends a success response as JSON, including the issues grouped by type from the SEO service's report
	 */
	private function send_success_response() {
		$report = $this->seo_service->get_report();

		wp_send_json_success(
			array(
				'issues' => $report->get_all_issues_grouped_by_type(),
			)
		);
	}

	/**
	 * Retrieves the request data from POST.
	 *
	 * @return array The sanitized request data.
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-crawler-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}

	/**
	 * Syncs ignores with Hub.
	 */
	private function sync_ignores_with_hub() {
		$service = Service::get( Service::SERVICE_SEO );

		if ( ! $service->sync_ignores() ) {
			Logger::debug( 'We encountered an error syncing ignores with Hub' );
		}
	}
}