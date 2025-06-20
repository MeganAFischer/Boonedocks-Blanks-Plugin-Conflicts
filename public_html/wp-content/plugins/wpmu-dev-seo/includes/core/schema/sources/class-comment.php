<?php
/**
 * Comment class for handling comment schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class Comment
 *
 * Handles comment schema fragments.
 */
class Comment extends Property {

	const ID = 'comment';

	/**
	 * The comment object.
	 *
	 * @var \WP_Comment
	 */
	private $comment;

	/**
	 * The field to retrieve the comment data for.
	 *
	 * @var string
	 */
	private $field;

	/**
	 * Comment constructor.
	 *
	 * @param \WP_Comment $comment The comment object.
	 * @param string      $field The field to retrieve the comment data for.
	 */
	public function __construct( $comment, $field ) {
		parent::__construct();

		$this->comment = $comment;
		$this->field   = $field;
	}

	/**
	 * Retrieves the value of the comment data.
	 *
	 * @return string The value of the comment data.
	 */
	public function get_value() {
		if ( empty( $this->comment ) ) {
			return '';
		}

		switch ( $this->field ) {
			case 'comment_date':
				return get_comment_date( 'c', $this->comment );

			case 'comment_author_name':
				return get_comment_author( $this->comment );

			case 'comment_text':
				return get_comment_text( $this->comment );

			case 'comment_url':
				return get_comment_link( $this->comment );

			default:
				return '';
		}
	}
}