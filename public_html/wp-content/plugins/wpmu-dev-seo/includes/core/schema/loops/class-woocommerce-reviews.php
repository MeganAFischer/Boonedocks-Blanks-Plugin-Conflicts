<?php
/**
 * Woocommerce_Reviews class for handling WooCommerce reviews schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Loops;

/**
 * Class Woocommerce_Reviews
 *
 * Handles WooCommerce reviews schema fragments.
 */
class Woocommerce_Reviews extends Loop {
	const ID = 'woocommerce-reviews';

	/**
	 * The post object.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Woocommerce_Reviews constructor.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function __construct( $post ) {
		$this->post = $post;
	}

	/**
	 * Retrieves the property value for the given property.
	 *
	 * @param string $property The property to retrieve the value for.
	 *
	 * @return array The property values.
	 */
	public function get_property_value( $property ) {
		if ( empty( $this->post ) ) {
			return array();
		}

		$schema = array();
		foreach ( $this->get_comments() as $comment ) {
			$factory               = new \SmartCrawl\Schema\Sources\Woocommerce_Review_Factory( $this->post, $comment );
			$property_value_helper = new \SmartCrawl\Schema\Property_Values( $factory, $this->post );
			$schema[]              = $property_value_helper->get_property_value( $property );
		}

		return $schema;
	}

	/**
	 * Retrieves the comments for the post.
	 *
	 * @return array|int The comments for the post.
	 */
	private function get_comments() {
		return get_comments(
			array(
				'number'     => 10,
				'post_id'    => $this->post->ID,
				'status'     => 'approve',
				'post_type'  => 'product',
				'parent'     => 0,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'rating',
						'type'    => 'NUMERIC',
						'compare' => '>',
						'value'   => 0,
					),
				),
			)
		);
	}
}