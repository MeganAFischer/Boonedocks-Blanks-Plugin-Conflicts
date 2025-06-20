<?php
/**
 * Breadcrumb builder for archives.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs\Builders;

/**
 * Archives breadcrumb class.
 */
class Archives extends Builder {

	/**
	 * Build items for breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_items() {
		$this->reset_items();

		if ( is_day() ) {
			$this->prepare_day_items();
		} elseif ( is_month() ) {
			$this->prepare_month_items();
		} elseif ( is_year() ) {
			$this->prepare_year_items();
		} elseif ( is_author() ) {
			$this->prepare_author_items();
		}
	}

	/**
	 * Build items for day archive breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_day_items() {
		$year  = get_the_time( 'Y' );
		$month = get_the_time( 'm' );
		$day   = get_the_time( 'j' );

		// Set current term to crumb.
		$this->add_item_with_paged(
			array(
				'link'  => get_day_link( $year, $month, $day ),
				'title' => $this->get_title( get_the_time( 'F j, Y' ) ),
			)
		);
	}

	/**
	 * Build items for month archive breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_month_items() {
		$month = get_the_time( 'm' );
		$year  = get_the_time( 'Y' );

		// Set current term to crumb.
		$this->add_item_with_paged(
			array(
				'link'  => get_month_link( $year, $month ),
				'title' => $this->get_title( get_the_time( 'F Y' ) ),
			)
		);
	}

	/**
	 * Build items for year archive breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_year_items() {
		$year = get_the_time( 'Y' );

		// Set current term to crumb.
		$this->add_item_with_paged(
			array(
				'link'  => get_year_link( $year ),
				'title' => $this->get_title( $year ),
			)
		);
	}

	/**
	 * Build items for breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_author_items() {
		$this->reset_items();

		global $author;

		$author_data = get_userdata( $author );

		// Set current term to crumb.
		$this->add_item_with_paged(
			array(
				'link'  => get_author_posts_url( $author ),
				'title' => $this->get_title( $author_data->user_nicename ),
			)
		);
	}

	/**
	 * Get the title for archive item.
	 *
	 * @since 3.5.0
	 *
	 * @param string $item_title Item title.
	 *
	 * @return string
	 */
	private function get_title( $item_title ) {
		return $this->get_label(
			'archive',
			// translators: %s archive item title.
			sprintf( __( 'Archive for %s', 'wds' ), $item_title )
		);
	}
}