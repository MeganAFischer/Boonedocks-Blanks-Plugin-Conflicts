<?php
/**
 * Links count check.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;

/**
 * Class instance to check Links_Count
 */
class Links_Count extends Check {

	/**
	 * Holds check state
	 *
	 * @var bool
	 */
	private $state;

	/**
	 * Link count.
	 *
	 * @var int
	 */
	private $link_count;

	/**
	 * Internal link count.
	 *
	 * @var int
	 */
	private $internal_link_count;

	/**
	 * Retrieves the message for the check.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_status_msg() {
		return $this->choose_message(
			/* translators: 1, 2: Number of internal/external links */
			__( 'You have %1$d internal and %2$d external links in your content', 'wds' ),
			__( "You haven't added any internal or external links in your content", 'wds' ),
			/* translators: 2: Number of external links */
			__( 'You have 0 internal and %2$d external links in your content', 'wds' )
		);
	}

	/**
	 * Select status message based on density.
	 *
	 * @param string $okay_message Ok message.
	 * @param string $no_links     No links message.
	 * @param string $no_internal  No internal message.
	 *
	 * @return string
	 */
	private function choose_message( $okay_message, $no_links, $no_internal ) {
		$total_count    = (int) $this->link_count;
		$internal_count = (int) $this->internal_link_count;
		$external_count = $total_count - $internal_count;

		if ( $this->state ) {
			$message = $okay_message;
		} elseif ( ! $total_count ) {
			$message = $no_links;
		} elseif ( ! $internal_count ) {
			$message = $no_internal;
		}

		return sprintf( $message, (int) $internal_count, (int) $external_count, (int) $total_count );
	}

	/**
	 * Applies check to the subject.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function apply() {
		$selector_links          = 'a[href]';
		$selector_internal_links = sprintf(
			'a[href^="%s"],a[href^="/"],a[href^="#"]',
			site_url()
		);

		$links            = Html::find( $selector_links, $this->get_markup() );
		$link_count       = count( $links );
		$this->link_count = $link_count;

		$internal_links            = Html::find( $selector_internal_links, $this->get_markup() );
		$internal_link_count       = count( $internal_links );
		$this->internal_link_count = $internal_link_count;

		$this->state = ! empty( $internal_link_count );

		return ! ! $this->state;
	}

	/**
	 * Retrieves check result.
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	public function get_result() {
		return array(
			'state'    => $this->state,
			'internal' => $this->internal_link_count,
			'total'    => $this->link_count,
		);
	}
}