<?php
/**
 * Manages Schema Author Archive fragment.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;
use WP_Post;
use WP_User;

/**
 * Schema Author Archive Fragment class.
 */
class Author_Archive extends Fragment {
	/**
	 * Schema Utils.
	 *
	 * @var Utils
	 */
	private Utils $utils;
	/**
	 * Author as WP_User object.
	 *
	 * @var WP_User
	 */
	private WP_User $author;
	/**
	 * Author url.
	 *
	 * @var string
	 */
	private string $url;
	/**
	 * Posts created by this author.
	 *
	 * @var WP_Post[]
	 */
	private array $posts;
	/**
	 * Author archive title.
	 *
	 * @var string
	 */
	private string $title;
	/**
	 * Author archive description.
	 *
	 * @var string
	 */
	private string $description;

	/**
	 * Constructor.
	 *
	 * @param WP_User   $author Author as WP_User object.
	 * @param WP_Post[] $posts WP Posts.
	 * @param string    $title Author archive title.
	 * @param string    $description Author archive description.
	 */
	public function __construct( WP_User $author, array $posts, string $title, string $description ) {
		$this->author      = $author;
		$this->posts       = $posts;
		$this->title       = $title;
		$this->description = $description;
		$this->utils       = Utils::get();
		$this->url         = get_author_posts_url( $this->author->ID );
	}

	/**
	 * Retrieves schema raw data.
	 *
	 * @return array
	 */
	protected function get_raw(): array {
		$enabled = (bool) $this->utils->get_schema_option( 'schema_enable_author_archives' );

		if ( $enabled ) {
			$publisher    = new Publisher( false );
			$publisher_id = $publisher->get_publisher_id();

			$schema = array(
				new Header( $this->url, $this->title, $this->description ),
				new Footer( $this->url, $this->title, $this->description ),
				$publisher,
				new Website(),
				$this->get_author_schema( $publisher_id ),
				new Breadcrumb(),
			);
		} else {
			$schema = array();
		}

		$custom_schema_types = $this->utils->get_custom_schema_types();

		if ( $custom_schema_types ) {
			$schema = $this->utils->add_custom_schema_types(
				$schema,
				$custom_schema_types,
				$this->utils->get_webpage_id( $this->url )
			);
		}

		return $schema;
	}

	/**
	 * Retrieves author's schema.
	 *
	 * @param string $publisher_id Publisher ID.
	 *
	 * @return array
	 */
	private function get_author_schema( string $publisher_id ): array {
		return array(
			'@type'      => 'ProfilePage',
			'@id'        => $this->utils->get_webpage_id( $this->url ),
			'url'        => $this->url,
			'isPartOf'   => array(
				'@id' => $this->utils->get_website_id(),
			),
			'publisher'  => array(
				'@id' => $publisher_id,
			),
			'mainEntity' => array(
				'@id'   => "#{$this->author->nickname}",
				'@type' => 'Person',
				'name'  => $this->author->display_name,
				'image' => get_avatar_url( $this->author->ID ),
			),
			'hasPart'    => $this->get_author_articles(),
		);
	}

	/**
	 * Retrieves author's articles.
	 *
	 * @return array
	 */
	private function get_author_articles(): array {
		$articles = array();

		foreach ( $this->posts as $_post ) {
			$post = new \SmartCrawl\Entities\Post( $_post );

			$articles[] = array(
				'@type'         => 'Article',
				'headline'      => $post->get_title(),
				'url'           => $post->get_permalink(),
				'datePublished' => $post->get_post_date(),
				'author'        => array(
					'@id' => "#{$this->author->nickname}",
				),
			);
		}

		return $articles;
	}
}