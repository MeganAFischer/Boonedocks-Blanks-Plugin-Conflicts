<?php
/**
 * Lighthouse report class.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse;

/**
 * Class Report
 */
class Report {
	const GROUP_CONTENT    = 'content';
	const GROUP_VISIBILITY = 'visibility';
	const GROUP_RESPONSIVE = 'responsive';
	const GROUP_MANUAL     = 'manual';
	/**
	 * Score.
	 *
	 * @var int
	 */
	private $score = 0;
	/**
	 * Number of total audits.
	 *
	 * @var int
	 */
	private $total_audits_count = 0;
	/**
	 * Number of passed audits.
	 *
	 * @var int
	 */
	private $passed_audits_count = 0;
	/**
	 * Audits groups as an array.
	 *
	 * @var Group[]
	 */
	private $groups = array();
	/**
	 * Timestamp.
	 *
	 * @var string
	 */
	private $timestamp = '';
	/**
	 * Error.
	 *
	 * @var \WP_Error
	 */
	private $errors;
	/**
	 * Screenshot
	 *
	 * @var string
	 */
	private $screenshot = '';
	/**
	 * Screenshot width.
	 *
	 * @var int
	 */
	private $screenshot_width = 0;
	/**
	 * Screenshot height.
	 *
	 * @var int
	 */
	private $screenshot_height = 0;
	/**
	 * Screenshot nodes.
	 *
	 * @var array
	 */
	private $screenshot_nodes = array();
	/**
	 * Device. ie: Desktop or mobile.
	 *
	 * @var string
	 */
	private $device;

	/**
	 * Class constructor.
	 *
	 * @param string $device Device label.
	 */
	public function __construct( $device ) {
		$this->errors = new \WP_Error();
		$this->device = $device;

		$this->populate_groups();
	}

	/**
	 * Format report data from raw data.
	 *
	 * @param array $raw_report Raw report data.
	 *
	 * @return void
	 */
	public function populate( $raw_report ) {
		$this->score   = empty( $raw_report['seo_score'] ) ? 0 : $raw_report['seo_score'];
		$metrics       = empty( $raw_report['metrics'] ) ? array() : $raw_report['metrics'];
		$passed_checks = 0;
		$total_checks  = 0;

		$this->populate_screenshot_data( $metrics );

		foreach ( $this->groups as $group ) {
			foreach ( $group->get_checks() as $check ) {
				$metric = \smartcrawl_get_array_value( $metrics, $check->get_id() );

				$score  = \smartcrawl_get_array_value( $metric, 'score' );
				$passed = null === $score || 1 === $score; // Set passed to true when score is either not available or is 1.
				$check->set_passed( $passed );

				$weight = \smartcrawl_get_array_value( $metric, 'weight' );
				$check->set_weight( $weight );

				$check->prepare();

				if ( $passed ) {
					++$passed_checks;
				}
				++$total_checks;
			}
		}

		$this->total_audits_count  = $total_checks;
		$this->passed_audits_count = $passed_checks;
	}

	/**
	 * Populate groups.
	 *
	 * @return void
	 */
	private function populate_groups() {
		$this->groups[ self::GROUP_CONTENT ] = new Group(
			self::GROUP_CONTENT,
			esc_html__( 'Content audits', 'wds' ),
			esc_html__( 'Make sure search engines understand your content.', 'wds' ),
			array(
				Checks\Document_Title::ID,
				Checks\Meta_Description::ID,
				Checks\Link_Text::ID,
				Checks\Hreflang::ID,
				Checks\Canonical::ID,
				Checks\Image_Alt::ID,
			)
		);

		$this->groups[ self::GROUP_VISIBILITY ] = new Group(
			self::GROUP_VISIBILITY,
			esc_html__( 'Crawling and indexing audits', 'wds' ),
			esc_html__( 'Make sure search engines can crawl and index your page.', 'wds' ),
			array(
				Checks\Http_Status_Code::ID,
				Checks\Is_Crawlable::ID,
				Checks\Robots_Txt::ID,
				Checks\Plugins::ID,
				Checks\Crawlable_Anchors::ID,
			)
		);

		$this->groups[ self::GROUP_RESPONSIVE ] = new Group(
			self::GROUP_RESPONSIVE,
			esc_html__( 'Responsive audits', 'wds' ),
			esc_html__( 'Make your page mobile friendly.', 'wds' ),
			array(
				Checks\Viewport::ID,
				Checks\Font_Size::ID,
				Checks\Tap_Targets::ID,
			)
		);

		$this->groups[ self::GROUP_MANUAL ] = new Group(
			self::GROUP_MANUAL,
			esc_html__( 'Manual audits', 'wds' ),
			esc_html__( 'The Lighthouse structured data audit is manual, so it does not affect your Lighthouse SEO score.', 'wds' ),
			array( Checks\Structured_Data::ID )
		);
	}

