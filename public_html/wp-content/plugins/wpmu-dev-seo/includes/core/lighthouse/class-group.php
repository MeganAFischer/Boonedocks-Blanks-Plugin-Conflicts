<?php
/**
 * Group class for managing a collection of checks in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse;

/**
 * Group class.
 *
 * Manages a collection of checks in SmartCrawl.
 */
class Group {
	/**
	 * Array of checks in the group.
	 *
	 * @var Checks\Check[]
	 */
	private $checks = array();
	/**
	 * Label of the group.
	 *
	 * @var string
	 */
	private $label;
	/**
	 * Description of the group.
	 *
	 * @var string
	 */
	private $description;
	/**
	 * ID of the group.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Constructor for the Group class.
	 *
	 * @param string $id The ID of the group.
	 * @param string $label The label of the group.
	 * @param string $description The description of the group.
	 * @param array  $checks The checks in the group.
	 */
	public function __construct( $id, $label, $description, $checks ) {
		$this->id          = $id;
		$this->label       = $label;
		$this->description = $description;

		foreach ( $checks as $check_id ) {
			$check                     = Checks\Check::create( $check_id );
			$this->checks[ $check_id ] = $check;
		}
	}

	/**
	 * Gets the checks in the group.
	 *
	 * @return Checks\Check[]
	 */
	public function get_checks() {
		return $this->checks;
	}

	/**
	 * Gets a specific check by ID.
	 *
	 * @param string $check_id The ID of the check.
	 *
	 * @return Checks\Check
	 */
	public function get_check( $check_id ) {
		return \smartcrawl_get_array_value( $this->checks, $check_id );
	}

	/**
	 * Gets the label of the group.
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Gets the description of the group.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Gets the count of failing checks in the group.
	 *
	 * @return int
	 */
	public function get_failing_count() {
		$failing_count = 0;
		foreach ( $this->checks as $check ) {
			if ( ! $check->is_passed() ) {
				++$failing_count;
			}
		}
		return $failing_count;
	}

	/**
	 * Gets the ID of the group.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}
}