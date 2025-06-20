<?php
/**
 * Abstract class for defining checks in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Lighthouse\Report;
use SmartCrawl\Lighthouse\Tables\Table;

/**
 * Abstract Check class.
 *
 * Provides a base for all checks in SmartCrawl.
 */
abstract class Check {

	/**
	 * Title displayed when the check passes.
	 *
	 * @var string
	 */
	private $success_title = '';

	/**
	 * Title displayed when the check fails.
	 *
	 * @var string
	 */
	private $failure_title = '';

	/**
	 * Indicates if the check passed.
	 *
	 * @var bool
	 */
	private $passed = false;

	/**
	 * Weight of the check.
	 *
	 * @var int
	 */
	private $weight;

	/**
	 * Constructor for the Check class.
	 */
	public function __construct() {}

	/**
	 * Gets the title based on the check result.
	 *
	 * @return string
	 */
	public function get_title() {
		if ( $this->is_passed() ) {
			return $this->success_title;
		} else {
			return $this->failure_title;
		}
	}

	/**
	 * Sets the success title.
	 *
	 * @param string $title The success title.
	 */
	public function set_success_title( $title ) {
		$this->success_title = $title;
	}

	/**
	 * Sets the failure title.
	 *
	 * @param string $title The failure title.
	 */
	public function set_failure_title( $title ) {
		$this->failure_title = $title;
	}

	/**
	 * Checks if the check passed.
	 *
	 * @return bool
	 */
	public function is_passed() {
		return $this->passed;
	}

	/**
	 * Sets the check result.
	 *
	 * @param bool $passed The check result.
	 */
	public function set_passed( $passed ) {
		$this->passed = $passed;
	}

	/**
	 * Gets the weight of the check.
	 *
	 * @return int
	 */
	public function get_weight() {
		return $this->weight;
	}

	/**
	 * Sets the weight of the check.
	 *
	 * @param int $weight The weight of the check.
	 *
	 * @return void
	 */
	public function set_weight( $weight ) {
		$this->weight = $weight;
	}

	/**
	 * Creates a check instance based on the ID.
	 *
	 * @param string $id The ID of the check.
	 *
	 * @return Check|null
	 */
	public static function create( $id ) {
		$available_checks = array(
			'\SmartCrawl\Lighthouse\Checks\Canonical',
			'\SmartCrawl\Lighthouse\Checks\Crawlable_Anchors',
			'\SmartCrawl\Lighthouse\Checks\Document_Title',
			'\SmartCrawl\Lighthouse\Checks\Font_Size',
			'\SmartCrawl\Lighthouse\Checks\Hreflang',
			'\SmartCrawl\Lighthouse\Checks\Http_Status_Code',
			'\SmartCrawl\Lighthouse\Checks\Image_Alt',
			'\SmartCrawl\Lighthouse\Checks\Is_Crawlable',
			'\SmartCrawl\Lighthouse\Checks\Link_Text',
			'\SmartCrawl\Lighthouse\Checks\Meta_Description',
			'\SmartCrawl\Lighthouse\Checks\Plugins',
			'\SmartCrawl\Lighthouse\Checks\Robots_Txt',
			'\SmartCrawl\Lighthouse\Checks\Tap_Targets',
			'\SmartCrawl\Lighthouse\Checks\Viewport',
			'\SmartCrawl\Lighthouse\Checks\Structured_Data',
		);

		foreach ( $available_checks as $check ) {
			if ( constant( "{$check}::ID" ) === $id ) {
				return new $check();
			}
		}

		return null;
	}

	/**
	 * Wraps a value in a span tag with a specific class.
	 *
	 * @param string $value The value to wrap.
	 *
	 * @return string
	 */
	public function tag( $value ) {
		return '<span class="wds-lh-tag">' . esc_html( $value ) . '</span>';
	}

	/**
	 * Wraps a value in a span tag with a specific class.
	 *
	 * @param string $value The value to wrap.
	 *
	 * @return string
	 */
	public function attr( $value ) {
		return '<span class="wds-lh-attr">' . esc_html( $value ) . '</span>';
	}

	/**
	 * Gets the ID of the check.
	 *
	 * @return mixed
	 */
	abstract public function get_id();

	/**
	 * Prepares the check.
	 *
	 * @return mixed
	 */
	abstract public function prepare();
}