	/**
	 * Return groups.
	 *
	 * @return Group[]
	 */
	public function get_groups() {
		return $this->groups;
	}

	/**
	 * Get group by id.
	 *
	 * @param string $group_id Group id.
	 *
	 * @return Group
	 */
	public function get_group( $group_id ) {
		return \smartcrawl_get_array_value( $this->groups, $group_id );
	}

	/**
	 * Get check from group.
	 *
	 * @param string $group_id Group id.
	 * @param string $check_id Check id.
	 *
	 * @return Checks\Check|null
	 */
	public function get_check( $group_id, $check_id ) {
		$group = $this->get_group( $group_id );
		if ( ! $group ) {
			return null;
		}

		return $group->get_check( $check_id );
	}

	/**
	 * Return test score.
	 *
	 * @return int
	 */
	public function get_score() {
		return $this->score;
	}

	/**
	 * Get grade from score.
	 *
	 * @return string
	 */
	public function get_score_grade() {
		$score = $this->get_score();
		if ( $score >= 90 ) {
			$grade = 'a';
		} elseif ( $score >= 50 ) {
			$grade = 'c';
		} else {
			$grade = 'f';
		}

		return $grade;
	}

	/**
	 * Return number of failed audits.
	 *
	 * @return int
	 */
	public function get_failed_audits_count() {
		return $this->total_audits_count - $this->passed_audits_count;
	}

	/**
	 * Return number of total audits.
	 *
	 * @return int
	 */
	public function get_total_audits_count() {
		return $this->total_audits_count;
	}

	/**
	 * Return number of passed audits.
	 *
	 * @return int
	 */
	public function get_passed_audits_count() {
		return $this->passed_audits_count;
	}

	/**
	 * Return if testing is still cooling down. Used to re-run the test.
	 *
	 * @return bool
	 */
	public function is_cooling_down() {
		return $this->is_fresh();
	}

	/**
	 * Check if testing is fresh. If it's true, testing can be run again.
	 *
	 * @return bool
	 */
	public function is_fresh() {
		if ( ! $this->has_data() ) {
			return false;
		}

		$last_checked = $this->get_timestamp();

		return ( time() - $last_checked ) / 60 < 5;
	}

	/**
	 * Return remaining cool-down as minutes.
	 *
	 * @return float
	 */
	public function get_remaining_cooldown_minutes() {
		if ( ! $this->is_cooling_down() ) {
			return 0;
		}

		$minutes_since_last_scan = ( time() - $this->get_timestamp() ) / 60;

		return ceil( 5 - $minutes_since_last_scan );
	}

	/**
	 * Get last checked date.
	 *
	 * @param string|false $format Date format.
	 *
	 * @return string|false
	 */
	public function get_last_checked( $format = false ) {
		$time = $this->get_timestamp();
		if ( empty( $time ) ) {
			return '';
		}

		if ( empty( $format ) ) {
			return sprintf(
				/* translators: 1: Date, 2: Time */
				esc_html__( '%1$s at %2$s', 'wds' ),
				wp_date( get_option( 'date_format' ), $time ),
				wp_date( get_option( 'time_format' ), $time )
			);
		}

		return wp_date( $format, $time );
	}

	/**
	 * Return status message based on score.
	 *
	 * @return string
	 */
	public function get_status_message() {
		if ( 100 === $this->score ) {
			return esc_html__( 'Excellent! Your site is fully optimized!', 'wds' );
		} elseif ( $this->score > 89 ) {
			return esc_html__( 'Follow the pending SEO audits for a perfect SEO score.', 'wds' );
		} elseif ( $this->score > 49 ) {
			return esc_html__( 'You can improve your score by following the outstanding SEO audits.', 'wds' );
		} else {
			return esc_html__( 'You need to improve your score by following the outstanding SEO audits.', 'wds' );
		}
	}

