<?php
/**
 * Post class for handling post schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Entities;
use SmartCrawl\Schema\Utils;

/**
 * Class Post
 *
 * Handles post schema fragments.
 */
class Post extends Fragment {

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The post object.
	 *
	 * @var Entities\Post
	 */
	private $post;

	/**
	 * The author ID.
	 *
	 * @var string
	 */
	private $author_id;

	/**
	 * Indicates if comments should be included.
	 *
	 * @var bool
	 */
	private $include_comments;

	/**
	 * The publisher ID.
	 *
	 * @var string
	 */
	private $publisher_id;

	/**
	 * Post constructor.
	 *
	 * @param Entities\Post $post The post object.
	 * @param string        $author_id The author ID.
	 * @param string        $publisher_id The publisher ID.
	 * @param bool          $include_comments Indicates if comments should be included.
	 */
	public function __construct( $post, $author_id, $publisher_id, $include_comments ) {
		$this->post             = $post;
		$this->publisher_id     = $publisher_id;
		$this->author_id        = $author_id;
		$this->include_comments = $include_comments;
		$this->utils            = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array The raw schema data.
	 */
	protected function get_raw() {
		$wp_post = $this->utils->apply_filters(
			'post',
			$this->post->get_wp_post()
		);

		$headline    = $this->post->get_meta_title();
		$description = $this->post->get_meta_description();

		$author_schema = $this->author_id
			? array( '@id' => $this->author_id ) // An author has already been added, just link to it.
			: new Post_Author( $this->post->get_post_author() );

		$schema = array(
			'author'        => $author_schema,
			'publisher'     => array( '@id' => $this->publisher_id ),
			'dateModified'  => get_the_modified_date( 'Y-m-d\TH:i:s', $wp_post ),
			'datePublished' => get_the_date( 'Y-m-d\TH:i:s', $wp_post ),
			'headline'      => $this->utils->apply_filters( 'post-data-headline', $headline, $wp_post ),
			'description'   => $description,
			'name'          => $this->utils->apply_filters( 'post-data-name', get_the_title( $wp_post ), $wp_post ),
		);

		$enable_comments = (bool) $this->utils->get_schema_option( 'schema_enable_comments' );
		if ( $this->include_comments && $enable_comments ) {
			$schema['commentCount'] = get_comments_number( $this->post->get_post_id() );
			$schema['comment']      = new Comments( $this->post );
		}

		return $this->add_article_image( $schema );
	}

	/**
	 * Adds the article image to the schema.
	 *
	 * @param array $schema The schema array.
	 *
	 * @return array The updated schema array.
	 */
	private function add_article_image( $schema ) {
		$thumbnail_id = $this->post->get_thumbnail_id();

		if ( $thumbnail_id ) {
			$image_id = $thumbnail_id;
		} else {
			$image_id = (int) $this->utils->get_schema_option( 'schema_default_image' );
		}

		if ( $image_id ) {
			$schema['image']        = $this->filter_post_data_image(
				$this->utils->get_media_item_image_schema(
					$image_id,
					$this->utils->url_to_id( $this->post->get_permalink(), '#schema-article-image' )
				)
			);
			$schema['thumbnailUrl'] = (string) $this->utils->apply_filters(
				'post-data-thumbnailUrl',
				\smartcrawl_get_array_value( $schema, array( 'image', 'url' ) )
			);
		}
		return $schema;
	}

	/**
	 * Filters the post data image.
	 *
	 * @param array $schema_image The schema image array.
	 *
	 * @return array The filtered schema image array.
	 */
	private function filter_post_data_image( $schema_image ) {
		return $this->utils->apply_filters( 'post-data-image', $schema_image );
	}
}