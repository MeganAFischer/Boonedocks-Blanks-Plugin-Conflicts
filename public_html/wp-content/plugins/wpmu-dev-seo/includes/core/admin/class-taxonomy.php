<?php
/**
 * Taxonomy management controller.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin;

use SmartCrawl\Checks\Metadesc_Length;
use SmartCrawl\Controllers\Assets;
use SmartCrawl\Settings;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Singleton;
use SmartCrawl\Controllers;

/**
 * Class Taxonomy
 */
class Taxonomy extends Controllers\Controller {

	use Singleton;

	/**
	 * Initialize the controller.
	 *
	 * @return void
	 */
	protected function init() {
		$taxonomy = \smartcrawl_get_array_value( $_GET, 'taxonomy' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( is_admin() && ! empty( $taxonomy ) ) {
			add_action( sanitize_key( $taxonomy ) . '_edit_form', array( &$this, 'term_additions_form' ), 10, 2 );
		}

		add_action( 'edit_term', array( &$this, 'update_term' ), 10, 3 );
		add_action( 'wp_ajax_wds-term-form-preview', array( $this, 'json_create_preview' ) );

		$seo_analysis_enabled = Settings::get_setting( 'analysis-seo' );

		if ( $seo_analysis_enabled ) {
			// SEO status.
			add_action( 'current_screen', array( $this, 'init_seo_column' ) );
		}
	}

	/**
	 * Register hooks for SEO status column.
	 *
	 * @since 3.4.0
	 *
	 * @param \WP_Screen $screen Current screen.
	 *
	 * @return void
	 */
	public function init_seo_column( $screen ) {
		// We need taxonomy.
		if ( empty( $screen->taxonomy ) ) {
			return;
		}

		$taxonomy = get_taxonomy( $screen->taxonomy );
		// Check for permission.
		if ( ! current_user_can( $taxonomy->cap->manage_terms ) ) {
			return;
		}

		add_filter( "manage_{$screen->id}_columns", array( $this, 'add_seo_column' ) );
		add_filter( "manage_{$screen->taxonomy}_custom_column", array( $this, 'render_seo_column' ), 10, 3 );
	}

	/**
	 * Add SEO status column to taxonomy list.
	 *
	 * @since 3.4.0
	 *
	 * @param array $columns Columns.
	 *
	 * @return array
	 */
	public function add_seo_column( $columns ) {
		$columns['wds-seo-status'] = __( 'SEO Status', 'wds' );

		return $columns;
	}

	/**
	 * Show content for SEO status column.
	 *
	 * @param string $value       Content.
	 * @param string $column_name Column name.
	 * @param int    $term_id     Term ID.
	 *
	 * @return string
	 */
	public function render_seo_column( $value, $column_name, $term_id ) {
		// Only for our column.
		if ( 'wds-seo-status' === $column_name ) {
			$term = get_term( $term_id );
			$desc = $this->get_meta_desc( $term_id, $term->taxonomy );
			// If no description.
			if ( empty( $desc ) ) {
				$icon  = 'dismiss';
				$color = '#dc3232';
				$title = __( 'Taxonomy meta description missing', 'wds' );
			} else {
				$valid_length = $this->get_length_checker()->is_within_char_length(
					$desc,
					$this->get_length_checker()->get_min(),
					$this->get_length_checker()->get_max()
				);
				$icon         = true === $valid_length ? 'yes-alt' : 'warning';
				$color        = true === $valid_length ? '#46b450' : '#ffb900';
				$title        = true === $valid_length ?
					__( 'Meta description is added, and it\'s the right length!', 'wds' )
					: sprintf(
					// translators: %1$d minimum length, %2$d maximum length.
						__( 'Meta description length should be between %1$d - %2$d characters', 'wds' ),
						$this->get_length_checker()->get_min(),
						$this->get_length_checker()->get_max()
					);
			}

			$value = sprintf( '<span class="dashicons dashicons-%s" style="color:%s" title="%s"></span>', $icon, $color, $title );
		}

		return $value;
	}

	/**
	 * Get json preview content.
	 *
	 * @return void
	 */
	public function json_create_preview() {
		$data    = $this->get_request_data();
		$term_id = (int) \smartcrawl_get_array_value( $data, 'term_id' );
		$result  = array( 'success' => false );

		if ( empty( $term_id ) ) {
			wp_send_json( $result );
		}

		$result['success'] = true;
		$result['markup']  = Simple_Renderer::load(
			'term/term-google-preview',
			array(
				'term' => get_term( $term_id ),
			)
		);

		wp_send_json( $result );
	}

	/**
	 * Get the current request data.
	 *
	 * @return array|mixed
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-metabox-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}

	/**
	 * Form to display on term page.
	 *
	 * @param \WP_Term $term     Term object.
	 * @param string   $taxonomy Taxonomy name.
	 *
	 * @return void
	 */
	public function term_additions_form( $term, $taxonomy ) {
		$taxonomy_object = get_taxonomy( $taxonomy );
		if ( ! $taxonomy_object->public ) {
			return;
		}

		$smartcrawl_options = Settings::get_options();
		$tax_meta           = get_option( 'wds_taxonomy_meta' );

		if ( isset( $tax_meta[ $taxonomy ][ $term->term_id ] ) ) {
			$tax_meta = $tax_meta[ $taxonomy ][ $term->term_id ];
		}

		$taxonomy_labels = $taxonomy_object->labels;

		$global_noindex  = ! empty( $smartcrawl_options[ 'meta_robots-noindex-' . $term->taxonomy ] )
			? $smartcrawl_options[ 'meta_robots-noindex-' . $term->taxonomy ]
			: false;
		$global_nofollow = ! empty( $smartcrawl_options[ 'meta_robots-nofollow-' . $term->taxonomy ] )
			? $smartcrawl_options[ 'meta_robots-nofollow-' . $term->taxonomy ]
			: false;

		wp_enqueue_style( Assets::APP_CSS );
		wp_enqueue_script( Assets::ONPAGE_JS );
		wp_enqueue_script( Assets::TERM_FORM_JS );
		wp_enqueue_media();

		Simple_Renderer::render(
			'term/term-form',
			array(
				'taxonomy_object' => $taxonomy_object,
				'taxonomy_labels' => $taxonomy_labels,
				'term'            => $term,
				'global_noindex'  => $global_noindex,
				'global_nofollow' => $global_nofollow,
				'tax_meta'        => $tax_meta,
				'title_key'       => $smartcrawl_options[ 'title-' . $term->taxonomy ],
				'desc_key'        => $smartcrawl_options[ 'metadesc-' . $term->taxonomy ],
			)
		);
	}

	/**
	 * Manage term update action.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Taxonomy ID.
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return void
	 */
	public function update_term( $term_id, $tt_id, $taxonomy ) {
		$taxonomy_object = get_taxonomy( $taxonomy );
		if ( ! $taxonomy_object->public ) {
			return;
		}

		$smartcrawl_options = Settings::get_options();

		$tax_meta  = get_option( 'wds_taxonomy_meta', array() );
		$post_data = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-tag_' . $term_id )
			? stripslashes_deep( $_POST )
			: array();

		foreach ( array( 'title', 'desc', 'bctitle', 'canonical' ) as $key ) {
			$value = isset( $post_data[ 'wds_' . $key ] ) ? $post_data[ 'wds_' . $key ] : '';
			if ( 'canonical' === $key ) {
				$value = esc_url_raw( $value );
			} else {
				$value = \smartcrawl_sanitize_preserve_macros( $value );
			}
			$tax_meta[ $taxonomy ][ $term_id ][ 'wds_' . $key ] = $value;
		}

		foreach ( array( 'noindex', 'nofollow' ) as $key ) {
			if ( empty( $smartcrawl_options[ 'meta_robots-' . $key . '-' . $taxonomy ] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ][ 'wds_' . $key ] = isset( $post_data[ 'wds_' . $key ] ) && (bool) $post_data[ 'wds_' . $key ];
			} else {
				$tax_meta[ $taxonomy ][ $term_id ][ 'wds_override_' . $key ] = isset( $post_data[ 'wds_override_' . $key ] ) && (bool) $post_data[ 'wds_override_' . $key ];
			}
		}

		if ( ! empty( $post_data['wds-opengraph'] ) ) {
			$data = is_array( $post_data['wds-opengraph'] ) ? stripslashes_deep( $post_data['wds-opengraph'] ) : array();

			$tax_meta[ $taxonomy ][ $term_id ]['opengraph'] = array();
			if ( ! empty( $data['title'] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ]['opengraph']['title'] = \smartcrawl_sanitize_preserve_macros( $data['title'] );
			}
			if ( ! empty( $data['description'] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ]['opengraph']['description'] = \smartcrawl_sanitize_preserve_macros( $data['description'] );
			}
			if ( ! empty( $data['images'] ) && is_array( $data['images'] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ]['opengraph']['images'] = array();
				foreach ( $data['images'] as $img ) {
					$tax_meta[ $taxonomy ][ $term_id ]['opengraph']['images'][] = is_numeric( $img ) ? intval( $img ) : esc_url_raw( $img );
				}
			}
			$tax_meta[ $taxonomy ][ $term_id ]['opengraph']['disabled'] = ! empty( $data['disabled'] );
		}

		if ( ! empty( $post_data['wds-twitter'] ) ) {
			$data = is_array( $post_data['wds-twitter'] ) ? stripslashes_deep( $post_data['wds-twitter'] ) : array();

			$tax_meta[ $taxonomy ][ $term_id ]['twitter'] = array();
			if ( ! empty( $data['title'] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ]['twitter']['title'] = \smartcrawl_sanitize_preserve_macros( $data['title'] );
			}
			if ( ! empty( $data['description'] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ]['twitter']['description'] = \smartcrawl_sanitize_preserve_macros( $data['description'] );
			}
			if ( ! empty( $data['images'] ) && is_array( $data['images'] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ]['twitter']['images'] = array();
				foreach ( $data['images'] as $img ) {
					$tax_meta[ $taxonomy ][ $term_id ]['twitter']['images'][] = is_numeric( $img ) ? intval( $img ) : esc_url_raw( $img );
				}
			}
			$tax_meta[ $taxonomy ][ $term_id ]['twitter']['disabled'] = ! empty( $data['disabled'] );
		}

		update_option( 'wds_taxonomy_meta', $tax_meta );

		if ( function_exists( '\w3tc_flush_all' ) ) {
			// Use W3TC API v0.9.5+.
			\w3tc_flush_all();
		} elseif ( defined( 'W3TC_DIR' ) && is_readable( \W3TC_DIR . '/lib/W3/ObjectCache.php' ) ) {
			// Old (very old) API.
			require_once \W3TC_DIR . '/lib/W3/ObjectCache.php';
			$w3_objectcache = &\W3_ObjectCache::instance();

			$w3_objectcache->flush();
		}
	}

	/**
	 * Get the class instance to check length.
	 *
	 * @since 3.4.0
	 *
	 * @return Metadesc_Length
	 */
	private function get_length_checker() {
		static $desc_len = null;
		if ( null === $desc_len ) {
			$desc_len = new Metadesc_Length();
		}

		return $desc_len;
	}

	/**
	 * Get the meta description for taxonomy.
	 *
	 * @since 3.4.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return string
	 */
	private function get_meta_desc( $term_id, $taxonomy = '' ) {
		// Get meta descriptions for taxonomies.
		$tax_meta = get_option( 'wds_taxonomy_meta' );

		if ( ! empty( $tax_meta[ $taxonomy ][ $term_id ]['wds_desc'] ) ) {
			// Custom description set for SEO.
			return $tax_meta[ $taxonomy ][ $term_id ]['wds_desc'];
		} else {
			$term = get_term( $term_id, $taxonomy );

			// Get default term description.
			return $term->description;
		}
	}
}