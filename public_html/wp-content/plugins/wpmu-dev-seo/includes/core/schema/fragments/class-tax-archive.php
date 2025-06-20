<?php
/**
 * Tax_Archive class for handling taxonomy archive schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Schema\Utils;

/**
 * Class Tax_Archive
 *
 * Handles taxonomy archive schema fragments.
 */
class Tax_Archive extends Fragment {

	/**
	 * The term object.
	 *
	 * @var \WP_Term
	 */
	private $term;

	/**
	 * The posts associated with the term.
	 *
	 * @var array
	 */
	private $posts;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The title of the archive.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The description of the archive.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Constructor.
	 *
	 * @param \WP_Term $term The term object.
	 * @param array    $posts The posts associated with the term.
	 * @param string   $title The title of the archive.
	 * @param string   $description The description of the archive.
	 */
	public function __construct( $term, $posts, $title, $description ) {
		$this->term        = $term;
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
		$enabled  = (bool) $this->utils->get_schema_option( 'schema_enable_taxonomy_archives' );
		$disabled = (bool) $this->utils->get_schema_option(
			array(
				'schema_disabled_taxonomy_archives',
				$this->term->taxonomy,
			)
		);
		$term_url = get_term_link( $this->term, $this->term->taxonomy );

		if ( $enabled && ! $disabled ) {
			return new Archive(
				'CollectionPage',
				$term_url,
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
					$this->utils->get_webpage_id( $term_url )
				);
			} else {
				return array();
			}
		}
	}
}