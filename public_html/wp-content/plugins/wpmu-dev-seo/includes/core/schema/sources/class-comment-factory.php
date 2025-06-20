<?php
/**
 * Comment_Factory class for creating comment-related schema sources in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

use WP_Comment;
use WP_Post;

/**
 * Class Comment_Factory
 *
 * Handles the creation of comment-related schema sources.
 */
class Comment_Factory extends Factory {

	/**
	 * The comment object.
	 *
	 * @var WP_Comment
	 */
	private $comment;

	/**
	 * Constructor.
	 *
	 * @param WP_Post    $post    The post object.
	 * @param WP_Comment $comment The comment object.
	 */
	public function __construct( $post, $comment ) {
		parent::__construct( $post );
		$this->comment = $comment;
	}

	/**
	 * Creates a schema source.
	 *
	 * @param string $source The source identifier.
	 * @param string $field  The field to retrieve.
	 * @param string $type   The type of the source.
	 *
	 * @return Comment|Text The created schema source.
	 */
	public function create( $source, $field, $type ) {
		if ( empty( $this->comment ) ) {
			return $this->create_default_source();
		}

		if ( Comment::ID === $source ) {
			return new Comment( $this->comment, $field );
		}

		return parent::create( $source, $field, $type );
	}
}