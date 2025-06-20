<?php
/**
 * Comments class for handling comments schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Loops;

/**
 * Class Comments
 *
 * Handles comments schema fragments.
 */
class Comments extends Loop {
	const ID = 'post-comments';

	/**
	 * The post object.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Comments constructor.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function __construct( $post ) {
		$this->post = $post;
	}

	/**
	 * Retrieves the property value for the comments.
	 *
	 * @param string $property The property to retrieve the value for.
	 *
	 * @return array The property value.
	 */
	public function get_property_value( $property ) {
		if ( empty( $this->post ) ) {
			return array();
		}

		$schema = array();
		foreach ( $this->get_comments() as $comment ) {
			$factory               = new \SmartCrawl\Schema\Sources\Comment_Factory( $this->post, $comment );
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
				'number'  => 10,
				'post_id' => $this->post->ID,
				'status'  => 'approve',
			)
		);
	}
}