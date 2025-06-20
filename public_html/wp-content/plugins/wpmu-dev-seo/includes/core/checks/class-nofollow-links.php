<?php
/**
 * No follow links check for external links.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;

/**
 * Class instance to check nofollow links
 */
class Nofollow_Links extends Check {

	/**
	 * Holds check state
	 *
	 * @var bool
	 */
	private $state;

	/**
	 * Retrieves the message for the check.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_status_msg() {
		return false === $this->state ?
			__( 'Nofollow external links', 'wds' ) :
			__( 'A dofollow external link(s) was found', 'wds' );
	}

	/**
	 * Applies the check to subject.
	 *
	 * @return bool
	 */
	public function apply() {
		$links = Html::find( 'a', $this->get_markup() );
		// If no link we don't need it.
		if ( empty( $links ) ) {
			$this->set_hidden();
			$this->state = true;

			return true;
		}

		$external_links          = 0;
		$external_nofollow_links = 0;

		foreach ( $links as $link ) {
			$url = $link->getAttribute( 'href' );
			$rel = $link->getAttribute( 'rel' );
			// Regex for external links.
			$regex = sprintf( '/^(?:%s|#|\/)/i', preg_quote( untrailingslashit( site_url() ), '/' ) );
			if ( ! preg_match( $regex, $url ) ) {
				++$external_links;
				// If nofollow.
				if ( strpos( $rel, 'nofollow' ) !== false ) {
					++$external_nofollow_links;
				}
			}
		}

		// No external links.
		if ( $external_links <= 0 ) {
			$this->set_hidden();
			$this->state = true;

			return true;
		}

		// The count should be different.
		$this->state = $external_nofollow_links !== $external_links;

		return $this->state;
	}

	/**
	 * Retrieves check result.
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	public function get_result() {
		return array( 'state' => $this->state );
	}
}