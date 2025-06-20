<?php
/**
 * Analysis (SEO and Readability) wiring hub.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Checks;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Cache\Post_Cache;
use SmartCrawl\Models\Analysis as Analysis_Model;

/**
 * Analysis controller
 *
 * At the same time, some rendering duties.
 */
class Analysis extends Controller {

	use Singleton;

	/**
	 * Bind listening actions
	 *
	 * @return bool
	 */
	protected function init() {
		// Fetch analysis data via AJAX POST request.
		add_action( 'wp_ajax_wds-analysis-get-data', array( $this, 'retrieve_post_analysis_data' ) );
		add_action( 'wp_ajax_wds-analysis-get-markup', array( $this, 'retrieve_post_analysis_markup' ) );
		add_action( 'wp_ajax_wds-analysis-recheck', array( $this, 'retrieve_post_analysis_recheck' ) );
		add_action( 'wp_ajax_wds_analysis_get_editor_analysis', array( $this, 'retrieve_post_analysis' ) );
		add_action( 'wp_ajax_wds_analysis_add_keyword', array( $this, 'add_post_keyword' ) );
		add_action( 'wp_ajax_wds_analysis_remove_keyword', array( $this, 'remove_post_keyword' ) );
		add_action( 'wp_ajax_wds_analysis_ignore_check', array( $this, 'json_set_ignore_check' ) );
		add_action( 'wp_ajax_wds_analysis_unignore_check', array( $this, 'json_unset_ignore_check' ) );

		// Set up CPT columns filtering.
		add_action( 'admin_init', array( $this, 'set_up_post_columns' ) );

		add_action( 'parse_query', array( $this, 'apply_analysis_post_list_filter' ) );

		add_action( 'post_submitbox_misc_actions', array( $this, 'add_postbox_fields' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'inject_script_dependencies' ) );

		return true;
	}

	/**
	 * Unbinds listening actions
	 *
	 * @return bool
	 */
	protected function terminate() {
		remove_action( 'wp_ajax_wds-analysis-get-data', array( $this, 'retrieve_post_analysis_data' ) );
		remove_action( 'wp_ajax_wds-analysis-get-markup', array( $this, 'retrieve_post_analysis_markup' ) );
		remove_action( 'wp_ajax_wds-analysis-recheck', array( $this, 'retrieve_post_analysis_recheck' ) );
		remove_action( 'wp_ajax_wds_analysis_get_editor_analysis', array( $this, 'retrieve_post_analysis' ) );
		remove_action( 'wp_ajax_wds_analysis_add_keyword', array( $this, 'add_post_keyword' ) );
		remove_action( 'wp_ajax_wds_analysis_remove_keyword', array( $this, 'remove_post_keyword' ) );
		remove_action( 'wp_ajax_wds_analysis_ignore_check', array( $this, 'json_set_ignore_check' ) );
		remove_action( 'wp_ajax_wds_analysis_unignore_check', array( $this, 'json_unset_ignore_check' ) );
		remove_action( 'admin_init', array( $this, 'set_up_post_columns' ) );
		remove_action( 'admin_enqueue_scripts', array( $this, 'inject_script_dependencies' ) );
		remove_action( 'post_submitbox_misc_actions', array( $this, 'add_postbox_fields' ) );

		return true;
	}

