<?php
/**
 * Taxonomy Term Archive Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

/**
 * Taxonomy_Term class extending Entity_With_Archive.
 */
class Taxonomy_Term extends Entity_With_Archive {
	/**
	 * Term ID.
	 *
	 * @var int
	 */
	private $term_id;
	/**
	 * WP Term object.
	 *
	 * @var \WP_Term|false
	 */
	private $wp_term;
	/**
	 * Posts within the archive.
	 *
	 * @var \WP_Post[]
	 */
	private $posts;
	/**
	 * OpenGraph term meta.
	 *
	 * @var array
	 */
	private $opengraph_term_meta;
	/**
	 * Twitter term meta.
	 *
	 * @var array
	 */
	private $twitter_term_meta;
	/**
	 * Page number.
	 *
	 * @var int
	 */
	private $page_number;

	/**
	 * Constructor.
	 *
	 * @param \WP_Term|int $term WP Term object.
	 * @param \WP_Post[]   $posts WP Post object array.
	 * @param int          $page_number Page number.
	 */
	public function __construct( $term, $posts = array(), $page_number = 0 ) {
		if ( is_a( $term, '\WP_Term' ) ) {
			$this->term_id = $term->term_id;
			$this->wp_term = $term;
		} else {
			$this->term_id = $term;
		}

		$this->posts       = $posts;
		$this->page_number = $page_number;
	}

	/**
	 * Retrieves the term ID.
	 *
	 * @return int The term ID.
	 */
	public function get_term_id() {
		return $this->term_id;
	}

	/**
	 * Retrieves the name of the term.
	 *
	 * @return string The name of the term.
	 */
	public function get_name() {
		$wp_term = $this->get_wp_term();

		return $wp_term ? $wp_term->name : '';
	}

	/**
	 * Retrieves the description of the term.
	 *
	 * @return string The description of the term or an empty string if the term does not exist.
	 */
	public function get_description() {
		$wp_term = $this->get_wp_term();

		return $wp_term ? $wp_term->description : '';
	}

	/**
	 * Retrieves the slug.
	 *
	 * @return string The term slug or an empty string if the WP term is not set.
	 */
	public function get_slug() {
		$wp_term = $this->get_wp_term();

		return $wp_term ? $wp_term->slug : '';
	}

	/**
	 * Retrieves the WP Term object.
	 *
	 * @return \WP_Term|false The WP Term object.
	 */
	public function get_wp_term() {
		if ( ! $this->wp_term ) {
			$this->wp_term = $this->load_wp_term();
		}

		return $this->wp_term;
	}

	/**
	 * Loads the WP term object.
	 *
	 * @return \WP_Term|false The WP Term object if loaded successfully, false otherwise.
	 */
	private function load_wp_term() {
		$term = get_term( $this->term_id );

		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		return $term;
	}

	/**
	 * Retrieves the taxonomy of the term.
	 *
	 * @return string The taxonomy name.
	 */
	public function get_taxonomy() {
		$wp_term = $this->get_wp_term();

		return $wp_term ? $wp_term->taxonomy : '';
	}

