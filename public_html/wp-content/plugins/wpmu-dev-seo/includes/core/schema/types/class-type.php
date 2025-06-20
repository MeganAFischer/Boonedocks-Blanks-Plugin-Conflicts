<?php
/**
 * Type class for handling schema types in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Types;

/**
 * Class Type
 *
 * Handles schema types.
 */
class Type {

	/**
	 * Schema utilities.
	 *
	 * @var \SmartCrawl\Schema\Utils
	 */
	protected $utils;

	/**
	 * The post object.
	 *
	 * @var \WP_Post
	 */
	protected $post;

	/**
	 * The schema type.
	 *
	 * @var array
	 */
	protected $type;

	/**
	 * Whether the post is the front page.
	 *
	 * @var bool
	 */
	private $is_front_page;

	/**
	 * Type constructor.
	 *
	 * @param array    $type The schema type.
	 * @param \WP_Post $post The post object.
	 * @param bool     $is_front_page Whether the post is the front page.
	 */
	private function __construct( $type, $post, $is_front_page ) {
		$this->utils         = \SmartCrawl\Schema\Utils::get();
		$this->type          = $type;
		$this->post          = $post;
		$this->is_front_page = $is_front_page;
	}

	/**
	 * Checks if the conditions are met.
	 *
	 * @return bool True if conditions are met, false otherwise.
	 */
	public function conditions_met() {
		$conditions = \smartcrawl_get_array_value( $this->type, 'conditions' );
		if ( is_null( $conditions ) ) {
			return false;
		}

		$conditions_helper = new \SmartCrawl\Schema\Type_Conditions( $conditions, $this->post, $this->is_front_page );

		return $conditions_helper->met();
	}

	/**
	 * Retrieves the schema type.
	 *
	 * @return mixed|null The schema type.
	 */
	public function get_type() {
		return \smartcrawl_get_array_value( $this->type, 'type' );
	}

	/**
	 * Retrieves the schema data.
	 *
	 * @return array The schema data.
	 */
	public function get_schema() {
		$type       = $this->get_type();
		$properties = \smartcrawl_get_array_value( $this->type, 'properties' );

		if ( is_null( $type ) || is_null( $properties ) ) {
			return array();
		}

		$factory               = new \SmartCrawl\Schema\Sources\Factory( $this->post );
		$property_value_helper = new \SmartCrawl\Schema\Property_Values( $factory, $this->post );
		$property_values       = $property_value_helper->get_property_values( $properties );

		if ( empty( $property_values ) ) {
			return array();
		}

		return array_merge(
			array( '@type' => $type ),
			$property_values
		);
	}

	/**
	 * Checks if the schema type is active.
	 *
	 * @return bool True if active, false otherwise.
	 */
	public function is_active() {
		return ! \smartcrawl_get_array_value( $this->type, 'disabled' );
	}

	/**
	 * Creates a new schema type instance.
	 *
	 * @param array    $data The schema type data.
	 * @param \WP_Post $post The post object.
	 * @param bool     $is_front_page Whether the post is the front page.
	 *
	 * @return Type|Woo_Product The schema type instance.
	 */
	public static function create( $data, $post, $is_front_page ) {
		$type = \smartcrawl_get_array_value( $data, 'type' );

		switch ( $type ) {
			case Woo_Product::TYPE:
			case Woo_Product::TYPE_SIMPLE:
			case Woo_Product::TYPE_VARIABLE:
				return new Woo_Product( $data, $post, false );

			default:
				return new self( $data, $post, $is_front_page );
		}
	}
}