<?php
/**
 * Class for checking if the document has a valid rel=canonical.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

/**
 * Canonical class.
 *
 * Checks if the document has a valid rel=canonical.
 */
class Canonical extends Check {
	const ID = 'canonical';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( 'Document has a valid rel=canonical', 'wds' ) );
		$this->set_failure_title( esc_html__( 'Document does not have a valid rel=canonical', 'wds' ) );
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