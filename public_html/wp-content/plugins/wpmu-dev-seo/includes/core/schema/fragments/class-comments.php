<?php
/**
 * Comments class for handling comment schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Entities;
use SmartCrawl\Schema\Utils;

/**
 * Class Comments
 *
 * Handles comment schema fragments.
 */
class Comments extends Fragment {

	/**
	 * The post object.
	 *
	 * @var Entities\Post
	 */
	private $post;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Constructor.
	 *
	 * @param Entities\Post $post The post object.
	 */
	public function __construct( $post ) {
		$this->post  = $post;
		$this->utils = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array The raw schema data.
	 */
	protected function get_raw() {
		/**
		 * Retrieves the comments for the post.
		 *
		 * @var \WP_Comment[] $comments The comments array.
		 */
		$comments = get_comments(
			array(
				'post_id'      => $this->post->get_post_id(),
				'status'       => 'approve',
				'hierarchical' => 'threaded',
			)
		);

		return $this->comments_to_schema( $comments, $this->post->get_permalink() );
	}

	/**
	 * Converts comments to schema format.
	 *
	 * @param \WP_Comment[] $comments The comments array.
	 * @param string        $post_url The post URL.
	 *
	 * @return array The comments' schema.
	 */
	private function comments_to_schema( $comments, $post_url ) {
		$schema = array();
		foreach ( $comments as $comment ) {
			$author_id      = '#comment-author-' . md5( $comment->comment_author_email );
			$comment_schema = array(
				'@type'       => 'Comment',
				'@id'         => $this->utils->url_to_id( $post_url, '#schema-comment-' . $comment->comment_ID ),
				'text'        => $comment->comment_content,
				'dateCreated' => $comment->comment_date,
				'url'         => get_comment_link( $comment ),
				'author'      => array(
					'@type' => 'Person',
					'@id'   => $this->utils->url_to_id( $post_url, $author_id ),
					'name'  => $comment->comment_author,
				),
			);

			$children = $comment->get_children();
			if ( ! empty( $children ) ) {
				$comment_schema['comment'] = $this->comments_to_schema( $children, $post_url );
			}

			if ( ! empty( $comment->comment_author_url ) ) {
				$comment_schema['author']['url'] = $comment->comment_author_url;
			}

			$schema[] = $comment_schema;
		}

		return $schema;
	}
}