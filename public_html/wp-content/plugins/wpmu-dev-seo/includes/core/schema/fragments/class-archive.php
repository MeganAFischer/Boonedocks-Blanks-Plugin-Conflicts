<?php
/**
 * Manages Schema Archive fragment.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Schema Archive Fragment class.
 */
class Archive extends Fragment {
	/**
	 * Archive type.
	 *
	 * @var string
	 */
	private $type;
	/**
	 * Archive url.
	 *
	 * @var string
	 */
	private $url;
	/**
	 * Schema Utils.
	 *
	 * @var Utils
	 */
	private $utils;
	/**
	 * Posts included in this archive.
	 *
	 * @var \WP_Post[]
	 */
	private $wp_posts;
	/**
	 * Archive title.
	 *
	 * @var string
	 */
	private $title;
	/**
	 * Archive description.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Constructor.
	 *
	 * @param string     $type Archive type.
	 * @param string     $url Archive url.
	 * @param \WP_Post[] $wp_posts Archive posts.
	 * @param string     $title Archive title.
	 * @param string     $description Archive description.
	 */
	public function __construct( $type, $url, $wp_posts, $title, $description ) {
		$this->type        = $type;
		$this->url         = $url;
		$this->wp_posts    = $wp_posts;
		$this->title       = $title;
		$this->description = $description;
		$this->utils       = Utils::get();
	}

	/**
	 * Retrieves schema raw data.
	 *
	 * @return array
	 */
	protected function get_raw() {
		$publisher    = new Publisher( false );
		$publisher_id = $publisher->get_publisher_id();

		$schema = array(
			new Header( $this->url, $this->title, $this->description ),
			new Footer( $this->url, $this->title, $this->description ),
			$publisher,
			new Website(),
			$this->get_collection_schema( $publisher_id, $this->wp_posts ),
			new Breadcrumb(),
		);

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
	 * Retrieves archive's main entity type.
	 *
	 * @return string
	 */
	private function get_archive_main_entity_type() {
		$list_type = $this->utils->get_schema_option( 'schema_archive_main_entity_type' );

		return $list_type
			? $list_type
			: \SmartCrawl\Schema\Type_Constants::TYPE_ITEM_LIST;
	}

	/**
	 * Retrieves collection schema.
	 *
	 * @param string     $publisher_id Publisher ID.
	 * @param \WP_Post[] $wp_posts WP Posts.
	 *
	 * @return array
	 */
	private function get_collection_schema( $publisher_id, $wp_posts ) {
		return array(
			'@type'      => $this->type,
			'@id'        => $this->utils->get_webpage_id( $this->url ),
			'isPartOf'   => array(
				'@id' => $this->utils->get_website_id(),
			),
			'publisher'  => array(
				'@id' => $publisher_id,
			),
			'url'        => $this->url,
			'mainEntity' => $this->get_main_entity( $wp_posts, $publisher_id ),
		);
	}

	/**
	 * Retrieves main entity.
	 *
	 * @param \WP_Post[] $wp_posts WP Posts.
	 * @param string     $publisher_id Publisher ID.
	 *
	 * @return array|false
	 */
	private function get_main_entity( $wp_posts, $publisher_id ) {
		$list_type         = $this->get_archive_main_entity_type();
		$is_type_item_list = \SmartCrawl\Schema\Type_Constants::TYPE_ITEM_LIST === $list_type;
		$list_type_key     = $is_type_item_list ? 'itemListElement' : 'blogPosts';
		$list_item_type    = $is_type_item_list ? 'ListItem' : 'BlogPosting';

		$item_list = array();
		$position  = 1;
		$wp_posts  = empty( $wp_posts ) || ! is_array( $wp_posts ) ? array() : $wp_posts;

		foreach ( $wp_posts as $wp_post ) {
			$post = new \SmartCrawl\Entities\Post( $wp_post );

			if ( $is_type_item_list ) {
				$item_list[] = array(
					'@type'    => $list_item_type,
					'position' => (string) $position,
					'url'      => $post->get_permalink(),
				);
			} else {
				$item_list[] = new Post(
					$post,
					false,
					$publisher_id,
					false
				);
			}

			++$position;
		}

		if ( $item_list ) {
			return array(
				'@type'        => $list_type,
				$list_type_key => $item_list,
			);
		}

		return false;
	}
}