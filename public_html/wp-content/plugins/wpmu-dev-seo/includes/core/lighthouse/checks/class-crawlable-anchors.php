<?php
/**
 * Class for checking if the links are crawlable.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

/**
 * Crawlable_Anchors class.
 *
 * Checks if the links are crawlable.
 */
class Crawlable_Anchors extends Check {
	const ID = 'crawlable-anchors';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Links are crawlable', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Links are not crawlable', 'wds' ) );
	}

	/**
	 * Gets the ID of the check.
	 *
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}
}