	/**
	 * Check if valid report data is existing.
	 *
	 * @return bool
	 */
	public function has_data() {
		return ! empty( $this->timestamp );
	}

	/**
	 * Return timestamp value.
	 *
	 * @return int
	 */
	public function get_timestamp() {
		return (int) $this->timestamp;
	}

	/**
	 * Set timestamp.
	 *
	 * @param string $timestamp Timestamp.
	 */
	public function set_timestamp( $timestamp ) {
		$this->timestamp = $timestamp;
	}

	/**
	 * Check if error exists.
	 *
	 * @return bool
	 */
	public function has_errors() {
		return $this->errors->has_errors();
	}

	/**
	 * Set error.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param mixed  $data Error data.
	 *
	 * @return void
	 */
	public function set_error( $code, $message, $data = null ) {
		$this->errors->add( $code, $message, $data );
	}

	/**
	 * Return error code.
	 *
	 * @return int|string
	 */
	public function get_error_code() {
		return $this->errors->get_error_code();
	}

	/**
	 * Return error message.
	 *
	 * @return string
	 */
	public function get_error_message() {
		return $this->errors->get_error_message();
	}

	/**
	 * Set screenshot.
	 *
	 * @param string $screenshot Screenshot data.
	 *
	 * @return void
	 */
	public function set_screenshot( $screenshot ) {
		$this->screenshot = $screenshot;
	}

	/**
	 * Gets the screenshot data.
	 *
	 * @return string
	 */
	public function get_screenshot() {
		return $this->screenshot;
	}

	/**
	 * Gets the screenshot width.
	 *
	 * @return int
	 */
	public function get_screenshot_width() {
		return $this->screenshot_width;
	}

	/**
	 * Set screenshot width.
	 *
	 * @param int $screenshot_width Screenshot width.
	 */
	public function set_screenshot_width( $screenshot_width ) {
		$this->screenshot_width = $screenshot_width;
	}

	/**
	 * Get screenshot height.
	 *
	 * @return int
	 */
	public function get_screenshot_height() {
		return $this->screenshot_height;
	}

	/**
	 * Set screenshot height.
	 *
	 * @param int $screenshot_height Screenshot height.
	 */
	public function set_screenshot_height( $screenshot_height ) {
		$this->screenshot_height = $screenshot_height;
	}

	/**
	 * Set screenshot nodes.
	 *
	 * @param array $nodes Nodes.
	 *
	 * @return void
	 */
	private function set_screenshot_nodes( $nodes ) {
		$this->screenshot_nodes = empty( $nodes )
			? array()
			: $nodes;
	}

	/**
	 * Get screenshot node.
	 *
	 * @param string $id Node id.
	 *
	 * @return array|mixed
	 */
	public function get_screenshot_node( $id ) {
		$node = \smartcrawl_get_array_value( $this->screenshot_nodes, $id );

		return empty( $node )
			? array()
			: $node;
	}

	/**
	 * Generate screenshot data.
	 *
	 * @param array $metrics Metrics.
	 *
	 * @return void
	 */
	private function populate_screenshot_data( $metrics ) {
		$screenshot_details = \smartcrawl_get_array_value(
			$metrics,
			array(
				'full-page-screenshot',
				'details',
			)
		);

		$nodes = \smartcrawl_get_array_value( $screenshot_details, 'nodes' );
		$this->set_screenshot_nodes( $nodes );

		$screenshot = \smartcrawl_get_array_value( $screenshot_details, 'screenshot' );
		if (
			! empty( $screenshot['width'] )
			&& ! empty( $screenshot['height'] )
			&& ! empty( $screenshot['data'] )
		) {
			$this->set_screenshot_width( $screenshot['width'] );
			$this->set_screenshot_height( $screenshot['height'] );
			$this->set_screenshot( $screenshot['data'] );
		}
	}

	/**
	 * Get device.
	 *
	 * @return string
	 */
	public function get_device() {
		return $this->device;
	}

	/**
	 * Set device.
	 *
	 * @param string $device Device text.
	 *
	 * @return void
	 */
	public function set_device( $device ) {
		$this->device = $device;
	}
}