	/**
	 * Applies filtering by analysis threshold
	 *
	 * Used on post list table pages
	 *
	 * @param object $query Query to augment.
	 *
	 * @return void
	 */
	public function apply_analysis_post_list_filter( $query ) {
		$data = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $data['wds_analysis_threshold'] ) && ! isset( $data['wds_readability_threshold'] ) ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! is_object( $screen ) || empty( $screen->base ) ) {
			return;
		}

		if ( 'edit' !== $screen->base ) {
			return;
		}

		$pt = ! empty( $screen->post_type ) ? $screen->post_type : false;

		if ( $query->get( 'post_type' ) !== $pt ) {
			return;
		}

		$meta_queries = $query->get( 'meta_query', array() );

		// Set SEO analysis threshold meta query.
		if ( isset( $data['wds_analysis_threshold'] ) ) {
			$raw = ! empty( $data['wds_analysis_threshold'] ) && is_numeric( $data['wds_analysis_threshold'] )
				? (int) $data['wds_analysis_threshold'] . ''
				: '69';

			if ( empty( $raw ) ) {
				return;
			}

			$rx = '';
			foreach ( str_split( $raw ) as $char ) {
				$rx .= ! empty( $char )
					? '[0-' . (int) $char . ']?'
					: '0?';
			}
			$rx             = substr( $rx, 0, strlen( $rx ) - 1 ); // Strip last question mark.
			$meta_queries[] = array(
				'key'     => Analysis_Model::META_KEY_ANALYSIS,
				'value'   => '[[:punct:]]percentage[[:punct:]];i:' . $rx . ';',
				'compare' => 'REGEXP',
			);
		}

		// Set readability meta query.
		if ( isset( $data['wds_readability_threshold'] ) ) {
			// Filter by just readable/not readable.
			$readable       = ! empty( $data['wds_readability_threshold'] ) ? 1 : 0;
			$meta_queries[] = array(
				'key'     => Analysis_Model::META_KEY_READABILITY,
				'value'   => '[[:punct:]]is_readable[[:punct:]];b:' . $readable . ';',
				'compare' => 'REGEXP',
			);
		}

		if ( ! empty( $meta_queries ) ) {
			$query->set( 'meta_query', $meta_queries );
		}
	}

	/**
	 * Enqueues admin scripts
	 *
	 * @param string $hook Page hook.
	 *
	 * @return void
	 */
	public function inject_script_dependencies( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		wp_enqueue_script( Assets::WP_POST_LIST_TABLE_JS );
		wp_enqueue_style( Assets::WP_POST_LIST_TABLE_CSS );
	}

	/**
	 * Sets up column filtering actions
	 *
	 * @return void
	 */
	public function set_up_post_columns() {
		if ( ! Settings::get_setting( 'disable-analysis-on-list' ) ) {
			// Set up column filtering.
			foreach ( \smartcrawl_frontend_post_types() as $type ) {
				add_filter( "manage_{$type}_posts_columns", array( $this, 'add_analysis_columns' ) );
				add_action( "manage_{$type}_posts_custom_column", array( $this, 'add_analysis_column_data' ), 10, 2 );
			}
			// Add quick edit for focus keyword.
			add_action( 'quick_edit_custom_box', array( $this, 'add_quick_edit_focus_keyword_field' ) );
		}
	}

	/**
	 * Add Focus Keyword section.
	 *
	 * @param string $column Column name.
	 *
	 * @return void
	 */
	public function add_quick_edit_focus_keyword_field( $column ) {
		if ( 'seo' === $column ) {
			Simple_Renderer::render( 'post-list/quick-edit-seo-analysis' );
		}
	}

	/**
	 * Injects custom columns for analysis
	 *
	 * @param array $columns Columns hash.
	 *
	 * @return array
	 */
	public function add_analysis_columns( $columns ) {
		if ( Settings::get_setting( 'analysis-seo' ) ) {
			$columns['seo'] = __( 'SEO', 'wds' );
		}
		if ( Settings::get_setting( 'analysis-readability' ) ) {
			$columns['readability'] = __( 'Readability', 'wds' );
		}

		return $columns;
	}

	/**
	 * Adds custom columns analysis data
	 *
	 * @param string $cid     Column ID.
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public function add_analysis_column_data( $cid, $post_id ) {
		if ( ! in_array( $cid, array( 'seo', 'readability' ), true ) ) {
			return;
		}

		$result = $this->get_post_analysis_result_markup( $post_id );

		if ( 'seo' === $cid ) {
			echo wp_kses_post( $result['seo'] );
		}

		if ( 'readability' === $cid ) {
			echo wp_kses_post( $result['readability'] );
		}
	}

	/**
	 * Gets post analysis results markup for posts column
	 *
	 * @param int $post_id ID of the post.
	 *
	 * @return array List of column markups
	 */
	public function get_post_analysis_result_markup( $post_id ) {
		$model  = new Analysis_Model( $post_id );
		$result = array();
		$na     = '<div class="wds-analysis wds-status-invalid"><span>' . esc_html( __( 'N/A', 'wds' ) ) . '</span></div>';

		if ( ! $model->has_post_data( Analysis_Model::DATA_ANALYSIS ) ) {
			$result['seo'] = $na;
		} else {
			$data                     = $model->get_post_data( Analysis_Model::DATA_ANALYSIS );
			$focus_keywords           = \smartcrawl_get_value( 'focus-keywords', $post_id );
			$focus_keywords_available = ! empty( $focus_keywords );
			if ( ! $focus_keywords_available ) {
				$result['seo'] = Simple_Renderer::load(
					'post-list/post-seo-analysis-errors',
					array(
						'focus_missing' => true,
						'status_class'  => 'wds-status-invalid',
						'errors'        => array(
							'focus-keyword-missing' => esc_html__( 'You need to add focus keyphrases to see recommendations for this article.', 'wds' ),
						),
					)
				);
			} elseif ( empty( $data['errors'] ) ) {
				$result['seo'] = Simple_Renderer::load( 'post-list/post-seo-analysis-good' );
			} else {
				$result['seo'] = Simple_Renderer::load(
					'post-list/post-seo-analysis-errors',
					array(
						'errors' => $data['errors'],
					)
				);
			}
		}

		if ( ! $model->has_post_data( Analysis_Model::DATA_READABILITY ) ) {
			$result['readability'] = $na;
		} else {
			$data                  = $model->get_post_data( Analysis_Model::DATA_READABILITY );
			$readability_score     = intval( ceil( \smartcrawl_get_array_value( $data, 'score' ) ) );
			$readability_ignored   = Checks::is_readability_ignored( $post_id );
			$readability_state     = $model->get_kincaid_readability_state( $readability_score, $readability_ignored );
			$readability_class     = sprintf(
				'wds-status-%s',
				$readability_state
			);
			$readability_level     = $model->get_readability_level( false );
			$tag                   = \smartcrawl_get_array_value(
				$model->get_readability_levels_map(),
				array( $readability_level, 'tag' )
			);
			$tag                   = empty( $tag )
				? esc_html__( 'N/A', 'wds' )
				: $tag;
			$result['readability'] = '<div class="wds-analysis ' . $readability_class . '" title="' . $readability_score . '">' . esc_html( $tag ) . '</div>';
			if ( empty( $readability_score ) ) {
				$result['readability'] .= '<div class="wds-analysis-details">' . $readability_level . '</div>';
			}
		}

		return $result;
	}

	/**
	 * Handles check ignoring front-end requests
	 *
	 * @return void
	 */
	public function json_set_ignore_check() {
		$data = $this->get_request_data();

		if ( empty( $data['post_id'] ) || ! is_numeric( $data['post_id'] ) ) {
			wp_send_json_error();
		}

		if ( empty( $data['check_id'] ) ) {
			wp_send_json_error();
		}

		Checks::add_ignored_check( (int) $data['post_id'], sanitize_text_field( $data['check_id'] ) );

		$model = new Analysis_Model( (int) $data['post_id'] );
		$model->clear_cached_data();

		wp_send_json_success();
	}

	/**
	 * Handles check de-ignoring front-end requests
	 *
	 * @return void
	 */
	public function json_unset_ignore_check() {
		$data = $this->get_request_data();

		if ( empty( $data['post_id'] ) || ! is_numeric( $data['post_id'] ) ) {
			wp_send_json_error();
		}

		if ( empty( $data['check_id'] ) ) {
			wp_send_json_error();
		}

		Checks::remove_ignored_check( (int) $data['post_id'], sanitize_text_field( $data['check_id'] ) );

		$model = new Analysis_Model( (int) $data['post_id'] );
		$model->clear_cached_data();

		wp_send_json_success();
	}

	/**
	 * Add a new keyword to the post.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public function add_post_keyword() {
		$data = $this->get_request_data();

		if ( empty( $data['post_id'] ) || ! is_numeric( $data['post_id'] ) || empty( $data['keyword'] ) ) {
			wp_send_json_error();
		}

		// Get required data.
		$keyword = (string) \smartcrawl_get_array_value( $data, 'keyword' );
		$post_id = (int) \smartcrawl_get_array_value( $data, 'post_id' );

		// Permission check.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error();
		}

		$smartcrawl_post = Post_Cache::get()->get_post( $post_id );

		if ( empty( $smartcrawl_post ) ) {
			wp_send_json_error();
		} else {
			$smartcrawl_post->add_focus_keyword( $keyword );
			$keywords = \smartcrawl_get_value( 'focus-keywords', $post_id );
			wp_send_json_success( empty( $keywords ) ? '' : $keywords );
		}
	}

	/**
	 * Remove an existing keyword from the post.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public function remove_post_keyword() {
		$data = $this->get_request_data();

		if ( empty( $data['post_id'] ) || ! is_numeric( $data['post_id'] ) || empty( $data['keyword'] ) ) {
			wp_send_json_error();
		}

		// Get required data.
		$keyword = (string) \smartcrawl_get_array_value( $data, 'keyword' );
		$post_id = (int) \smartcrawl_get_array_value( $data, 'post_id' );

		// Permission check.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error();
		}

		$smartcrawl_post = Post_Cache::get()->get_post( $post_id );

		if ( empty( $smartcrawl_post ) ) {
			wp_send_json_error();
		} else {
			$smartcrawl_post->remove_focus_keyword( $keyword );
			$keywords = \smartcrawl_get_value( 'focus-keywords', $post_id );
			wp_send_json_success( empty( $keywords ) ? '' : $keywords );
		}
	}

	/**
	 * Sends postbox editor JSON response with detailed post analysis
	 *
	 * @return void
	 */
	public function retrieve_post_analysis() {
		$data = $this->get_request_data();

		if ( empty( $data['post_id'] ) || ! is_numeric( $data['post_id'] ) ) {
			wp_send_json_error();
		}

		$post_id = (int) \smartcrawl_get_array_value( $data, 'post_id' );

		$keywords     = \smartcrawl_get_value( 'focus-keywords', $post_id );
		$new_keywords = \smartcrawl_get_array_value( $data, 'wds_focus_keywords' );

		if ( $keywords !== $new_keywords ) {
			$cached_post = Post_Cache::get()->get_post( $post_id );

			if ( empty( $cached_post ) ) {
				wp_send_json_error();
			} else {
				$cached_post->set_focus_keywords( explode( ',', $new_keywords ) );
			}
		}

		$is_dirty = (bool) \smartcrawl_get_array_value( $data, 'is_dirty' );
		$post     = get_post( $post_id );
		/**
		 * If there is_dirty flag is set i.e. are unsaved changes in the editor then we
		 * will fetch the latest post revision and analyze that.
		 */
		$post_to_analyze = $is_dirty
			? \smartcrawl_get_latest_post_version( $post_id )
			: $post;
		$this->analyze_post( $post_to_analyze->ID );

		$out = array();

		$seo_analysis = $this->retrieve_post_seo_analysis( $post );

		if ( ! empty( $seo_analysis ) ) {
			$out['seo'] = $seo_analysis;
		}

		$readability_analysis = $this->retrieve_post_readability_analysis( $post );

		if ( ! empty( $readability_analysis ) ) {
			$out['readability'] = $readability_analysis;
		}

		wp_send_json_success( $out );
	}

	/**
	 * Retrieve SEO analysis for post metabox.
	 *
	 * @param \WP_Post $post Post instance.
	 *
	 * @return array
	 */
	public function retrieve_post_seo_analysis( $post ) {
		if ( ! Settings::get_setting( 'analysis-seo' ) ) {
			return array();
		}

		// If no analysis data is available, run analysis first.
		$this->maybe_analyze_post( $post->ID );

		$model        = new Analysis_Model( $post->ID );
		$seo_data     = $model->get_post_data( Analysis_Model::DATA_ANALYSIS );
		$extra_checks = $model->get_post_data( Analysis_Model::DATA_ANALYSIS_EXTRA );

		$result = array();

		$post_cache      = Post_Cache::get()->get_post( $post->ID );
		$primary_keyword = $post_cache ? $post_cache->get_primary_keyword() : '';

		if ( $primary_keyword ) {
			$errors = \smartcrawl_get_array_value( $seo_data, 'errors' );

			$result['primary_keyword']     = $primary_keyword;
			$result['primary_checks']      = \smartcrawl_get_array_value( $seo_data, 'checks' );
			$result['primary_error_count'] = ! empty( $errors ) ? count( $errors ) : 0;
		}

		$extra_keywords = $post_cache ? $post_cache->get_extra_keywords() : array();

		if ( ! empty( $extra_keywords ) ) {
			$result['extra_keywords'] = $extra_keywords;
			$result['extra_checks']   = $extra_checks;
		}

		return $result;
	}

	/**
	 * Retrieve Readability analysis for post metabox.
	 *
	 * @param \WP_Post $post Post instance.
	 *
	 * @return array
	 */
	public function retrieve_post_readability_analysis( $post ) {
		if ( ! Settings::get_setting( 'analysis-readability' ) ) {
			return array();
		}

		// If no analysis data is available, run analysis first.
		$this->maybe_analyze_post( $post->ID );

		$model = new Analysis_Model( $post->ID );
		$data  = $model->get_post_data( Analysis_Model::DATA_READABILITY );
		$score = \smartcrawl_get_array_value( $data, 'score' );

		if ( null === $score ) {
			return array();
		}

		$score   = intval( ceil( $score ) );
		$level   = $model->get_readability_level();
		$ignored = Checks::is_readability_ignored( $post->ID );
		$state   = $model->get_kincaid_readability_state( $score, $ignored );

		$refresh_disabled = 'auto-draft' === get_post_status() ? 'disabled' : '';
		$whitelabel_class = White_Label::get()->summary_class();

		return array(
			'score'            => $score,
			'level'            => $level,
			'state'            => $state,
			'ignored'          => $ignored,
			'refresh_disabled' => $refresh_disabled,
			'whitelabel_class' => $whitelabel_class,
		);
	}

	/**
	 * Determine whether current post type requires analysis or not.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	private function post_type_requires_analysis( $post_id ) {
		$post_type = get_post_type_object( get_post_type( $post_id ) );

		return 'revision' === $post_type->name || $post_type->public;
	}

	/**
	 * Update post analysis data
	 *
	 * Forcefully updates the post analysis data,
	 * no questions asked
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool Status
	 */
	public function analyze_post( $post_id ) {
		if (
			empty( $post_id )
			|| ! is_numeric( $post_id )
			|| ! $this->post_type_requires_analysis( $post_id )
		) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$model = new Analysis_Model( $post_id );
		$model->update_analysis_data();
		$model->update_readability_data();

		return true;
	}

	/**
	 * Update post analysis data only if there's no such data
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool Whether we updated the analysis data or not
	 */
	public function maybe_analyze_post( $post_id ) {
		if ( empty( $post_id ) || ! is_numeric( $post_id ) ) {
			return false;
		}

		$analyzed = false;

		$model = new Analysis_Model( $post_id );

		if ( ! $model->has_post_data( Analysis_Model::DATA_ANALYSIS ) ) {
			if ( current_user_can( 'edit_post', $post_id ) ) {
				$model->update_analysis_data();
			}
			$analyzed = true;
		}

		if ( ! $model->has_post_data( Analysis_Model::DATA_READABILITY ) ) {
			if ( current_user_can( 'edit_post', $post_id ) ) {
				$model->update_readability_data();
			}
			$analyzed = true;
		}

		return $analyzed;
	}

	/**
	 * Injects postbox publish editor content
	 *
	 * @param \WP_Post $post Post instance.
	 *
	 * @return void
	 */
	public function add_postbox_fields( $post ) {
		$model                    = new Analysis_Model( $post->ID );
		$smartcrawl_post          = Post_Cache::get()->get_post( $post->ID );
		$focus_keywords           = $smartcrawl_post
			? $smartcrawl_post->get_focus_keywords()
			: array();
		$focus_keywords_available = ! empty( $focus_keywords );

		if ( in_array( get_post_status( $post ), array( 'draft', 'auto-draft' ), true ) ) {
			$result     = Checks::apply( $post->ID );
			$checks     = $result->get_applied_checks();
			$has_errors = false;
			foreach ( $checks as $chk ) {
				if ( empty( $chk['status'] ) && empty( $chk['ignored'] ) ) {
					$has_errors = true;
					break;
				}
			}
		} else {
			$seo_data   = $model->get_post_data( Analysis_Model::DATA_ANALYSIS );
			$has_errors = ! empty( $seo_data['errors'] );
		}

		if ( ! $focus_keywords_available ) {
			$seo_class = 'wds-status-invalid';
			$seo_text  = __( 'No Focus Keyword', 'wds' );
		} elseif ( $has_errors ) {
			$seo_class = 'wds-status-warning';
			$seo_text  = __( 'Needs Improvement', 'wds' );
		} else {
			$seo_class = 'wds-status-success';
			$seo_text  = __( 'Good', 'wds' );
		}

		$readability_data    = $model->get_post_data( Analysis_Model::DATA_READABILITY );
		$readability_score   = \smartcrawl_get_array_value( $readability_data, 'score', 0 );
		$readability_score   = intval( ceil( $readability_score ) );
		$readability_ignored = Checks::is_readability_ignored( $post->ID );
		$readability_state   = $model->get_kincaid_readability_state( $readability_score, $readability_ignored );
		$readability_class   = sprintf(
			'wds-status-%s',
			$readability_state
		);
		$readability_text    = $model->get_readability_level();

		?>
		<div class="wds-post-box-fields">
			<?php if ( Settings::get_setting( 'analysis-seo' ) ) : ?>
				<div class="misc-pub-section seo-analysis <?php echo esc_attr( $seo_class ); ?>">
					<i class="wds-icon-magnifying-glass-search"></i>
					<?php esc_html_e( 'SEO:', 'wds' ); ?> <b><?php echo esc_html( $seo_text ); ?></b>
				</div>
			<?php endif; ?>

			<?php if ( Settings::get_setting( 'analysis-readability' ) ) : ?>
				<div class="misc-pub-section readability-analysis <?php echo esc_attr( $readability_class ); ?>">
					<i class="wds-icon-monitor"></i>
					<?php esc_html_e( 'Readability:', 'wds' ); ?> <b><?php echo esc_html( $readability_text ); ?></b>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Sends JSON response with post analysis data
	 *
	 * As a side effect, updates the analysis of needed
	 *
	 * @return void
	 */
	public function retrieve_post_analysis_data() {
		$data = $this->get_request_data();

		if ( empty( $data['post_id'] ) || ! is_numeric( $data['post_id'] ) ) {
			wp_send_json_error();
		}

		$this->maybe_analyze_post( (int) $data['post_id'] );
		$model = new Analysis_Model( (int) $data['post_id'] );

		wp_send_json_success(
			array(
				'analysis'              => $model->get_post_data( Analysis_Model::DATA_ANALYSIS ),
				'readability'           => $model->get_post_data( Analysis_Model::DATA_READABILITY ),
				'readability_threshold' => $model->get_readability_threshold(),
				'readable'              => $model->is_readable(),
			)
		);
	}

	/**
	 * Sends JSON response with post analysis markup
	 *
	 * As a side effect, updates the analysis of needed
	 *
	 * @return void
	 */
	public function retrieve_post_analysis_markup() {
		$data = $this->get_request_data();

		if ( empty( $data['post_id'] ) || ! is_numeric( $data['post_id'] ) ) {
			wp_send_json_error();
		}

		$this->maybe_analyze_post( (int) $data['post_id'] );
		$result = $this->get_post_analysis_result_markup( (int) $data['post_id'] );

		wp_send_json_success( $result );
	}

	/**
	 * Force analysis recheck and respond with column markup data
	 *
	 * @return void
	 */
	public function retrieve_post_analysis_recheck() {
		$data = $this->get_request_data();

		if ( empty( $data['post_id'] ) || ! is_numeric( $data['post_id'] ) ) {
			wp_send_json_error();
		}

		$this->analyze_post( (int) $data['post_id'] );
		$result = $this->get_post_analysis_result_markup( (int) $data['post_id'] );

		wp_send_json_success( $result );
	}

	/**
	 * Verify nonce for request.
	 *
	 * @return array|mixed
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-metabox-nonce' ) ? stripslashes_deep( $_POST ) : array();
	}
}