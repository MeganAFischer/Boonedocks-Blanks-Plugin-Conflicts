<?php
/**
 * Text class for handling custom text schema sources in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class Text
 *
 * Handles custom text schema sources.
 */
class Text extends Property {

	/**
	 * The custom text identifier.
	 */
	const ID = 'custom_text';

	/**
	 * The text value.
	 *
	 * @var string
	 */
	private $text;

	/**
	 * Constructor.
	 *
	 * @param string $text The text value.
	 */
	public function __construct( $text ) {
		parent::__construct();
		$this->text = $text;
	}

	/**
	 * Retrieves the value of the text.
	 *
	 * @return string The text value.
	 */
	public function get_value() {
		return $this->text;
	}
}