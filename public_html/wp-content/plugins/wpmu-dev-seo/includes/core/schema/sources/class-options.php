<?php
/**
 * Options class for handling options schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class Options
 *
 * Handles options schema fragments.
 */
class Options extends Property {
	const ID = 'options';

	/**
	 * The option value.
	 *
	 * @var mixed
	 */
	private $option;

	/**
	 * The type of the option.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Options constructor.
	 *
	 * @param mixed  $option The option value.
	 * @param string $type The type of the option.
	 */
	public function __construct( $option, $type ) {
		parent::__construct();

		$this->option = $option;
		$this->type   = $type;
	}

	/**
	 * Retrieves the value of the option.
	 *
	 * @return string The value of the option.
	 */
	public function get_value() {
		if ( 'Array' !== $this->type && is_array( $this->option ) ) {
			return join( ',', $this->option );
		}

		return $this->option;
	}
}