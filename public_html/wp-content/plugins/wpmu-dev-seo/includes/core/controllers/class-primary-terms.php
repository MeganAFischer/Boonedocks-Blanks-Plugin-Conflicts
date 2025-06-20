<?php
/**
 * Initializes Primary categories' functionality.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Singleton;
use SmartCrawl\Cache\Object_Cache;

/**
 * Primary Categories class.
 */
class Primary_Terms extends Controller {

	use Singleton;

	/**
	 * Initialize the class.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'init', array( $this, 'register_primary_category' ) );
		add_action( 'save_post', array( $this, 'save_primary_terms' ), 10 );
		add_action( 'admin_footer', array( $this, 'include_selection_template' ) );
		add_filter( 'post_link_category', array( $this, 'post_link_category' ), 10, 3 );
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 9, 2 );
	}

	/**
	 * Whether or not this controller should run in the current context.
	 *
	 * @return bool
	 */
	public function should_run() {
		return ! defined( '\SMARTCRAWL_DISABLE_PRIMARY_TERMS' ) || ! \SMARTCRAWL_DISABLE_PRIMARY_TERMS;
	}

	/**
	 * Registers WDS Primary meta for REST API.
	 *
	 * @return void
	 */
	public function register_primary_category() {
		$post_types = get_post_types();

		$excluded_types = apply_filters_deprecated(
			'wds_primary_term_rest_excluded_post_types',
			array(
				array(
					'attachment',
					'revision',
					'nav_menu_item',
					'custom_css',
					'customize_changeset',
					'oembed_cache',
					'user_request',
					'wp_block',
				),
			),
			'6.6.1',
			'smartcrawl_primary_term_excluded_post_types',
			__( 'Please use our new filter `smartcrawl_primary_term_excluded_post_types` in SmartCrawl.', 'wds' )
		);

		$excluded_types = apply_filters( 'smartcrawl_primary_term_excluded_post_types', $excluded_types );

		foreach ( $post_types as $post_type ) {
			if ( in_array( $post_type, $excluded_types, true ) ) {
				continue;
			}

			$taxonomies = get_object_taxonomies( $post_type, 'objects' );

			if ( ! empty( $taxonomies ) && is_array( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy_name => $taxonomy ) {
					if ( ! $taxonomy->hierarchical ) {
						continue;
					}

					register_post_meta(
						$post_type,
						'wds_primary_' . $taxonomy_name,
						array(
							'show_in_rest' => true,
							'single'       => true,
							'type'         => 'integer',
						)
					);
				}
			}
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Registers scripts and styles.
	 *
	 * @param string $hook_name The name of the action to add the callback to.
	 */
	public function register_assets( $hook_name ) {
		if ( 'post-new.php' !== $hook_name && 'post.php' !== $hook_name ) {
			return;
		}

		$post_taxonomies = $this->get_post_taxonomies();

		wp_localize_script(
			Assets::METABOX_COMPONENTS_JS,
			'_wds_primary',
			array(
				'taxonomies_js' => array_map(
					array( $this, 'get_taxonomy_data' ),
					$post_taxonomies
				),
			)
		);
	}

	/**
	 * Retrieves the taxonomies associated with a post.
	 *
	 * @param int $post_id The ID of the post. Default is 0.
	 *
	 * @return \WP_Taxonomy[] An array of taxonomy objects.
	 */
	public function get_post_taxonomies( $post_id = 0 ) {
		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		$cache = Object_Cache::get();

		$taxonomies = $cache->get_cache( 'wds_post_taxonomies_' . $post_id, 'wds-primary-terms' );

		if ( empty( $taxonomies ) ) {
			$post_type  = get_post_type( $post_id );
			$taxonomies = get_object_taxonomies( $post_type, 'objects' );

			foreach ( $taxonomies as $taxonomy_name => $taxonomy ) {
				if ( ! $taxonomy->hierarchical ) {
					unset( $taxonomies[ $taxonomy_name ] );
				}
			}

			$cache->set_cache( 'wds_post_taxonomies_' . $post_id, $taxonomies, 'wds-primary-terms' );
		}

		return $taxonomies;
	}

	/**
	 * Returns taxonomy data for JS.
	 *
	 * @param \WP_Taxonomy $taxonomy WP Taxonomy object.
	 *
	 * @return array
	 */
	public function get_taxonomy_data( $taxonomy ) {
		$terms = get_terms( array( 'taxonomy' => $taxonomy->name ) );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			$terms = array();
		}
		return array(
			'name'     => $taxonomy->name,
			'title'    => $taxonomy->labels->singular_name,
			'primary'  => $this->get_primary_term( $taxonomy->name ),
			'restBase' => $taxonomy->rest_base,
			'terms'    => array_map(
				array( $this, 'get_term_data' ),
				$terms
			),
		);
	}

	/**
	 * Returns term data for JS.
	 *
	 * @param \WP_Term $term WP term object.
	 *
	 * @return array
	 */
	public function get_term_data( $term ) {
		return array(
			'id'   => $term->term_id,
			'name' => $term->name,
		);
	}

	/**
	 * Retrieves the primary term for a given taxonomy.
	 *
	 * @param string $taxonomy The taxonomy name.
	 *
	 * @return int|false The ID of the primary term if it exists, otherwise false.
	 *
	 * @global WP_Post $post The current post object.
	 */
	public function get_primary_term( $taxonomy ) {
		global $post;

		$primary_term = (int) get_post_meta( $post->ID, 'wds_primary_' . $taxonomy, true );

		$post_terms = $this->get_post_terms( $taxonomy );

		if (
			in_array(
				$primary_term,
				wp_list_pluck( $post_terms, 'term_id' ),
				true
			)
		) {
			return $primary_term;
		}

		return false;
	}

	/**
	 * Saves primary terms on post submit.
	 *
	 * @param int $post_id Post id.
	 */
	public function save_primary_terms( $post_id ) {
		$post_taxonomies = $this->get_post_taxonomies( $post_id );

		foreach ( $post_taxonomies as $post_taxonomy ) {
			$this->save_primary_term( $post_id, $post_taxonomy );
		}
	}

	/**
	 * Saves the primary term for a specific post and taxonomy.
	 *
	 * @param int          $post_id The ID of the post.
	 * @param \WP_Taxonomy $taxonomy The taxonomy object.
	 *
	 * @return void
	 */
	public function save_primary_term( $post_id, $taxonomy ) {

		$primary_term = false;

		if ( isset( $_POST[ 'wds_primary_term_' . $taxonomy->name ] ) ) {
			$primary_term = (int) sanitize_text_field(
				wp_unslash( $_POST[ 'wds_primary_term_' . $taxonomy->name ] )
			);

		}

		if ( ! $primary_term ) {
			return;
		}

		check_admin_referer(
			'wds-save-primary-term',
			'wds_save_primary_' . $taxonomy->name . '_nonce'
		);

		update_post_meta(
			$post_id,
			'wds_primary_' . $taxonomy->name,
			$primary_term
		);
	}

	/**
	 * Retrieves terms of a specific taxonomy for current post.
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return \WP_Term[]
	 */
	public function get_post_terms( $taxonomy ) {
		global $post;

		$terms = get_the_terms( $post->ID, $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * Filters the permalink for a post of a custom post type.
	 *
	 * @param string   $post_link The post's permalink.
	 * @param \WP_Post $post      The post in question.
	 *
	 * @return string
	 */
	public function post_type_link( $post_link, $post ) {
		$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );
		$taxonomies = wp_filter_object_list( $taxonomies, array( 'hierarchical' => true ), 'and', 'name' );

		foreach ( $taxonomies as $taxonomy ) {
			$this->sanitize_post_type_link( $post_link, $post, $taxonomy );
		}

		return $post_link;
	}

	/**
	 * Filters the permalink for a post of a custom post type.
	 *
	 * @param string   $post_link The post's permalink.
	 * @param \WP_Post $post      The post in question.
	 * @param string   $taxonomy  The post taxonomy.
	 */
	public function sanitize_post_type_link( $post_link, $post, $taxonomy ) {
		$primary_term = $this->make_primary_term( $post->ID, $taxonomy );

		if ( ! $primary_term ) {
			$terms = get_the_terms( $post->ID, $taxonomy );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return $post_link;
			}
			$primary_term = reset( $terms );
		}

		$term_hierarchy = $this->get_hierarchical_link( $primary_term, $taxonomy );

		if ( $term_hierarchy ) {
			$post_link = str_replace( '%' . $taxonomy . '%', trim( $term_hierarchy, '/' ), $post_link );
		}

		return $post_link;
	}

	/**
	 * Returns the hierarchical link for a given term.
	 *
	 * @param \WP_Term $term The term object or WordPress error object.
	 * @param string   $taxonomy The taxonomy name.
	 *
	 * @return string The hierarchical link for the given term.
	 */
	public function get_hierarchical_link( $term, $taxonomy ) {
		$hierarchical_path = get_term_parents_list(
			$term->term_id,
			$taxonomy,
			array(
				'separator' => '/',
				'link'      => false,
				'format'    => 'slug',
			)
		);

		if ( is_wp_error( $hierarchical_path ) ) {
			error_log( 'Error in get_term_parents_list: ' . $hierarchical_path->get_error_message() );

			return '';
		}

		return trim( $hierarchical_path, '/' );
	}

	/**
	 * Filters the category that gets used in the %category% permalink token.
	 *
	 * @param \WP_Term $term  The category to use in the permalink.
	 * @param array    $terms Array of all categories (WP_Term objects) associated with the post.
	 * @param \WP_Post $post  The post in question.
	 *
	 * @return Object $primary_term
	 */
	public function post_link_category( $term, $terms, $post ) {
		$primary_term = $this->make_primary_term( $term->taxonomy, $post->ID );

		if ( false === $primary_term ) {
			return $term;
		}

		$term_ids = array_column( $terms, 'term_id' );
		if ( ! is_object( $primary_term ) || ! in_array( $primary_term->term_id, $term_ids, true ) ) {
			return $term;
		}

		return $primary_term;
	}

	/**
	 * Generates the primary term for a given taxonomy and post ID if it doesn't exist and returns it.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @param int    $post_id The post ID.
	 *
	 * @return \WP_Term|false The primary term if found, false otherwise.
	 */
	public function make_primary_term( $taxonomy, $post_id ) {
		$primary = get_post_meta( $post_id, 'wds_primary_' . $taxonomy, true );

		if ( ! $primary ) {
			return false;
		}

		$primary = get_term( $primary, $taxonomy );

		// Sets first term as primary.
		if ( ! $primary instanceof \WP_Term ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy );

			if ( isset( $terms[0] ) && $terms[0] instanceof \WP_Term ) {
				$primary = $terms[0];
				// Automatically assign first term into primary term.
				update_post_meta( $post_id, 'wds_primary_' . $taxonomy, $primary->term_id );
			}
		}

		return $primary instanceof \WP_Term ? $primary : false;
	}

	/**
	 * Html Template
	 */
	public function include_selection_template() {
		?>
		<script type="text/html" id="tmpl-wds-select-primary-term">
			<div class="wds-primary-term">
				<input type="hidden" value="{{data.taxonomy.primary}}" id="wds_primary_term_{{data.taxonomy.name}}" name="wds_primary_term_{{data.taxonomy.name}}"/>
				<?php wp_nonce_field( 'wds-save-primary-term', 'wds_save_primary_{{data.taxonomy.name}}_nonce' ); ?>
			</div>
		</script>
		<?php
	}
}