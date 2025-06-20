<?php
/**
 * File containing the Seo_Report class for SmartCrawl plugin.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Modules\Advanced\Redirects\Database_Table;
use SmartCrawl\Models\Ignores;

/**
 * Class Seo_Report
 *
 * Provides SEO report generation and management for the SmartCrawl plugin.
 *
 * @package SmartCrawl
 */
class Seo_Report {

	/**
	 * Indicates if a report is in progress.
	 *
	 * @var bool
	 */
	private $in_progress = false;

	/**
	 * Progress of the report generation.
	 *
	 * @var int
	 */
	private $progress = 0;

	/**
	 * Timestamp when the report generation started.
	 *
	 * @var int
	 */
	private $start_timestamp = 0;

	/**
	 * List of report items.
	 *
	 * @var array
	 */
	private $items = array();

	/**
	 * Report items grouped by type.
	 *
	 * @var array
	 */
	private $by_type = array();

	/**
	 * State messages of the report.
	 *
	 * @var array
	 */
	private $state_messages = array();

	/**
	 * Meta information of the report.
	 *
	 * @var array
	 */
	private $meta = array();

	/**
	 * Ignored items manager.
	 *
	 * @var Ignores
	 */
	private $ignores;

	/**
	 * Number of sitemap issues.
	 *
	 * @var int
	 */
	private $sitemap_issues = 0;

	/**
	 * Redirects table.
	 *
	 * @var Database_Table
	 */
	private $redirects_table;

	/**
	 * Constructor for the Seo_Report class.
	 */
	public function __construct() {
		$this->ignores         = new Ignores();
		$this->redirects_table = Database_Table::get();
	}

	/**
	 * Builds report instance
	 *
	 * @param array $raw Raw crawl report, as returned by service.
	 *
	 * @return Seo_Report instance
	 */
	public function build( $raw ) {
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$issues = ! empty( $raw['issues'] )
			? $raw['issues']
			: array();
		if ( isset( $issues['issues'] ) && is_array( $issues['issues'] ) ) {
			$issues = $issues['issues'];
		}

		$this->build_meta( $raw );
		$this->build_issues( $issues );

		return $this;
	}

	/**
	 * Builds report meta list
	 *
	 * @param array $raw Raw crawl report, as returned by service.
	 *
	 * @return void
	 */
	public function build_meta( $raw ) {
		$sitemap_total = ! empty( $raw['sitemap_total'] )
			? $raw['sitemap_total']
			: ( ! empty( $raw['issues']['sitemap_total'] ) ? $raw['issues']['sitemap_total'] : 0 );
		$discovered    = ! empty( $raw['discovered'] )
			? $raw['discovered']
			: ( ! empty( $raw['issues']['discovered'] ) ? $raw['issues']['discovered'] : 0 );

		if ( ! empty( $raw['issues']['messages'] ) ) {
			foreach ( $raw['issues']['messages'] as $msg ) {
				$this->state_messages[] = $msg;
			}
		}

		$total = isset( $raw['total'] )
			? (int) $raw['total']
			: 0;

		$this->meta = array(
			'sitemap_total' => $sitemap_total,
			'discovered'    => $discovered,
			'total'         => $total,
		);
	}

	/**
	 * Builds report instance issues
	 *
	 * @param array $raw Raw issues list, as returned by service.
	 *
	 * @return object Seo_Report instance
	 */
	public function build_issues( $raw ) {
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		foreach ( $raw as $type => $items ) {
			if ( ! is_array( $items ) || empty( $items ) ) {
				continue;
			}
			if ( ! in_array( $type, array_keys( $this->by_type ), true ) ) {
				$this->by_type[ $type ] = array();
			}
			foreach ( $items as $item ) {
				$key  = $this->get_item_key( $item, $type );
				$path = (string) \smartcrawl_get_array_value( $item, 'path' );
				if ( empty( $key ) ) {
					continue; // Invalid key.
				}
				$item['type']     = $type;
				$item['ignored']  = $this->is_ignored_issue( $key );
				$item['redirect'] = $this->get_redirect( $path );

				$this->items[ $key ]            = $item;
				$this->by_type[ $type ][ $key ] = $item;
			}
		}

		// Special case sitemap issues reporting.
		if ( ! empty( $raw['sitemap'] ) && is_numeric( $raw['sitemap'] ) ) {
			$this->sitemap_issues = (int) $raw['sitemap'];
		}

		if ( empty( $this->state_messages ) && ! empty( $raw['messages'] ) ) {
			foreach ( $raw['messages'] as $msg ) {
				$this->state_messages[] = $msg;
			}
		}

		return $this;
	}

	/**
	 * Creates an unique key for a corresponding item
	 *
	 * @param array  $item Item to create the key for.
	 * @param string $type Optional item type.
	 *
	 * @return string Unique key
	 */
	public function get_item_key( $item, $type = false ) {
		if ( ! is_array( $item ) ) {
			return false;
		}
		if ( empty( $item['path'] ) ) {
			return false;
		}

		if ( empty( $type ) ) {
			$type = 'generic';
		}

		return md5( "{$type}-{$item['path']}" );
	}

	/**
	 * Returns known issue types
	 *
	 * @return array List of known issue types identifiers
	 */
	public function get_issue_types() {
		return array_keys( $this->by_type );
	}

	/**
	 * Gets a list of ignored items
	 *
	 * @return array List of ignored items unique IDs
	 */
	public function get_ignored_issues() {
		return $this->ignores->get_all();
	}

