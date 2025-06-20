<?php
/**
 * Date Archive Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

use SmartCrawl\Settings;

/**
 * Class Date_Archive
 */
class Date_Archive extends Entity_With_Archive {
	/**
	 * Archive Year.
	 *
	 * @var string
	 */
	private $year;
	/**
	 * Archive Month.
	 *
	 * @var string
	 */
	private $month;
	/**
	 * Archive Date.
	 *
	 * @var string
	 */
	private $date;
	/**
	 * Posts within the archive.
	 *
	 * @var \WP_Post[]
	 */
	private $posts;
	/**
	 * Page number.
	 *
	 * @var int
	 */
	private $page_number;

	/**
	 * Constructor for initializing the object.
	 *
	 * @param string     $year The year value.
	 * @param string     $month The month value (optional, default is empty string).
	 * @param string     $day The day value (optional, default is empty string).
	 * @param \WP_Post[] $posts The posts array (optional, default is empty array).
	 * @param int        $page_number The page number value (optional, default is 0).
	 *
	 * @return void
	 */
	public function __construct( $year, $month = '', $day = '', $posts = array(), $page_number = 0 ) {
		$this->year        = $year;
		$this->month       = $month;
		$this->date        = $day;
		$this->posts       = $posts;
		$this->page_number = $page_number;
	}

	/**
	 * Loads meta title.
	 *
	 * @return string Meta title.
	 */
	protected function load_meta_title() {
		return $this->load_option_string_value(
			'date',
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return '%%date%% %%sep%% %%sitename%%';
			}
		);
	}

	/**
	 * Loads meta description.
	 *
	 * @return string Meta description.
	 */
	protected function load_meta_description() {
		return $this->load_option_string_value(
			'date',
			array( $this, 'load_meta_desc_from_options' ),
			'__return_empty_string'
		);
	}

	/**
	 * Loads robots meta tag.
	 *
	 * @return string Robots meta tag value.
	 */
	protected function load_robots() {
		return $this->get_robots_for_page_number( $this->page_number );
	}

	/**
	 * Loads canonical URL.
	 *
	 * @return string Canonical URL if Buddypress group exists, otherwise returns an empty string.
	 */
	protected function load_canonical_url() {
		$requested_year   = $this->year;
		$requested_month  = $this->month;
		$date_callback    = ! empty( $requested_year ) && empty( $requested_month )
			? 'get_year_link'
			: 'get_month_link';
		$date_archive_url = $date_callback( $requested_year, $requested_month );

		$first_page_indexed   = $this->is_first_page_indexed();
		$current_page_indexed = ! $this->is_noindex();
		if ( $current_page_indexed ) {
			return $this->append_page_number( $date_archive_url, $this->page_number );
		} elseif ( $first_page_indexed ) {
				return $date_archive_url;
		} else {
			return '';
		}
	}

	/**
	 * Loads schema.
	 *
	 * @return array The schema array.
	 */
	protected function load_schema() {
		$fragment = new \SmartCrawl\Schema\Fragments\Date_Archive(
			$this->year,
			$this->month,
			$this->posts,
			$this->get_meta_title(),
			$this->get_meta_description()
		);

		return $fragment->get_schema();
	}

	/**
	 * Loads OpenGraph enabled value for BP Groups.
	 *
	 * @return bool Indicates if OpenGraph is enabled for BP Groups.
	 */
	protected function load_opengraph_enabled() {
		return $this->is_opengraph_enabled_for_location( 'date' );
	}

	/**
	 * Loads OpenGraph title.
	 *
	 * @return string OpenGraph title.
	 */
	protected function load_opengraph_title() {
		return $this->load_option_string_value(
			'date',
			array( $this, 'load_opengraph_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads OpenGraph description.
	 *
	 * @return string OpenGraph description.
	 */
	protected function load_opengraph_description() {
		return $this->load_option_string_value(
			'date',
			array( $this, 'load_opengraph_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads OpenGraph images for the groups.
	 *
	 * @return array Array of image URLs.
	 */
	protected function load_opengraph_images() {
		$images = $this->load_opengraph_images_from_options( 'date' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Loads enabled status for Twitter.
	 *
	 * @return bool
	 */
	protected function load_twitter_enabled() {
		return $this->is_twitter_enabled_for_location( 'date' );
	}

	/**
	 * Loads Twitter title.
	 *
	 * @return string Twitter title.
	 */
	protected function load_twitter_title() {
		return $this->load_option_string_value(
			'date',
			array( $this, 'load_twitter_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads Twitter description.
	 *
	 * @return string Twitter description.
	 */
	protected function load_twitter_description() {
		return $this->load_option_string_value(
			'date',
			array( $this, 'load_twitter_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads Twitter images.
	 *
	 * @return array List of Twitter image URLs.
	 */
	protected function load_twitter_images() {
		$images = $this->load_twitter_images_from_options( 'date' );
		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Returns the macros.
	 *
	 * @param string $subject The subject of the macros.
	 *
	 * @return array An array of macros.
	 */
	public function get_macros( $subject = '' ) {
		return array(
			'%%date%%'           => array( $this, 'get_date_for_archive' ),
			'%%archive-title%%'  => get_the_archive_title(),
			'%%original-title%%' => array( $this, 'get_date_for_archive' ),
		);
	}

	/**
	 * Retrieves the formatted date for the archive.
	 *
	 * @return string
	 */
	public function get_date_for_archive() {
		$date   = $this->date;
		$month  = $this->month;
		$year   = $this->year;
		$format = '';

		if ( empty( $year ) ) {
			// At the very least we need a year.
			return '';
		}

		$timestamp = mktime(
			0,
			0,
			0,
			empty( $month ) ? 1 : $month,
			empty( $date ) ? 1 : $date,
			$year
		);

		if ( ! empty( $date ) ) {
			$format = get_option( 'date_format' );
		} elseif ( ! empty( $month ) ) {
			$format = 'F Y';
		} elseif ( ! empty( $year ) ) {
			$format = 'Y';
		}

		// TODO: should we replace date_i18n with wp_date?.
		return date_i18n( $format, $timestamp );
	}

	/**
	 * Retrieves robots meta tag based on page number.
	 *
	 * @param int $page_number Page number.
	 *
	 * @return string
	 */
	protected function get_robots_for_page_number( $page_number ) {
		$options = Settings::get_options();

		if ( empty( $options['enable-date-archive'] ) ) {
			return 'noindex,follow';
		}

		$setting_key = 'date';

		if (
			$this->show_robots_on_subsequent_pages_only( $setting_key )
			&& $page_number < 2
		) {
			return '';
		}

		$noindex  = $this->get_noindex_setting( $setting_key ) ? 'noindex' : 'index';
		$nofollow = $this->get_nofollow_setting( $setting_key ) ? 'nofollow' : 'follow';

		return "{$noindex},{$nofollow}";
	}
}