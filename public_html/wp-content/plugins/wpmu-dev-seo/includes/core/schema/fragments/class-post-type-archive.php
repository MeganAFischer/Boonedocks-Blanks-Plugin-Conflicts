<?php
/**
 * Post_Type_Archive class for handling post type archive schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Post_Type_Archive
 *
 * Handles post type archive schema fragments.
 */
class Post_Type_Archive extends Fragment {

	/**
	 * The post type.
	 *
	 * @var \WP_Post_Type
	 */
	private $post_type;

	/**
	 * The posts related to the post type archive.
	 *
	 * @var \WP_Post[]
	 */
	private $posts;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The title of the post type archive.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the post type archive.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Post_Type_Archive constructor.
	 *
	 * @param \WP_Post_Type $post_type The post type.
	 * @param \WP_Post[]    $posts The posts related to the post type archive.
	 * @param string        $title The title of the post type archive.
	 * @param string        $description The description of the post type archive.
	 */
	public function __construct( $post_type, $posts, $title, $description ) {
		$this->post_type   = $post_type;
		$this->posts       = $posts;
		$this->title       = $title;
		$this->description = $description;
		$this->utils       = Utils::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array|mixed|Archive The raw schema data.
	 */
	protected function get_raw() {
		$enabled                = (bool) $this->utils->get_schema_option( 'schema_enable_post_type_archives' );
		$disabled               = (bool) $this->utils->get_schema_option(
			array(
				'schema_disabled_post_type_archives',
				$this->post_type->name,
			)
		);
		$post_type_archive_link = get_post_type_archive_link( $this->post_type->name );

		if ( $enabled && ! $disabled ) {
			return new Archive(
				'CollectionPage',
				$post_type_archive_link,
				$this->posts,
				$this->title,
				$this->description
			);
		} else {
			$custom_schema_types = $this->utils->get_custom_schema_types();
			if ( $custom_schema_types ) {
				return $this->utils->add_custom_schema_types(
					array(),
					$custom_schema_types,
					$this->utils->get_webpage_id( $post_type_archive_link )
				);
			} else {
				return array();
			}
		}
	}
}