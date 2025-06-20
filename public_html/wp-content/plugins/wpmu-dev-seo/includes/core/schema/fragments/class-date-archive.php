<?php
/**
 * Date_Archive class for handling date archive schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Date_Archive
 *
 * Handles date archive schema fragments.
 */
class Date_Archive extends Fragment {

	/**
	 * The year of the archive.
	 *
	 * @var int
	 */
	private $year;

	/**
	 * The month of the archive.
	 *
	 * @var int
	 */
	private $month;

	/**
	 * The posts in the archive.
	 *
	 * @var array
	 */
	private $posts;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The title of the archive.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the archive.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Date_Archive constructor.
	 *
	 * @param int    $year The year of the archive.
	 * @param int    $month The month of the archive.
	 * @param array  $posts The posts in the archive.
	 * @param string $title The title of the archive.
	 * @param string $description The description of the archive.
	 */
	public function __construct( $year, $month, $posts, $title, $description ) {
		$this->year        = $year;
		$this->month       = $month;
		$this->posts       = $posts;
		$this->title       = $title;
		$this->description = $description;
		$this->utils       = Utils::get();
	}

	/**
	 * Retrieves raw schema data for the date archive.
	 *
	 * @return array|mixed|Archive The raw schema data.
	 */
	protected function get_raw() {
		$enabled          = (bool) $this->utils->get_schema_option( 'schema_enable_date_archives' );
		$requested_year   = $this->year;
		$requested_month  = $this->month;
		$date_callback    = ! empty( $requested_year ) && empty( $requested_month )
			? 'get_year_link'
			: 'get_month_link';
		$date_archive_url = $date_callback( $requested_year, $requested_month );

		if ( $enabled ) {
			return new Archive(
				'CollectionPage',
				$date_archive_url,
				$this->posts,
				$this->title,
				$this->description
			);
		} else {
			$custom_schema_types = $this->utils->get_custom_schema_types();
			if ( $custom_schema_types ) {
				return $this->utils->add_custom_schema_types(
					array(),
					$custom_schema_types,
					$this->utils->get_webpage_id( $date_archive_url )
				);
			} else {
				return array();
			}
		}
	}
}