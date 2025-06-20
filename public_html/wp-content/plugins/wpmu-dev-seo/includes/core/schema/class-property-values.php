<?php
/**
 * Property_Values class for handling property values in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema;

/**
 * Class Property_Values
 *
 * Handles property values.
 */
class Property_Values {

	/**
	 * Property source factory.
	 *
	 * @var \SmartCrawl\Schema\Sources\Factory
	 */
	private $property_source_factory;

	/**
	 * Context post.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Property_Values constructor.
	 *
	 * @param \SmartCrawl\Schema\Sources\Factory $property_source_factory The property source factory.
	 * @param \WP_Post                           $post The context post.
	 */
	public function __construct( $property_source_factory, $post ) {
		$this->property_source_factory = $property_source_factory;
		$this->post                    = $post;
	}

	/**
	 * Retrieves property values.
	 *
	 * @param array $properties The properties to retrieve values for.
	 *
	 * @return array The property values.
	 */
	public function get_property_values( $properties ) {
		$values = array();

		foreach ( $properties as $property_key => $property ) {
			$value = $this->get_property_value( $property );
			if ( ! empty( $value ) ) {
				$values[ $property_key ] = $value;
			}
		}

		return $values;
	}

	/**
	 * Checks if array keys are numeric.
	 *
	 * @param array $array The array to check.
	 *
	 * @return bool True if array keys are numeric, false otherwise.
	 */
	private function array_keys_numeric( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}

		return count( array_filter( array_keys( $array ), 'is_numeric' ) ) === count( $array );
	}

	/**
	 * Retrieves a single property value.
	 *
	 * @param array $property The property to retrieve the value for.
	 *
	 * @return array|mixed|string The property value.
	 */
	public function get_property_value( $property ) {
		if ( $this->has_alt_versions( $property ) ) {
			return $this->get_property_value( $this->get_active_property_version( $property ) );
		} elseif ( $this->has_loop( $property ) ) {
			$loop_id     = $this->get_loop_id( $property );
			$loop_helper = \SmartCrawl\Schema\Loops\Loop::create( $loop_id, $this->post );
			if ( $loop_helper ) {
				return $loop_helper->get_property_value(
					array_merge(
						$property,
						array( 'loop' => false ) // Disable loop to avoid infinite recursion.
					)
				);
			}
		} elseif ( $this->is_nested_property( $property ) ) {
			$nested_property_values = $this->get_property_values( $this->get_nested_properties( $property ) );
			if ( $this->array_keys_numeric( $nested_property_values ) ) {
				$nested_property_values = array_values( $nested_property_values );
			}
			if ( $nested_property_values && $this->has_required_for_block( $property, $nested_property_values ) ) {
				$property_type_value = $this->get_property_type( $property );
				$property_type       = $property_type_value && ! $this->is_simple_type( $property_type_value )
					? array( '@type' => $property_type_value )
					: array();

				return $property_type + $nested_property_values;
			}
		} else {
			$property_value = $this->get_single_property_value( $property );
			if ( $property_value ) {
				return $property_value;
			}
		}

		return '';
	}

	/**
	 * Checks if the property has required values for the block.
	 *
	 * @param array $property The property to check.
	 * @param array $values The values to check.
	 *
	 * @return bool True if the property has required values for the block, false otherwise.
	 */
	private function has_required_for_block( $property, $values ) {
		if ( ! $this->is_nested_property( $property ) ) {
			return true;
		}

		$nested             = $this->get_nested_properties( $property );
		$required_for_block = array_filter(
			$nested,
			function ( $nested_property ) {
				return ! empty( $nested_property['requiredInBlock'] );
			}
		);
		if ( empty( $required_for_block ) || ! is_array( $required_for_block ) ) {
			return true;
		}

		foreach ( array_keys( $required_for_block ) as $required_item ) {
			if ( empty( $values[ $required_item ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if the property has a loop.
	 *
	 * @param array $property The property to check.
	 *
	 * @return bool True if the property has a loop, false otherwise.
	 */
	private function has_loop( $property ) {
		return ! empty( $this->get_loop_id( $property ) );
	}

	/**
	 * Retrieves the loop ID.
	 *
	 * @param array $property The property to retrieve the loop ID for.
	 *
	 * @return mixed|null The loop ID.
	 */
	private function get_loop_id( $property ) {
		return \smartcrawl_get_array_value( $property, 'loop' );
	}

	/**
	 * Checks if the property has alternative versions.
	 *
	 * @param array $property The property to check.
	 *
	 * @return bool True if the property has alternative versions, false otherwise.
	 */
	private function has_alt_versions( $property ) {
		return ! empty( $this->get_active_property_version( $property ) );
	}

	/**
	 * Retrieves the active property version.
	 *
	 * @param array $property The property to retrieve the active version for.
	 *
	 * @return array|mixed The active property version.
	 */
	private function get_active_property_version( $property ) {
		$active_version = \smartcrawl_get_array_value( $property, 'activeVersion' );
		if ( empty( $active_version ) ) {
			return array();
		}

		return empty( $property['properties'][ $active_version ] )
			? array()
			: $property['properties'][ $active_version ];
	}

	/**
	 * Checks if the property is nested.
	 *
	 * @param array $property The property to check.
	 *
	 * @return bool True if the property is nested, false otherwise.
	 */
	private function is_nested_property( $property ) {
		return (bool) $this->get_nested_properties( $property );
	}

	/**
	 * Retrieves the nested properties.
	 *
	 * @param array $property The property to retrieve the nested properties for.
	 *
	 * @return mixed|null The nested properties.
	 */
	private function get_nested_properties( $property ) {
		return \smartcrawl_get_array_value( $property, 'properties' );
	}

	/**
	 * Retrieves the property type.
	 *
	 * @param array $property The property to retrieve the type for.
	 *
	 * @return mixed|null The property type.
	 */
	private function get_property_type( $property ) {
		return \smartcrawl_get_array_value( $property, 'type' );
	}

	/**
	 * Checks if the type is simple.
	 *
	 * @param string $type The type to check.
	 *
	 * @return bool True if the type is simple, false otherwise.
	 */
	private function is_simple_type( $type ) {
		return in_array(
			$type,
			array(
				'DateTime',
				'Email',
				'ImageObject',
				'ImageURL',
				'Phone',
				'Text',
				'TextFull',
				'URL',
				'Dynamic',
			),
			true
		);
	}

	/**
	 * Retrieves a single property value.
	 *
	 * @param array $property The property to retrieve the value for.
	 *
	 * @return array|mixed|string The property value.
	 */
	private function get_single_property_value( $property ) {
		$source = \smartcrawl_get_array_value( $property, 'source' );
		if ( ! $source ) {
			return '';
		}

		$value    = \smartcrawl_get_array_value( $property, 'value' );
		$type     = $this->get_property_type( $property );
		$property = $this->property_source_factory->create( $source, $value, $type );
		return \smartcrawl_clean( $property->get_value() );
	}
}