<?php
/**
 * Item class for managing redirect items.
 *
 * @package SmartCrawl\Modules\Advanced\Redirects
 */

namespace SmartCrawl\Modules\Advanced\Redirects;

/**
 * Class Item
 *
 * Represents a redirect item with various properties and methods to manage it.
 */
class Item {

	/**
	 * Item ID.
	 *
	 * @var int
	 */
	private $id = 0;

	/**
	 * Item title.
	 *
	 * @var string
	 */
	private $title = '';

	/**
	 * Source URL.
	 *
	 * @var string
	 */
	private $source = '';

	/**
	 * Path.
	 *
	 * @var string
	 */
	private $path = '';

	/**
	 * Destination URL.
	 *
	 * @var string
	 */
	private $destination = '';

	/**
	 * Redirect type.
	 *
	 * @var int
	 */
	private $type = 0;

	/**
	 * Options for the redirect.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Rules for the redirect.
	 *
	 * @var array
	 */
	private $rules = array();

	/**
	 * Get the item ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the item ID.
	 *
	 * @param int $id Item ID.
	 *
	 * @return Item
	 */
	public function set_id( $id ) {
		$this->id = (int) $id;

		return $this;
	}

	/**
	 * Get the item title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Set the item title.
	 *
	 * @param string $title Title.
	 *
	 * @return Item
	 */
	public function set_title( $title ) {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get the source URL.
	 *
	 * @return string
	 */
	public function get_source() {
		return $this->source;
	}

	/**
	 * Set the source URL.
	 *
	 * @param string $source Source.
	 *
	 * @return Item
	 */
	public function set_source( $source ) {
		$this->source = $source;

		return $this;
	}

	/**
	 * Get the path.
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Set the path.
	 *
	 * @param string $path Path.
	 *
	 * @return Item
	 */
	public function set_path( $path ) {
		$this->path = $path;

		return $this;
	}

	/**
	 * Get the destination URL.
	 *
	 * @return string
	 */
	public function get_destination() {
		return $this->destination;
	}

	/**
	 * Retrieves absolute url from destination.
	 *
	 * @return string | false
	 */
	public function get_absolute_destination() {
		$destination = $this->get_destination();

		if ( ! empty( $destination['id'] ) ) {
			$destination = get_permalink( $destination['id'] );

			if ( ! $destination ) {
				return false;
			}
		}

		if ( strpos( $destination, '/' ) === 0 ) {
			return home_url() . $destination;
		}

		return $destination;
	}

	/**
	 * Sets redirect destination.
	 *
	 * @param array|string $destination Destination.
	 *
	 * @return Item
	 */
	public function set_destination( $destination ) {
		if ( in_array( $this->get_type(), Utils::get()->get_non_redirect_types(), true ) ) {
			$this->destination = '';
		} else {
			$this->destination = $destination;
		}

		return $this;
	}

	/**
	 * Get the options for the redirect.
	 *
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Set the options for the redirect.
	 *
	 * @param array $options Options.
	 *
	 * @return Item
	 */
	public function set_options( $options ) {
		$this->options = empty( $options ) || ! is_array( $options )
			? array()
			: $options;

		return $this;
	}

	/**
	 * Check if the redirect is a regex.
	 *
	 * @return bool
	 */
	public function is_regex() {
		return array_search( 'regex', $this->get_options(), true ) !== false;
	}

	/**
	 * Get the redirect type.
	 *
	 * @return int
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Set the redirect type.
	 *
	 * @param int $type Type.
	 *
	 * @return Item
	 */
	public function set_type( $type ) {
		$this->type = (int) $type;

		return $this;
	}

	/**
	 * Retrieves redirect rules.
	 *
	 * @return array
	 */
	public function get_rules() {
		return $this->rules;
	}

	/**
	 * Sets redirect rules.
	 *
	 * @param array $rules Rules.
	 *
	 * @return Item
	 */
	public function set_rules( $rules ) {
		if ( in_array( $this->get_type(), Utils::get()->get_non_redirect_types(), true ) ) {
			$this->rules = array();
		} else {
			$this->rules = $rules;
		}

		return $this;
	}

	/**
	 * Deflate the item to an array.
	 *
	 * @return array
	 */
	public function deflate() {
		return array(
			'id'          => $this->id,
			'title'       => $this->title,
			'source'      => $this->source,
			'path'        => $this->path,
			'destination' => $this->destination,
			'type'        => $this->type,
			'options'     => $this->options,
			'rules'       => $this->rules,
		);
	}

	/**
	 * Inflate the item from an array.
	 *
	 * @param array $data Data to inflate from.
	 *
	 * @return Item
	 */
	public static function inflate( $data ) {
		return ( new self() )
			->set_id( (int) \smartcrawl_get_array_value( $data, 'id' ) )
			->set_title( \smartcrawl_clean( \smartcrawl_get_array_value( $data, 'title' ) ) )
			->set_source( \smartcrawl_clean( \smartcrawl_get_array_value( $data, 'source' ) ) )
			->set_path( \smartcrawl_clean( \smartcrawl_get_array_value( $data, 'path' ) ) )
			->set_destination( \smartcrawl_clean( \smartcrawl_get_array_value( $data, 'destination' ) ) )
			->set_options( \smartcrawl_clean( \smartcrawl_get_array_value( $data, 'options' ) ) )
			->set_type( (int) \smartcrawl_get_array_value( $data, 'type' ) )
			->set_rules( \smartcrawl_clean( \smartcrawl_get_array_value( $data, 'rules' ) ) );
	}
}