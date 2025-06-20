<?php
/**
 * Loop class for handling different types of loops in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Loops;

/**
 * Class Loop
 *
 * Abstract class for handling different types of loops.
 */
abstract class Loop {

	/**
	 * Creates a loop instance based on the given ID.
	 *
	 * @param string   $id The ID of the loop type.
	 * @param \WP_Post $post The post object.
	 *
	 * @return Loop|null The created loop instance or null if the ID is not recognized.
	 */
	public static function create( $id, $post ) {
		switch ( $id ) {
			case Woocommerce_Reviews::ID:
				return new Woocommerce_Reviews( $post );

			case Comments::ID:
				return new Comments( $post );

			default:
				return null;
		}
	}

	/**
	 * Retrieves the value of the specified property.
	 *
	 * @param string $property The property to retrieve the value for.
	 *
	 * @return mixed The value of the specified property.
	 */
	abstract public function get_property_value( $property );
}