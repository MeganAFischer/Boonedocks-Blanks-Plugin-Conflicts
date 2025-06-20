<?php
/**
 * Property class for handling schema properties in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

use SmartCrawl\Schema\Utils;

/**
 * Class Property
 *
 * Abstract class for schema properties.
 */
abstract class Property {

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	protected $utils;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->utils = Utils::get();
	}

	/**
	 * Retrieves the value of the property.
	 *
	 * @return mixed The value of the property.
	 */
	abstract public function get_value();
}