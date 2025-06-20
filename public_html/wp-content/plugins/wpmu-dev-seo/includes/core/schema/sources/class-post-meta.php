<?php
/**
 * Post_Meta class for handling post meta schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class Post_Meta
 *
 * Handles post meta schema fragments.
 */
class Post_Meta extends Property {
	const ID = 'post_meta';

	/**
	 * The meta key.
	 *
	 * @var string
	 */
	private $meta_key;

	/**
	 * The post object.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Post_Meta constructor.
	 *
	 * @param \WP_Post $post The post object.
	 * @param string   $meta_key The meta key.
	 */
	public function __construct( $post, $meta_key ) {
		parent::__construct();

		$this->meta_key = $meta_key;
		$this->post     = $post;
	}

	/**
	 * Retrieves the value of the post meta.
	 *
	 * @return bool|float|int|string The value of the post meta.
	 */
	public function get_value() {
		$meta_value = get_post_meta( $this->post->ID, $this->meta_key, true );
		if ( $meta_value && is_scalar( $meta_value ) ) {
			return $meta_value;
		}

		return '';
	}
}