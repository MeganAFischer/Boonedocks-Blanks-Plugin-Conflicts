<?php
/**
 * Woocommerce\_Review\_Factory class for handling WooCommerce review schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class Woocommerce\_Review\_Factory
 *
 * Handles WooCommerce review schema fragments.
 */
class Woocommerce_Review_Factory extends Factory {

	/**
	 * The comment associated with the review.
	 *
	 * @var mixed
	 */
	private $comment;

	/**
	 * Woocommerce\_Review\_Factory constructor.
	 *
	 * @param mixed $post The post associated with the review.
	 * @param mixed $comment The comment associated with the review.
	 */
	public function __construct( $post, $comment ) {
		parent::__construct( $post );
		$this->comment = $comment;
	}

	/**
	 * Creates a schema source based on the provided parameters.
	 *
	 * @param string $source The source type.
	 * @param string $field The field name.
	 * @param string $type The type of the field.
	 *
	 * @return Author|Media|Options|Post|Post_Meta|Schema_Settings|SEO_Meta|Site_Settings|Text|Woocommerce|Woocommerce_Review The created schema source.
	 */
	public function create( $source, $field, $type ) {
		if ( empty( $this->comment ) ) {
			return $this->create_default_source();
		}

		if ( Woocommerce_Review::ID === $source ) {
			return new Woocommerce_Review( $this->comment, $field );
		}

		return parent::create( $source, $field, $type );
	}
}