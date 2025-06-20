<?php
/**
 * Static_Home class for handling static home schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Static_Home
 *
 * Handles static home schema fragments.
 */
class Static_Home extends Fragment {

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The posts related to the static home.
	 *
	 * @var \WP_Post[]
	 */
	private $posts;

	/**
	 * The title of the static home.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the static home.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Static_Home constructor.
	 *
	 * @param \WP_Post[] $posts The posts related to the static home.
	 * @param string     $title The title of the static home.
	 * @param string     $description The description of the static home.
	 */
	public function __construct( $posts, $title, $description ) {
		$this->utils       = Utils::get();
		$this->posts       = $posts;
		$this->title       = $title;
		$this->description = $description;
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return Archive The raw schema data.
	 */
	protected function get_raw() {
		$page_for_posts_id = get_option( 'page_for_posts' );
		$url               = get_permalink( $page_for_posts_id );

		return new Archive(
			'CollectionPage',
			$url,
			$this->posts,
			$this->title,
			$this->description
		);
	}
}