	/**
	 * Loads meta title.
	 *
	 * @return string Meta title.
	 */
	protected function load_meta_title() {
		return $this->load_string_value(
			$this->get_taxonomy(),
			array( $this, 'load_meta_title_from_term_meta' ),
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return '%%term_title%% %%sep%% %%sitename%%';
			}
		);
	}

	/**
	 * Loads the meta title from the term meta.
	 *
	 * @return string The meta title.
	 */
	protected function load_meta_title_from_term_meta() {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return '';
		}

		return \smartcrawl_get_term_meta( $wp_term, $wp_term->taxonomy, 'wds_title' );
	}

	/**
	 * Loads the meta description.
	 *
	 * @return string The loaded meta description.
	 */
	protected function load_meta_description() {
		return $this->load_string_value(
			$this->get_taxonomy(),
			array( $this, 'load_meta_desc_from_term_meta' ),
			array( $this, 'load_meta_desc_from_options' ),
			array( $this, 'get_description' )
		);
	}

	/**
	 * Loads the meta description from term meta.
	 *
	 * @return string The meta description.
	 */
	protected function load_meta_desc_from_term_meta() {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return '';
		}

		return \smartcrawl_get_term_meta( $wp_term, $wp_term->taxonomy, 'wds_desc' );
	}

	/**
	 * Loads robots meta tag.
	 *
	 * @return string Robots meta tag for the page number.
	 */
	protected function load_robots() {
		return $this->get_robots_for_page_number( $this->page_number );
	}

	/**
	 * Loads the canonical URL for the term object.
	 *
	 * @return string The canonical URL.
	 */
	protected function load_canonical_url() {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return '';
		}

		$canonical_from_meta = \smartcrawl_get_term_meta( $wp_term, $wp_term->taxonomy, 'wds_canonical' );
		if ( $canonical_from_meta ) {
			return $canonical_from_meta;
		}

		$first_page_indexed   = $this->is_first_page_indexed();
		$current_page_indexed = ! $this->is_noindex();
		$term_link            = get_term_link( $wp_term, $wp_term->taxonomy );

		if ( $current_page_indexed ) {
			return $this->append_page_number( $term_link, $this->page_number );
		}

		if ( $first_page_indexed ) {
			return $term_link;
		}

		return '';
	}

	/**
	 * Loads schema.
	 *
	 * @return array The schema data.
	 */
	protected function load_schema() {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return array();
		}

		$schema = new \SmartCrawl\Schema\Fragments\Tax_Archive(
			$wp_term,
			$this->posts,
			$this->get_meta_title(),
			$this->get_meta_description()
		);

		return $schema->get_schema();
	}

	/**
	 * Loads the OpenGraph tags.
	 *
	 * @return array Opengraph tags.
	 */
	protected function load_opengraph_tags() {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return array();
		}

		return parent::load_opengraph_tags();
	}

	/**
	 * Loads the OpenGraph enabled status.
	 *
	 * @return bool Whether OpenGraph is enabled or not.
	 */
	protected function load_opengraph_enabled() {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return false;
		}

		$enabled_in_options = $this->is_opengraph_enabled_for_location( $this->get_taxonomy() );

		if ( ! $enabled_in_options ) {
			return false;
		}

		$term_meta             = $this->get_opengraph_term_meta();
		$disabled_in_term_meta = \smartcrawl_get_array_value( $term_meta, 'disabled' );

		return ! $disabled_in_term_meta;
	}

	/**
	 * Retrieves the OpenGraph term meta.
	 *
	 * @return mixed The OpenGraph term meta.
	 */
	private function get_opengraph_term_meta() {
		if ( empty( $this->opengraph_term_meta ) ) {
			$this->opengraph_term_meta = $this->load_opengraph_term_meta();
		}

		return $this->opengraph_term_meta;
	}

	/**
	 * Loads OpenGraph term meta.
	 *
	 * @return array Opengraph term meta.
	 */
	private function load_opengraph_term_meta() {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return array();
		}

		return \smartcrawl_get_term_meta( $wp_term, $wp_term->taxonomy, 'opengraph' );
	}

	/**
	 * Retrieves the term meta.
	 *
	 * @param string $meta_key The meta key.
	 *
	 * @return string The meta value or an empty string if term does not exist.
	 */
	public function get_term_meta( $meta_key ) {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return '';
		}

		return get_term_meta( $this->get_term_id(), $meta_key, true );
	}

	/**
	 * Loads the OpenGraph title.
	 *
	 * @return string The OpenGraph title.
	 */
	protected function load_opengraph_title() {
		return $this->load_string_value(
			$this->get_taxonomy(),
			array( $this, 'load_opengraph_title_from_term_meta' ),
			array( $this, 'load_opengraph_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the OpenGraph title from term meta.
	 *
	 * @return string|null The OpenGraph title from term meta, or null if not found.
	 */
	protected function load_opengraph_title_from_term_meta() {
		return \smartcrawl_get_array_value( $this->get_opengraph_term_meta(), 'title' );
	}

	/**
	 * Loads the OpenGraph description.
	 *
	 * @return string The loaded OpenGraph description.
	 */
	protected function load_opengraph_description() {
		return $this->load_string_value(
			$this->get_taxonomy(),
			array( $this, 'load_opengraph_description_from_term_meta' ),
			array( $this, 'load_opengraph_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the OpenGraph description from term meta.
	 *
	 * @return string|null OpenGraph description or null.
	 */
	protected function load_opengraph_description_from_term_meta() {
		return \smartcrawl_get_array_value( $this->get_opengraph_term_meta(), 'description' );
	}

	/**
	 * Loads the OpenGraph images.
	 *
	 * @return array
	 */
	protected function load_opengraph_images() {
		return $this->load_social_images(
			array( $this, 'get_opengraph_term_meta' ),
			array( $this, 'load_opengraph_images_from_options' )
		);
	}

	/**
	 * Loads the social images.
	 *
	 * @param callable $load_post_meta A callback to load post meta.
	 * @param callable $load_from_options A callback to load from options.
	 *
	 * @return array Array of image URLs.
	 */
	private function load_social_images( $load_post_meta, $load_from_options ) {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return array();
		}

		// Check meta.
		$images = array_filter(
			\smartcrawl_get_array_value( call_user_func( $load_post_meta ), 'images', array() ),
			function ( $image ) {
				return wp_get_attachment_image_src( $image );
			}
		);

		if ( ! $images ) {
			// Meta not available, check options.
			$images = call_user_func( $load_from_options, $this->get_taxonomy() );
		}

		if ( $images ) {
			return $this->image_ids_to_urls( $images );
		}

		return array();
	}

	/**
	 * Loads the Twitter enabled status for the term object.
	 *
	 * @return bool Whether Twitter is enabled for the term.
	 */
	protected function load_twitter_enabled() {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return false;
		}

		$enabled_in_options = $this->is_twitter_enabled_for_location( $this->get_taxonomy() );

		if ( ! $enabled_in_options ) {
			return false;
		}

		$term_meta             = $this->get_twitter_term_meta();
		$disabled_in_term_meta = \smartcrawl_get_array_value( $term_meta, 'disabled' );

		return ! $disabled_in_term_meta;
	}

	/**
	 * Retrieves the Twitter term meta.
	 *
	 * If the Twitter term meta is not loaded, loads it.
	 *
	 * @return array|false Twitter term meta.
	 */
	public function get_twitter_term_meta() {
		if ( empty( $this->twitter_term_meta ) ) {
			$this->twitter_term_meta = $this->load_twitter_term_meta();
		}

		return $this->twitter_term_meta;
	}

	/**
	 * Loads the Twitter term meta.
	 *
	 * @return array Term meta array.
	 */
	private function load_twitter_term_meta() {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return array();
		}

		return \smartcrawl_get_term_meta( $wp_term, $wp_term->taxonomy, 'twitter' );
	}

	/**
	 * Loads the Twitter title.
	 *
	 * @return string The Twitter title.
	 */
	protected function load_twitter_title() {
		return $this->load_string_value(
			$this->get_taxonomy(),
			array( $this, 'load_twitter_title_from_term_meta' ),
			array( $this, 'load_twitter_title_from_options' ),
			array( $this, 'get_meta_title' )
		);
	}

	/**
	 * Loads the Twitter title from term meta.
	 *
	 * @return string The Twitter title from the term meta.
	 */
	protected function load_twitter_title_from_term_meta() {
		return \smartcrawl_get_array_value( $this->get_twitter_term_meta(), 'title', '' );
	}

	/**
	 * Loads the Twitter description.
	 *
	 * @return string The loaded Twitter description.
	 */
	protected function load_twitter_description() {
		return $this->load_string_value(
			$this->get_taxonomy(),
			array( $this, 'load_twitter_description_from_term_meta' ),
			array( $this, 'load_twitter_description_from_options' ),
			array( $this, 'get_meta_description' )
		);
	}

	/**
	 * Loads the Twitter description from term meta.
	 *
	 * @return string The loaded Twitter description.
	 */
	protected function load_twitter_description_from_term_meta() {
		return \smartcrawl_get_array_value( $this->get_twitter_term_meta(), 'description', '' );
	}

	/**
	 * Loads the Twitter images.
	 *
	 * @return array The loaded Twitter images.
	 */
	protected function load_twitter_images() {
		return $this->load_social_images(
			array( $this, 'get_twitter_term_meta' ),
			array( $this, 'load_twitter_images_from_options' )
		);
	}

	/**
	 * Retrieves macros based on the term object.
	 *
	 * @param string $subject The subject to search for dynamic replacements.
	 *
	 * @return array Array of macros.
	 */
	public function get_macros( $subject = '' ) {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return array();
		}

		$macros = array(
			'%%id%%'               => array( $this, 'get_term_id' ),
			'%%term_title%%'       => array( $this, 'get_name' ),
			'%%term_description%%' => array( $this, 'get_description' ),
			'%%archive-title%%'    => get_the_archive_title(),
			'%%original-title%%'   => array( $this, 'get_name' ),
		);

		if ( $this->get_taxonomy() === 'category' ) {
			$macros['%%category%%']             = array( $this, 'get_name' );
			$macros['%%category_description%%'] = array( $this, 'get_description' );
		} elseif ( $this->get_taxonomy() === 'post_tag' ) {
			$macros['%%tag%%']             = array( $this, 'get_name' );
			$macros['%%tag_description%%'] = array( $this, 'get_description' );
		}

		$dynamic = $this->find_dynamic_replacements(
			$subject,
			function ( $taxonomy ) use ( $wp_term ) {
				if ( $taxonomy === $wp_term->taxonomy ) {
					return array( $wp_term );
				}

				return array();
			},
			array( $this, 'get_term_meta' )
		);

		return array_merge(
			$macros,
			$dynamic
		);
	}

	/**
	 * Checks if a term should have noindex attribute.
	 *
	 * @param \WP_Term $wp_term WP Term object.
	 *
	 * @return bool Whether the term should be set to noindex or not.
	 */
	protected function is_term_noindex( $wp_term ) {
		$noindex_in_settings = $this->get_noindex_setting( $wp_term->taxonomy );
		$noindex_overridden  = (bool) \smartcrawl_get_term_meta( $wp_term, $wp_term->taxonomy, 'wds_override_noindex' );
		$noindex_in_meta     = (bool) \smartcrawl_get_term_meta( $wp_term, $wp_term->taxonomy, 'wds_noindex' );

		if ( $noindex_in_settings ) {
			$noindex = ! $noindex_overridden;
		} else {
			$noindex = $noindex_in_meta;
		}

		return $noindex;
	}

	/**
	 * Checks if a term should have nofollow attribute.
	 *
	 * @param \WP_Term $wp_term WP Term object.
	 *
	 * @return bool True if term should have a nofollow attribute, false otherwise.
	 */
	protected function is_term_nofollow( $wp_term ) {
		$nofollow_in_settings = $this->get_nofollow_setting( $wp_term->taxonomy );
		$nofollow_overridden  = (bool) \smartcrawl_get_term_meta( $wp_term, $wp_term->taxonomy, 'wds_override_nofollow' );
		$nofollow_in_meta     = (bool) \smartcrawl_get_term_meta( $wp_term, $wp_term->taxonomy, 'wds_nofollow' );

		if ( $nofollow_in_settings ) {
			$nofollow = ! $nofollow_overridden;
		} else {
			$nofollow = $nofollow_in_meta;
		}

		return $nofollow;
	}

	/**
	 * Retrieves the robots meta tag for a specific page number.
	 *
	 * @param int $page_number The page number.
	 *
	 * @return string The robots meta tag.
	 */
	protected function get_robots_for_page_number( $page_number ) {
		$wp_term = $this->get_wp_term();

		if ( ! $wp_term ) {
			return '';
		}

		if (
			$this->show_robots_on_subsequent_pages_only( $this->get_taxonomy() )
			&& $page_number < 2
		) {
			return '';
		}

		$noindex  = $this->is_term_noindex( $wp_term );
		$nofollow = $this->is_term_nofollow( $wp_term );

		$noindex_string  = $noindex ? 'noindex' : 'index';
		$nofollow_string = $nofollow ? 'nofollow' : 'follow';

		return "{$noindex_string},{$nofollow_string}";
	}
}