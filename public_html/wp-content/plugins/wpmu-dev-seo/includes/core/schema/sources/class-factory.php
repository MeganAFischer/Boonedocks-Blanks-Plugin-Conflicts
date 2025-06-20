<?php
/**
 * Factory class for creating schema sources in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class Factory
 *
 * Handles the creation of schema sources.
 */
class Factory {

	/**
	 * The post object.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Constructor.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function __construct( $post ) {
		$this->post = $post;
	}

	/**
	 * Creates a schema source.
	 *
	 * @param string $source The source identifier.
	 * @param mixed  $value  The value for the source.
	 * @param string $type   The type of the source.
	 *
	 * @return Author|Media|Options|Post|Post_Meta|Schema_Settings|SEO_Meta|Site_Settings|Text|Woocommerce
	 */
	public function create( $source, $value, $type ) {
		switch ( $source ) {
			case Author::ID:
			case Post::ID:
			case Post_Meta::ID:
			case Woocommerce::ID:
				return $this->create_post_dependent_source( $source, $value );

			case Media::OBJECT:
				return new Media( $value, Media::OBJECT );

			case Media::URL:
				return new Media( $value, Media::URL );

			case Schema_Settings::ID:
				return new Schema_Settings( $value );

			case SEO_Meta::ID:
				return new SEO_Meta( $value );

			case Site_Settings::ID:
				return new Site_Settings( $value );

			case Text::ID:
			case 'datetime':
			case 'number':
			case 'duration':
				return new Text( $value );

			case Options::ID:
				return new Options( $value, $type );

			default:
				return $this->create_default_source();
		}
	}

	/**
	 * Creates a post-dependent schema source.
	 *
	 * @param string $source The source identifier.
	 * @param mixed  $value  The value for the source.
	 *
	 * @return Author|Post|Post_Meta|Text|Woocommerce
	 */
	private function create_post_dependent_source( $source, $value ) {
		if ( ! $this->post ) {
			return $this->create_default_source();
		}

		switch ( $source ) {
			case Author::ID:
				return new Author( $this->post, $value );

			case Post::ID:
				return new Post( $this->post, $value );

			case Post_Meta::ID:
				return new Post_Meta( $this->post, $value );

			case Woocommerce::ID:
				return new Woocommerce( $this->post, $value );

			default:
				return $this->create_default_source();
		}
	}

	/**
	 * Creates a default schema source.
	 *
	 * @return Text
	 */
	protected function create_default_source() {
		return new Text( '' );
	}
}