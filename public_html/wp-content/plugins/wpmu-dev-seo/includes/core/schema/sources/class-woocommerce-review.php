<?php
/**
 * Woocommerce_Review class for handling WooCommerce review properties in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class Woocommerce_Review
 *
 * Handles WooCommerce review properties.
 */
class Woocommerce_Review extends Property {
	const ID = 'woocommerce_review';

	/**
	 * The comment object.
	 *
	 * @var \WP_Comment
	 */
	private $comment;

	/**
	 * The field to retrieve.
	 *
	 * @var string
	 */
	private $field;

	/**
	 * Constructor.
	 *
	 * @param \WP_Comment $comment The comment object.
	 * @param string      $field   The field to retrieve.
	 */
	public function __construct( $comment, $field ) {
		parent::__construct();

		$this->comment = $comment;
		$this->field   = $field;
	}

	/**
	 * Retrieves the value of the specified field.
	 *
	 * @return mixed|string The value of the field or an empty string if the field is not found.
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

			case 'rating_value':
				return get_comment_meta( $this->comment->comment_ID, 'rating', true );

			case 'comment_text':
				return get_comment_text( $this->comment );

			default:
				return '';
		}
	}
}