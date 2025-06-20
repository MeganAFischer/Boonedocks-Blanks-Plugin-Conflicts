<?php
/**
 * Manages Schema Article fragment.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Entities;
use SmartCrawl\Schema\Utils;

/**
 * Schema Article Fragment class.
 */
class Article extends Fragment {
	/**
	 * Post entity.
	 *
	 * @var Entities\Post
	 */
	private $post;
	/**
	 * Article type.
	 *
	 * @var string
	 */
	private $type;
	/**
	 * Author ID.
	 *
	 * @var string
	 */
	private $author_id;
	/**
	 * Publisher ID.
	 *
	 * @var string
	 */
	private $publisher_id;
	/**
	 * Schema Utils.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Constructor.
	 *
	 * @param Entities\Post $post Post entity.
	 * @param string        $type Article type.
	 * @param string        $author_id Article author ID.
	 * @param string        $publisher_id Article publisher ID.
	 */
	public function __construct( $post, $type, $author_id, $publisher_id ) {
		$this->post         = $post;
		$this->type         = $type;
		$this->author_id    = $author_id;
		$this->publisher_id = $publisher_id;
		$this->utils        = Utils::get();
	}

	/**
	 * Retrieves schema raw data.
	 *
	 * @return array
	 */
	protected function get_raw() {
		$post_fragment = new Post(
			$this->post,
			$this->author_id,
			$this->publisher_id,
			true
		);

		return array(
			'@type'            => $this->type,
			'mainEntityOfPage' => array(
				'@id' => $this->utils->get_webpage_id( $this->post->get_permalink() ),
			),
		) + $post_fragment->get_schema();
	}
}