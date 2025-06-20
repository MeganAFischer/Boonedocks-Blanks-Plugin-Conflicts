<?php
/**
 * Abstract Fragment class for handling schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

/**
 * Class Fragment
 *
 * Abstract class for handling schema fragments.
 */
abstract class Fragment {

	/**
	 * The schema data.
	 *
	 * @var null|array
	 */
	private $schema = null;

	/**
	 * Retrieves the schema data.
	 *
	 * @return array|mixed|null The schema data.
	 */
	public function get_schema() {
		if ( is_null( $this->schema ) ) {
			$this->schema = $this->make_schema();
		}

		return $this->schema;
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return mixed The raw schema data.
	 */
	abstract protected function get_raw();

	/**
	 * Creates the schema data.
	 *
	 * @return array|mixed|null The created schema data.
	 */
	private function make_schema() {
		/**
		 * Action hook to run before making schema.
		 *
		 * @since 3.8.0
		 */
		do_action( 'smartcrawl_before_make_schema' );

		$schema = $this->process_schema_item( $this->get_raw() );

		/**
		 * Action hook to run after making schema.
		 *
		 * @since 3.8.0
		 *
		 * @param array $schema Schema data.
		 */
		do_action( 'smartcrawl_after_make_schema' );

		return $schema;
	}

	/**
	 * Processes a schema item.
	 *
	 * @param mixed $schema_item The schema item to process.
	 *
	 * @return array|mixed|null The processed schema item.
	 */
	private function process_schema_item( $schema_item ) {
		if ( is_a( $schema_item, self::class ) ) {
			return $schema_item->get_schema();
		} elseif ( is_array( $schema_item ) ) {
			return $this->traverse_schema_array( $schema_item );
		} else {
			return $schema_item;
		}
	}

	/**
	 * Traverses a schema array.
	 *
	 * @param array $schema The schema array to traverse.
	 *
	 * @return array The traversed schema array.
	 */
	private function traverse_schema_array( $schema ) {
		$new_schema   = array();
		$keys_numeric = true;

		foreach ( $schema as $schema_item_id => $schema_item ) {
			$keys_numeric = $keys_numeric && is_numeric( $schema_item_id );

			$processed_schema_item = $this->process_schema_item( $schema_item );

			if ( false !== $processed_schema_item ) {
				$new_schema[ $schema_item_id ] = $processed_schema_item;
			}
		}

		return $keys_numeric
			? array_values( $new_schema )
			: $new_schema;
	}
}