<?php
/**
 * Metabox main class
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Entities\Entity;
use SmartCrawl\Settings;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Singleton;
use SmartCrawl\Cache\Post_Cache;
use SmartCrawl\Cache\Term_Cache;
use SmartCrawl\Controllers\Assets;
use SmartCrawl\Controllers;
use SmartCrawl\Sitemaps\Cache;

/**
 * Metabox rendering / handling class
 */
class Metabox extends Controllers\Controller {

	use Singleton;

	/**
	 * Post cache.
	 *
	 * @var Post_Cache
	 */
	private $post_cache;

	/**
	 * Term cache.
	 *
	 * @var Term_Cache
	 */
	private $term_cache;

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct();

		$this->post_cache = Post_Cache::get();
		$this->term_cache = Term_Cache::get();
	}

	/**
	 * Initializing method
	 */
	protected function init() {
		// WPSC integration.
		add_action( 'wpsc_edit_product', array( $this, 'rebuild_sitemap' ) );
		add_action( 'wpsc_rate_product', array( $this, 'rebuild_sitemap' ) );

		add_action( 'admin_menu', array( $this, 'smartcrawl_create_meta_box' ) );

		add_action( 'save_post', array( $this, 'save_postdata' ) );
		add_filter( 'attachment_fields_to_save', array( $this, 'smartcrawl_save_attachment_postdata' ) );

		add_action( 'init', array( $this, 'init_post_columns' ) );

		add_action( 'quick_edit_custom_box', array( $this, 'smartcrawl_quick_edit_dispatch' ), 20 );
		add_action( 'wp_ajax_wds_get_meta_fields', array( $this, 'json_wds_postmeta' ) );

		add_action( 'admin_print_scripts-post.php', array( $this, 'js_load_scripts' ) );
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'js_load_scripts' ) );

		/**
		 * TODO: perhaps we can combine wds_analysis_get_editor_analysis wds-metabox-preview and wds_metabox_update since they are used together so frequently
		 */

		/*
		 * When running analysis in metabox, or rendering metabox preview,
		 * always use overriding values passed in the request before values saved in the DB.
		 *
		 * This is done by filtering the metadata.
		 */
		add_filter( 'get_post_metadata', array( $this, 'filter_meta_title' ), 10, 4 );
		add_filter( 'get_post_metadata', array( $this, 'filter_meta_desc' ), 10, 4 );
		add_filter( 'get_post_metadata', array( $this, 'filter_focus_keyword' ), 10, 4 );

		/**
		 * Similar for taxonomy meta
		 */
		add_filter( 'wds-taxonomy-meta-wds_title', array( $this, 'filter_term_meta_title' ), 10, 2 );
		add_filter( 'wds-taxonomy-meta-wds_desc', array( $this, 'filter_term_meta_desc' ), 10, 2 );

		add_filter( 'default_hidden_columns', array( $this, 'hide_robots_column_by_default' ) );
		add_filter( 'page_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
	}

	/**
	 * Save opengraph meta.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $input   Input.
	 */
	public function save_opengraph_meta( $post_id, $input ) {
		$result = $this->get_social_meta( $input );

		if ( empty( $result ) ) {
			delete_post_meta( $post_id, '_wds_opengraph' );
		} else {
			update_post_meta( $post_id, '_wds_opengraph', $result );
		}
	}

	/**
	 * Save twitter meta.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $input   Input.
	 */
	public function save_twitter_post_meta( $post_id, $input ) {
		$twitter = $this->get_social_meta( $input );

		if ( empty( $twitter ) ) {
			delete_post_meta( $post_id, '_wds_twitter' );
		} else {
			update_post_meta( $post_id, '_wds_twitter', $twitter );
		}
	}


	/**
	 * Get social metadata value.
	 *
	 * @param array $input Input.
	 *
	 * @return array
	 */
	public function get_social_meta( array $input ) {
		$result = array();

		$disabled = ! empty( $input['disabled'] );
		if ( $disabled ) {
			$result['disabled'] = true;
		}

		if ( ! empty( $input['title'] ) ) {
			$result['title'] = \smartcrawl_sanitize_preserve_macros( $input['title'] );
		}

		if ( ! empty( $input['description'] ) ) {
			$result['description'] = \smartcrawl_sanitize_preserve_macros( $input['description'] );
		}

		if ( ! empty( $input['images'] ) && is_array( $input['images'] ) ) {
			$result['images'] = array();
			foreach ( $input['images'] as $img ) {
				$result['images'][] = is_numeric( $img ) ? intval( $img ) : esc_url_raw( $img );
			}
		}

		return $result;
	}

	/**
	 * Save robots meta data.
	 *
	 * @param \WP_Post $post         Post object.
	 * @param array    $request_data Request data.
	 */
	public function save_robots_meta( $post, $request_data ) {
		$all_options          = Settings::get_options();
		$post_type_noindexed  = (bool) \smartcrawl_get_array_value( $all_options, sprintf( 'meta_robots-noindex-%s', get_post_type( $post ) ) );
		$post_type_nofollowed = (bool) \smartcrawl_get_array_value( $all_options, sprintf( 'meta_robots-nofollow-%s', get_post_type( $post ) ) );
		/**
		 * If the user un-checks a checkbox and saves the post, the value for that checkbox will not be included inside $_POST array
		 * so we may have to delete the corresponding meta value manually.
		 */
		$checkbox_meta_items   = array( 'wds_meta-robots-adv' );
		$checkbox_meta_items[] = $post_type_nofollowed ? 'wds_meta-robots-follow' : 'wds_meta-robots-nofollow';
		$checkbox_meta_items[] = $post_type_noindexed ? 'wds_meta-robots-index' : 'wds_meta-robots-noindex';

		foreach ( $checkbox_meta_items as $item ) {
			$meta_key = "_$item";
			if ( ! isset( $request_data[ $item ] ) ) {
				delete_post_meta( $post->ID, $meta_key );
			} else {
				$value = $request_data[ $item ];

				if ( is_array( $value ) ) {
					$value = join( ',', array_keys( array_filter( $value ) ) );
				} else {
					$value = ! empty( $request_data[ $item ] );
				}

				update_post_meta( $post->ID, $meta_key, sanitize_text_field( $value ) );
			}
		}
	}

	/**
	 * Handle to manage post columns.
	 */
	public function init_post_columns() {
		foreach ( \smartcrawl_frontend_post_types() as $type ) {
			add_filter( "manage_{$type}_posts_columns", array( $this, 'smartcrawl_meta_column_heading' ), 20 );
			add_action( "manage_{$type}_posts_custom_column", array( $this, 'smartcrawl_meta_column_content' ), 20, 2 );
		}
	}

	/**
	 * Filters the array of row action links on the Pages list table.
	 *
	 * @param string[] $actions An array of row action links. Defaults are
	 *                          'Edit', 'Quick Edit', 'Restore', 'Trash',
	 *                          'Delete Permanently', 'Preview', and 'View'.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return array
	 */
	public function post_row_actions( $actions, $post ) {
		$onpage_active = Settings::get_setting( 'onpage' );
		if (
			$onpage_active
			&& ! empty( $actions )
			&& in_array( $post->post_type, \smartcrawl_frontend_post_types(), true )
		) {
			Simple_Renderer::render(
				'post-list/meta-details',
				array(
					'post' => $post,
				)
			);
		}

		return $actions;
	}

	/**
	 * Filters the default list of hidden columns.
	 *
	 * @param string[] $hidden Array of IDs of columns hidden by default.
	 *
	 * @return array
	 */
	public function hide_robots_column_by_default( $hidden ) {
		$hidden[] = 'smartcrawl-robots';

		return $hidden;
	}

	/**
	 * Enqueues frontend dependencies
	 */
	public function js_load_scripts() {
		if ( $this->is_editing_private_post_type() ) {
			return;
		}

		wp_enqueue_script( Assets::METABOX_COMPONENTS_JS );

		wp_enqueue_media();

		wp_enqueue_style( Assets::APP_CSS );
	}

	/**
	 * Handles page body class
	 *
	 * @param string $string Body classes this far.
	 *
	 * @return string
	 */
	public function admin_body_class( $string ) {
		return str_replace( 'wpmud', '', $string );
	}

	/**
	 * Handles actual metabox rendering.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function smartcrawl_meta_boxes( $post ) {
		Simple_Renderer::render(
			'metabox/metabox-main',
			array(
				'post' => $post,
			)
		);
	}

	/**
	 * Adds the metabox to the queue
	 */
	public function smartcrawl_create_meta_box() {
		$show = \user_can_see_seo_metabox();
		if ( function_exists( '\add_meta_box' ) ) {
			// Show branding for singular installs.
			$metabox_title = \smartcrawl_get_plugin_title();
			$post_types    = get_post_types(
				array(
					'show_ui' => true, // Only if it actually supports WP UI.
					'public'  => true, // ... and is public.
				)
			);
			foreach ( $post_types as $posttype ) {
				if ( $this->is_private_post_type( $posttype ) ) {
					continue;
				}
				if ( $show ) {
					add_meta_box(
						'wds-wds-meta-box',
						$metabox_title,
						array(
							&$this,
							'smartcrawl_meta_boxes',
						),
						$posttype,
						'normal',
						'high'
					);
				}
			}
		}
	}

	/**
	 * Handles attachment metadata saving
	 *
	 * @param array $data Data to save.
	 *
	 * @return array
	 */
	public function smartcrawl_save_attachment_postdata( $data ) {
		$request_data = $this->get_request_data();
		if ( empty( $request_data ) || empty( $data['post_ID'] ) || ! is_numeric( $data['post_ID'] ) ) {
			return $data;
		}
		$this->save_postdata( (int) $data['post_ID'] );

		return $data;
	}

	/**
	 * Get current post.
	 *
	 * @return array|\WP_Post|null
	 */
	private function get_post() {
		global $post;

		return $post;
	}

	/**
	 * Saves submitted metabox POST data
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function save_postdata( $post_id ) {
		$request_data = $this->get_request_data();

		if ( ! $post_id || empty( $request_data ) ) {
			return;
		}

		$post = $this->get_post();

		if ( empty( $post ) ) {
			$post = get_post( $post_id );
		}

		// Determine posted type.
		$post_type_rq = ! empty( $request_data['post_type'] ) ? sanitize_key( $request_data['post_type'] ) : false;

		if ( 'page' === $post_type_rq && ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$ptype = ! empty( $post_type_rq )
			? $post_type_rq
			: ( ! empty( $post->post_type ) ? $post->post_type : false );

		// Do not process post stuff for non-public post types.
		if ( ! in_array( $ptype, get_post_types( array( 'public' => true ) ), true ) ) {
			return;
		}

		if ( ! empty( $request_data['wds-opengraph'] ) ) {
			$this->save_opengraph_meta(
				$post_id,
				stripslashes_deep( $request_data['wds-opengraph'] )
			);
		}

		if ( ! empty( $request_data['wds-twitter'] ) ) {
			$this->save_twitter_post_meta(
				$post_id,
				stripslashes_deep( $request_data['wds-twitter'] )
			);
		}

		if ( isset( $request_data['wds_focus'] ) ) {
			$focus = stripslashes_deep( $request_data['wds_focus'] );

			if ( trim( $focus ) === '' ) {
				delete_post_meta( $post_id, '_wds_focus-keywords' );
			} else {
				$smartcrawl_post = Post_Cache::get()->get_post( $post_id );
				// Save keywords.
				$smartcrawl_post->set_focus_keywords_from_string( \smartcrawl_sanitize_preserve_macros( $focus ) );
			}
		}

		foreach ( $request_data as $key => $value ) {
			if ( in_array( $key, array( 'wds-opengraph', 'wds_focus', 'wds-twitter' ), true ) ) {
				continue;
			} // We already handled those.

			if ( ! preg_match( '/^wds_/', $key ) ) {
				continue;
			}

			$id   = "_$key";
			$data = $value;

			if ( is_array( $value ) ) {
				$data = join( ',', $value );
			}

			if ( $data ) {
				// Check redirect setting capability.
				if ( 'wds_redirect' === $key ) {
					if (
						function_exists( '\user_can_see_seo_metabox_301_redirect' ) &&
						\user_can_see_seo_metabox_301_redirect()
					) {
						update_post_meta( $post_id, $id, esc_url_raw( $data ) );
					}
					continue;
				}

				$value = 'wds_canonical' === $key ? esc_url_raw( $data ) : \smartcrawl_sanitize_preserve_macros( $data );
				update_post_meta( $post_id, $id, $value );
			} else {
				delete_post_meta( $post_id, $id );
			}
		}

		$this->save_robots_meta( $post, $request_data );

		if ( ! isset( $request_data['wds_autolinks-exclude'] ) ) {
			delete_post_meta( $post_id, '_wds_autolinks-exclude' );
		}

		update_post_meta(
			$post->ID,
			'_wds_trimmed_excerpt',
			\smartcrawl_get_trimmed_excerpt(
				$post->post_excerpt,
				$post->post_content
			)
		);

		do_action( 'wds_saved_postdata' );
	}

	/**
	 * Handles sitemap rebuilding
	 */
	public function rebuild_sitemap() {
		Cache::get()->invalidate();
	}

	/**
	 * Adds title and robots columns to post listing page
	 *
	 * @param array $columns Post list columns.
	 *
	 * @return array
	 */
	public function smartcrawl_meta_column_heading( $columns ) {
		$onpage_allowed = Settings::get_setting( Settings::COMP_ONPAGE ) && Admin_Settings::is_tab_allowed( Settings::TAB_ONPAGE );

		if ( $onpage_allowed ) {
			$columns['smartcrawl-robots'] = __( 'Robots Meta', 'wds' );
		}

		return $columns;
	}

	/**
	 * Puts out actual column bodies
	 *
	 * @param string $column_name Column ID.
	 * @param int    $id          Post ID.
	 *
	 * @return void
	 */
	public function smartcrawl_meta_column_content( $column_name, $id ) {
		if ( 'smartcrawl-robots' === $column_name ) {
			$meta_robots_arr = array(
				( \smartcrawl_get_value( 'meta-robots-noindex', $id ) ? 'noindex' : 'index' ),
				( \smartcrawl_get_value( 'meta-robots-nofollow', $id ) ? 'nofollow' : 'follow' ),
			);
			$meta_robots     = join( ',', $meta_robots_arr );

			echo esc_html( ucwords( str_replace( ',', ', ', $meta_robots ) ) );

			// Show additional robots data.
			$advanced = array_filter( array_map( 'trim', explode( ',', \smartcrawl_get_value( 'meta-robots-adv', $id ) ) ) );

			if ( ! empty( $advanced ) ) {
				$adv_map = array(
					'noodp'     => __( 'No ODP', 'wds' ),
					'noydir'    => __( 'No YDIR', 'wds' ),
					'noarchive' => __( 'No Archive', 'wds' ),
					'nosnippet' => __( 'No Snippet', 'wds' ),
				);

				$additional = array();

				foreach ( $advanced as $key ) {
					if ( ! empty( $adv_map[ $key ] ) ) {
						$additional[] = $adv_map[ $key ];
					}
				}

				if ( ! empty( $additional ) ) {
					echo '<br /><small>' . esc_html( join( ', ', $additional ) ) . '</small>';
				}
			}
		}
	}

	/**
	 * Dispatch quick edit areas
	 *
	 * @param string $column Column ID.
	 */
	public function smartcrawl_quick_edit_dispatch( $column ) {
		if ( 'smartcrawl-robots' === $column ) {
			Simple_Renderer::render(
				'post-list/quick-edit-onpage',
				array( 'show_title' => Settings::get_setting( 'disable-analysis-on-list' ) )
			);
		}
	}

	/**
	 * Handle postmeta getting requests
	 */
	public function json_wds_postmeta() {
		$data = $this->get_request_data();
		$id   = (int) $data['id'];

		die(
			wp_json_encode(
				array(
					'title'       => \smartcrawl_get_value( 'title', $id ),
					'description' => \smartcrawl_get_value( 'metadesc', $id ),
					'focus'       => \smartcrawl_get_value( 'focus-keywords', $id ),
				)
			)
		);
	}

	/**
	 * Short-circuits the return value of a meta title.
	 *
	 * @param mixed  $original The value to return.
	 * @param int    $post_id  ID of post the meta title is for.
	 * @param string $meta_key Metadata key.
	 * @param bool   $single   Whether to return only the first value of the specified key.
	 *
	 * @return mixed
	 */
	public function filter_meta_title( $original, $post_id, $meta_key, $single ) {
		if ( '_wds_title' !== $meta_key ) {
			return $original;
		}

		$post = $this->post_cache->get_post( $post_id );

		return $this->use_request_param_value( 'wds_title', $original, $single, $post );
	}

	/**
	 * Short-circuits the return value of a meta description.
	 *
	 * @param mixed  $original The value to return.
	 * @param int    $post_id  ID of post the meta description is for.
	 * @param string $meta_key Metadata key.
	 * @param bool   $single   Whether to return only the first value of the specified key.
	 *
	 * @return mixed
	 */
	public function filter_meta_desc( $original, $post_id, $meta_key, $single ) {
		if ( '_wds_metadesc' !== $meta_key ) {
			return $original;
		}

		$post = $this->post_cache->get_post( $post_id );

		return $this->use_request_param_value( 'wds_description', $original, $single, $post );
	}

	/**
	 * Short-circuits the return value of focus keywords.
	 *
	 * @param mixed  $original The value to return.
	 * @param int    $post_id  ID of post focus keywords are for.
	 * @param string $meta_key Metadata key.
	 * @param bool   $single   Whether to return only the first value of the specified key.
	 *
	 * @return mixed
	 */
	public function filter_focus_keyword( $original, $post_id, $meta_key, $single ) {
		if ( '_wds_focus-keywords' !== $meta_key ) {
			return $original;
		}

		$post = $this->post_cache->get_post( $post_id );

		return $this->use_request_param_value( 'wds_focus_keywords', $original, $single, $post );
	}

	/**
	 * Filters out the term meta title.
	 *
	 * @param mixed $original Original meta value.
	 * @param int   $term_id  Term id meta value is for.
	 *
	 * @return mixed
	 */
	public function filter_term_meta_title( $original, $term_id ) {
		$term = $this->term_cache->get_term( $term_id );

		return $this->use_request_param_value( 'wds_title', $original, true, $term );
	}

	/**
	 * Filters out the term meta description.
	 *
	 * @param mixed $original Original meta value.
	 * @param int   $term_id  Term id meta value is for.
	 *
	 * @return mixed
	 */
	public function filter_term_meta_desc( $original, $term_id ) {
		$term = $this->term_cache->get_term( $term_id );

		return $this->use_request_param_value( 'wds_description', $original, true, $term );
	}

	/**
	 * Manage to return value from request params.
	 *
	 * @param string      $request_param Key parameter of request.
	 * @param mixed       $default       Default value.
	 * @param bool        $single        Whether to return only the first value of the specified key.
	 * @param Entity|null $entity        Entity to get value.
	 *
	 * @return mixed
	 */
	private function use_request_param_value( $request_param, $default, $single, $entity ) {
		$overridden = \smartcrawl_get_array_value( $this->get_request_data(), $request_param );

		if ( is_null( $overridden ) || ! $entity ) {
			return $default;
		}

		$overridden = $entity->apply_macros( $overridden );

		if ( $single ) {
			return $overridden;
		} else {
			/**
			 * The WP function update_metadata doesn't update if the old value matches the new value.
			 * However, if the old value is an array and has more than one items it is not compared to the new value.
			 * So we are returning an empty string in the array to ensure that what we return here doesn't prevent meta from getting updated.
			 *
			 * @see update_metadata
			 */
			return array( $overridden, '' );
		}
	}

	/**
	 * Get request data of HTTP POST method.
	 *
	 * @return mixed.
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-metabox-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}

	/**
	 * Check whether post type is private or not.
	 *
	 * @param string $post_type_name The name of a registered post type.
	 *
	 * @return bool
	 */
	private function is_private_post_type( $post_type_name ) {
		$post_type = get_post_type_object( $post_type_name );

		return 'attachment' === $post_type->name || ! $post_type->show_ui || ! $post_type->public;
	}

	/**
	 * Check whether current admin page is for private post type or not.
	 *
	 * @return bool
	 */
	private function is_editing_private_post_type() {
		$current_screen = get_current_screen();
		if ( empty( $current_screen->post_type ) ) {
			return false;
		}

		return $this->is_private_post_type( $current_screen->post_type );
	}
}