<?php
/**
 * Webpage class for handling webpage schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Entities;
use SmartCrawl\Schema\Utils;

/**
 * Class Webpage
 *
 * Handles webpage schema fragments.
 */
class Webpage extends Fragment {

	/**
	 * The post entity.
	 *
	 * @var Entities\Post
	 */
	private $post;

	/**
	 * The type of the webpage.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * The author ID.
	 *
	 * @var int
	 */
	private $author_id;

	/**
	 * The publisher ID.
	 *
	 * @var int
	 */
	private $publisher_id;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Webpage constructor.
	 *
	 * @param Entities\Post $post The post entity.
	 * @param string        $type The type of the webpage.
	 * @param int           $author_id The author ID.
	 * @param int           $publisher_id The publisher ID.
	 */
	public function __construct( $post, $type, $author_id, $publisher_id ) {
		$this->post         = $post;
		$this->type         = $type;
		$this->author_id    = $author_id;
		$this->publisher_id = $publisher_id;
		$this->utils        = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array The raw schema data.
	 */
	protected function get_raw() {
		$post_permalink = $this->post->get_permalink();
		$post_fragment  = new Post(
			$this->post,
			$this->author_id,
			$this->publisher_id,
			true
		);

		return array(
			'@type'    => $this->type,
			'@id'      => $this->utils->get_webpage_id( $post_permalink ),
			'isPartOf' => $this->utils->get_website_id(),
			'hasPart'  => new Menu( $post_permalink ),
			'url'      => $post_permalink,
		) + $post_fragment->get_schema();
	}
}