	/**
	 * Gets issues count, for all issues or by type
	 *
	 * @param string $type            Optional issue type.
	 *                                - if omitted, all issues are counted.
	 * @param bool   $include_ignored Whether to include ignored items (default: no).
	 *
	 * @return int Issues count
	 */
	public function get_issues_count( $type = false, $include_ignored = false ) {
		$issues = empty( $type )
			? $this->get_all_issues( $include_ignored )
			: $this->get_issues_by_type( $type, $include_ignored );

		return (int) count( $issues );
	}

	/**
	 * Gets unique IDs of all issues
	 *
	 * @param bool $include_ignored Whether to include ignored items (default: no).
	 *
	 * @return array List of all known issues
	 */
	public function get_all_issues( $include_ignored = false ) {
		$all = $this->items;
		if ( ! empty( $include_ignored ) ) {
			return $all;
		}

		$result = array();
		foreach ( $all as $key => $issue ) {
			if ( ! \smartcrawl_get_array_value( $issue, 'ignored' ) ) {
				$result[ $key ] = $issue;
			}
		}

		return $result;
	}

	/**
	 * Checks if an issue is to be ignored.
	 *
	 * @param string $key Key.
	 *
	 * @return bool
	 */
	public function is_ignored_issue( $key ) {
		return (bool) $this->ignores->is_ignored( $key );
	}

	/**
	 * Gets issues for a specific issue type
	 *
	 * @param string $type            Type identifier.
	 * @param bool   $include_ignored Whether to include ignored items (default: no).
	 *
	 * @return array List of issues for this type
	 */
	public function get_issues_by_type( $type, $include_ignored = false ) {
		$issues = ! empty( $this->by_type[ $type ] ) && is_array( $this->by_type[ $type ] )
			? $this->by_type[ $type ]
			: array();

		if ( ! empty( $include_ignored ) ) {
			return $issues;
		}

		$result = array();
		foreach ( $issues as $key => $issue ) {
			if ( ! \smartcrawl_get_array_value( $issue, 'ignored' ) ) {
				$result[ $key ] = $issue;
			}
		}

		return $result;
	}

	/**
	 * Gets all issues grouped by type.
	 *
	 * @return array List of issues grouped by type
	 */
	public function get_all_issues_grouped_by_type() {
		return empty( $this->by_type )
			? array()
			: $this->by_type;
	}

	/**
	 * Gets count of URLs not in sitemaps
	 *
	 * @return int Count
	 */
	public function get_sitemap_misses() {
		$count = (int) $this->sitemap_issues;

		return 0 === $count
			? (int) $this->get_issues_count( 'sitemap' )
			: $count;
	}

	/**
	 * Gets a meta key value
	 *
	 * @param string $key      Meta key to check.
	 * @param mixed  $fallback What to return instead if there's no such key.
	 *
	 * @return mixed Meta value
	 */
	public function get_meta( $key, $fallback = false ) {
		if ( $this->has_meta( $key ) ) {
			return $this->meta[ $key ];
		}

		return $fallback;
	}

	/**
	 * Check whether a meta key has been set
	 *
	 * @param string $key Meta key to check.
	 *
	 * @return bool
	 */
	public function has_meta( $key ) {
		return isset( $this->meta[ $key ] );
	}

	/**
	 * Gets a specific issue by its key
	 *
	 * @param string $key Issue's unique key.
	 *
	 * @return array Issue info hash
	 */
	public function get_issue( $key ) {
		return ! empty( $this->items[ $key ] ) && is_array( $this->items[ $key ] )
			? $this->items[ $key ]
			: array();
	}

	/**
	 * Gets all known state messages
	 *
	 * @return array
	 */
	public function get_state_messages() {
		return ! empty( $this->state_messages ) && is_array( $this->state_messages )
			? $this->state_messages
			: array();
	}

	/**
	 * Checks whether we have any state messages
	 *
	 * @return bool
	 */
	public function has_state_messages() {
		return ! empty( $this->state_messages );
	}

	/**
	 * Gets the start timestamp.
	 *
	 * @return int
	 */
	public function get_start_timestamp() {
		return $this->start_timestamp;
	}

	/**
	 * Sets the start timestamp.
	 *
	 * @param int $start_timestamp Start timestamp.
	 */
	public function set_start_timestamp( $start_timestamp ) {
		$this->start_timestamp = $start_timestamp;
	}

	/**
	 * Prevents cloning of the instance.
	 */
	private function __clone() {
	}

	/**
	 * Checks if a report is in progress.
	 *
	 * @return bool
	 */
	public function is_in_progress() {
		return $this->in_progress;
	}

	/**
	 * Sets the in-progress state of the report.
	 *
	 * @param bool $in_progress In-progress state.
	 */
	public function set_in_progress( $in_progress ) {
		$this->in_progress = $in_progress;
	}

	/**
	 * Gets the progress of the report.
	 *
	 * @return int
	 */
	public function get_progress() {
		return $this->progress;
	}

	/**
	 * Sets the progress of the report.
	 *
	 * @param int $progress Progress value.
	 */
	public function set_progress( $progress ) {
		$this->progress = $progress;
	}

	/**
	 * Checks if the report has data.
	 *
	 * @return bool
	 */
	public function has_data() {
		// Check if the meta has been set already or we have some error messages to show.
		return (bool) (
			array_filter( $this->meta )
			|| array_filter( $this->state_messages )
		);
	}

	/**
	 * Gets the redirect for a given path.
	 *
	 * @param string $path Path to check for redirect.
	 *
	 * @return string Redirect destination
	 */
	private function get_redirect( $path ) {
		$redirect = $this->redirects_table->get_redirect_by_source( $path );

		return $redirect ? $redirect->get_destination() : '';
	}
}