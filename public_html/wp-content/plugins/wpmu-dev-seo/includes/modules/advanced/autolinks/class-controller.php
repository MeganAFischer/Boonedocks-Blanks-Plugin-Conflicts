<?php
/**
 * Automatic Linking action controller.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Autolinks;

use SmartCrawl\Cache\Object_Cache;
use SmartCrawl\Controllers;
use SmartCrawl\Services\Service;
use SmartCrawl\Singleton;

/**
 * Automatic Linking controller
 */
class Controller extends Controllers\Submodule_Controller {

	use Singleton;

	/**
	 * Backup fragments.
	 *
	 * @var array $fragments
	 */
	private $fragments = array();

	/**
	 * Cache helper instance.
	 *
	 * @var Object_Cache $cache
	 */
	private $cache;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct();

		// Cache instance.
		$this->cache = Object_Cache::get();

		$this->module_title = __( 'Automatic Links', 'wds' );
		$this->event_name   = 'Automatic Links';
		$this->premium      = true;
	}

	/**
	 * Initialization method.
	 *
	 * @since 3.3.0
	 */
	protected function init() {
		parent::init();

		// Initialize the auto linking.
		add_action( 'init', array( $this, 'initialize_autolinks' ) );
	}

	/**
	 * Includes methods when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {
		parent::terminate();

		remove_action( 'init', array( $this, 'initialize_autolinks' ) );
	}

	/**
	 * Initializes autolinks.
	 *
	 * @return void
	 */
	public function initialize_autolinks() {
		if ( ! is_admin() ) {
			// Setup content filter.
			add_filter( 'the_content', array( $this, 'post_filter' ), 99 );
			add_filter( 'get_the_excerpt', array( $this, 'excerpt_filter' ), 99 );
			add_filter( 'smartcrawl_autolinks_skip_post_linking', array( $this, 'skip_post_linking' ), 10, 3 );
			add_filter( 'term_description', array( $this, 'product_category_filter' ), 10, 2 );

			// Is comment processing enabled.
			if ( $this->is_type_enabled( 'comment' ) ) {
				add_filter( 'comment_text', array( $this, 'comment_text_filter' ), 10, 2 );
			}
		}

		// Clear cache whenever content changes.
		add_action( 'saved_term', array( $this, 'clear_term_caches' ), 10, 3 );
		add_action( 'save_post', array( $this, 'clear_post_caches' ) );
		add_action( 'after_delete_post', array( $this, 'clear_post_caches' ) );
		add_action( 'edit_comment', array( $this, 'clear_comment_caches' ) );
		add_action( 'deleted_comment', array( $this, 'clear_comment_caches' ) );
		add_action( "update_option_{$this->parent->module_name}", array( $this, 'clear_all_caches' ) );
	}

	/**
	 * Sanitizes submitted options
	 *
	 * @param array $input Raw input.
	 *
	 * @return bool True if sanitized successfully, otherwise false.
	 */
	public function sanitize_options( $input ) {
		if ( ! $this->is_premium_member() ) {
			return false;
		}

		if ( isset( $input['active'] ) ) {
			$active = boolval( $input['active'] );

			if ( empty( $this->options['active'] ) || $active !== $this->options['active'] ) {
				$this->options['active'] = $active;

				return true;
			}

			unset( $input['active'] );
		}

		if ( empty( $input ) ) {
			return true;
		}

		// Booleans.
		$booleans = array(
			'onlysingle',
			'allowfeed',
			'casesens',
			'customkey_preventduplicatelink',
			'target_blank',
			'rel_nofollow',
			'allow_empty_tax',
			'excludeheading',
			'exclude_no_index',
			'exclude_image_captions',
			'disable_content_cache',
		);

		foreach ( $booleans as $bool ) {
			$this->options[ $bool ] = (bool) $input[ $bool ];
		}

		$this->options['insert']  = array();
		$this->options['link_to'] = array();
		$post_type_names          = array_keys( \smartcrawl_get_post_types() );
		if ( ! empty( $input['insert'] ) ) {
			// Accept only allowed types.
			$this->options['insert'] = array_intersect( (array) $input['insert'], array_merge( $post_type_names, array( 'comment', 'product_cat' ) ) );
		}
		if ( ! empty( $input['link_to'] ) ) {
			// Accept only allowed types.
			foreach ( $post_type_names as $post_type ) {
				if ( in_array( 'l' . $post_type, (array) $input['link_to'], true ) ) {
					$this->options['link_to'][] = 'l' . $post_type;
				}
			}
			foreach ( get_taxonomies() as $taxonomy ) {
				$tax = get_taxonomy( $taxonomy );
				$key = strtolower( $tax->labels->name );
				if ( in_array( 'l' . $key, (array) $input['link_to'], true ) ) {
					$this->options['link_to'][] = 'l' . $key;
				}
			}
		}

		// Numerics.
		$numeric = array(
			'cpt_char_limit',
			'tax_char_limit',
			'link_limit',
			'single_link_limit',
		);
		foreach ( $numeric as $num ) {
			if ( isset( $input[ $num ] ) ) {
				if ( is_numeric( $input[ $num ] ) ) {
					$this->options[ $num ] = (int) $input[ $num ];
				} elseif ( empty( $input[ $num ] ) ) {
					$this->options[ $num ] = '';
				} else {
					add_settings_error( $this->option_name, 'numeric-limits', __( 'Limit values must be numeric', 'wds' ) );
				}
			}
		}

		// Strings.
		$strings = array(
			'ignore',
			'ignorepost',
		);
		foreach ( $strings as $str ) {
			if ( isset( $input[ $str ] ) ) {
				$this->options[ $str ] = sanitize_text_field( $input[ $str ] );
			}
		}

		// Arrays.
		$arrays = array( 'excluded_urls' );
		foreach ( $arrays as $array_key ) {
			if ( isset( $input[ $array_key ] ) ) {
				// Remove empty values.
				$array_value = array_filter(
					(array) $input[ $array_key ],
					function ( $value ) {
						return ! empty( $value );
					}
				);
				// Remove duplicates.
				$array_value = array_unique( $array_value );
				// Sanitize values.
				$this->options[ $array_key ] = array_map( 'sanitize_text_field', $array_value );
			}
		}

		// Custom keywords, they need newlines.
		if ( isset( $input['customkey'] ) ) {
			$str                        = wp_check_invalid_utf8( $input['customkey'] );
			$str                        = wp_pre_kses_less_than( $str );
			$str                        = wp_strip_all_tags( $str );
			$this->options['customkey'] = $str;

			$found = false;
			while ( preg_match( '/%[a-f0-9]{2}/i', $str, $match ) ) {
				$str   = str_replace( $match[0], '', $str );
				$found = true;
			}
			if ( $found ) {
				$str = trim( preg_replace( '/ +/', ' ', $str ) );
			}
		}

		return true;
	}

	/**
	 * Returns localize script data.
	 *
	 * @return array
	 */
	public function localize_script_args() {
		$args = array(
			'active'          => ! empty( $this->options['active'] ) && $this->is_premium_member(),
			'insert_options'  => $this->get_insert_keys(),
			'link_to_options' => $this->get_linkto_keys(),
			'options'         => array(),
		);

		foreach (
			array(
				'insert',
				'link_to',
				'customkey',
				'ignore',
				'ignorepost',
				'cpt_char_limit',
				'tax_char_limit',
				'link_limit',
				'single_link_limit',
				'allow_empty_tax',
				'excludeheading',
				'onlysingle',
				'allowfeed',
				'casesens',
				'customkey_preventduplicatelink',
				'target_blank',
				'rel_nofollow',
				'exclude_no_index',
				'exclude_image_captions',
				'disable_content_cache',
			) as $value
		) {
			$args['options'][ $value ] = \smartcrawl_get_array_value(
				$this->options,
				$value
			);
		}

		return $args;
	}

	/**
	 * Outputs submodule content to dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_content() {
		$is_member = $this->is_premium_member();
		?>

		<div class="wds-separator-top <?php echo ! $is_member ? 'wds-box-blocked-area wds-draw-down-md wds-draw-left' : 'wds-draw-left-padded'; ?>">
			<small><strong><?php esc_html_e( 'Automatic Linking', 'wds' ); ?></strong></small>

			<?php if ( ! $is_member ) : ?>
				<a
					href="https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=smartcrawl_dash_autolinking_pro_tag"
					target="_blank"
				>
						<span
							class="sui-tag sui-tag-pro sui-tooltip"
							data-tooltip="<?php esc_attr_e( 'Upgrade to SmartCrawl Pro', 'wds' ); ?>"
						>
							<?php esc_html_e( 'Pro', 'wds' ); ?>
						</span>
				</a>
			<?php endif; ?>

			<?php if ( $this->should_run() && $is_member ) : ?>
				<div class="wds-right">
					<span class="sui-tag wds-right sui-tag-sm sui-tag-blue"><?php esc_html_e( 'Active', 'wds' ); ?></span>
				</div>
			<?php else : ?>
				<p>
					<small>
						<?php
						printf(
						/* translators: 1,2: strong tag, 2: plugin title */
							esc_html__( 'Configure %1$s%3$s%2$s to automatically link certain key words to a page on your blog or even a whole new site all together.', 'wds' ),
							'<strong>',
							'</strong>',
							esc_html( \smartcrawl_get_plugin_title() )
						);
						?>
					</small>
				</p>
				<button
					type="button"
					data-module="<?php echo esc_attr( $this->parent->module_id ); ?>"
					data-submodule="<?php echo esc_attr( $this->module_id ); ?>"
					class="wds-activate-submodule wds-disabled-during-request sui-button sui-button-blue">

					<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>
			<?php endif; ?>
		</div>

		<?php
	}

	/**
	 * Comment text filter handler.
	 *
	 * @since 1.0.0
	 *
	 * @param string           $text    Text of the current comment.
	 * @param \WP_Comment|null $comment The comment object. Null if not found.
	 *
	 * @return string
	 */
	public function comment_text_filter( $text, $comment ) {
		if ( $comment instanceof \WP_Comment ) {
			// Do not continue if pre checks are not passed.
			if ( ! $this->pre_check_passed( 'comment' ) ) {
				return $text;
			}

			// Return from cache if already cached.
			$cached_content = $this->get_item_cache( $comment->comment_ID, 'comment' );
			if ( ! empty( $cached_content ) ) {
				return $cached_content;
			}

			// Process the content and add links.
			$text = $this->process_text( $text );

			// Set to cache so we don't re-loop on every page load.
			$this->set_item_cache( $comment->comment_ID, $text, 'comment' );

			return $text;
		}

		return $text;
	}

	/**
	 * Post content filter handler.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Content.
	 *
	 * @return string
	 */
	public function post_filter( $text ) {
		if (
			// Do only for the main content loop.
			! in_the_loop() &&
			// Divi compatibility - see https://incsub.atlassian.net/browse/SMA-1242.
			! defined( 'ET_CORE_VERSION' )
		) {
			return $text;
		}

		return $this->content_filter( $text );
	}

	/**
	 * Post excerpt content filter handler.
	 *
	 * @since 3.3.0
	 *
	 * @param string $text Content.
	 *
	 * @return string
	 */
	public function excerpt_filter( $text ) {
		return $this->content_filter( $text, 'excerpt' );
	}

	/**
	 * Product category content filter handler.
	 *
	 * @since 3.3.0
	 *
	 * @param string $description Raw description text.
	 * @param int    $term        Term ID.
	 *
	 * @return string
	 */
	public function product_category_filter( $description, $term ) {
		// If current page is not product category archive.
		if ( ! is_tax( 'product_cat' ) ) {
			return $description;
		}
		// If current product category is not enabled.
		$insert = $this->get_option( 'insert', array() );
		if ( ! in_array( 'product_cat', $insert, true ) ) {
			return $description;
		}

		// Do not continue if pre-checks are not passed.
		if ( ! $this->pre_check_passed( 'product_cat' ) ) {
			return $description;
		}

		// Return from cache if already cached.
		$cached_content = $this->get_item_cache( $term, 'term' );
		if ( ! empty( $cached_content ) ) {
			return $cached_content;
		}

		// Process the content and add links.
		$description = $this->process_text( $description );

		// Set to cache so we don't re-loop on every page load.
		$this->set_item_cache( $term, $description, 'term' );

		return $description;
	}

	/**
	 * When autolinks settings are changed, clear all caches.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function clear_all_caches() {
		// Delete all caches when settings are changed.
		$this->cache->purge_cache_group( 'wds-autolinks' );
		$this->cache->purge_cache_group( 'wds-autolinks-post' );
		$this->cache->purge_cache_group( 'wds-autolinks-excerpt' );
		$this->cache->purge_cache_group( 'wds-autolinks-comment' );
		$this->cache->purge_cache_group( 'wds-autolinks-term' );
	}

	/**
	 * Clear all post related caches whenever update happens.
	 *
	 * This should be done whenever a post is created, updated or deleted.
	 *
	 * @since 3.3.0
	 *
	 * @param int $id Post ID.
	 *
	 * @return void
	 */
	public function clear_post_caches( $id ) {
		// Always delete current post's cached content.
		$this->delete_item_cache( $id );

		// Enabled post types.
		$enabled = $this->get_enabled_post_types();

		// Only if one of enabled types.
		if ( in_array( get_post_type( $id ), $enabled, true ) ) {
			// Delete cached content.
			$this->cache->purge_cache_group( 'wds-autolinks-post' );
			$this->cache->purge_cache_group( 'wds-autolinks-excerpt' );
			// Delete list of posts.
			$this->cache->purge_cache( 'posts', 'wds-autolinks' );
		}
	}

	/**
	 * Clear all term related caches.
	 *
	 * @since 3.3.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return void
	 */
	public function clear_term_caches( $term_id, $tt_id, $taxonomy ) {
		// Always delete current post's cached content.
		$this->delete_item_cache( $term_id, 'term' );

		// Enabled taxonomies.
		$enabled = $this->get_enabled_taxonomies();

		// Only if one of enabled types.
		if ( in_array( $taxonomy, $enabled, true ) ) {
			// Delete cached content.
			$this->cache->purge_cache_group( 'wds-autolinks-post' );
			// Delete list of terms.
			$this->cache->purge_cache( 'terms', 'wds-autolinks' );
		}
	}

	/**
	 * Clear all comment related caches.
	 *
	 * @since 3.3.0
	 *
	 * @param int $id The comment ID.
	 *
	 * @return void
	 */
	public function clear_comment_caches( $id ) {
		// Delete comment cache.
		$this->delete_item_cache( $id, 'comment' );
	}

	/**
	 * Skip linking post by condition for Polylang compatibility.
	 *
	 * @since 3.3.0
	 *
	 * @param bool     $skip     Should skip?.
	 * @param \WP_Post $post     Current post.
	 * @param object   $postitem Post data to link (ID, post_title, post_type).
	 *
	 * @return bool
	 */
	public function skip_post_linking( $skip, $post, $postitem ) {
		if ( function_exists( '\pll_get_post_language' ) && function_exists( '\pll_current_language' ) ) {
			// Link only if the current post and linking post is in same language.
			if ( \pll_current_language() !== \pll_get_post_language( $postitem->ID ) ) {
				return true;
			}
		}

		return $skip;
	}

	/**
	 * Post content filter handler.
	 *
	 * @since 3.3.0
	 *
	 * @param string $text Content.
	 * @param string $type Type (post or excerpt).
	 *
	 * @return string
	 */
	private function content_filter( $text, $type = 'post' ) {
		// If current post type is not enabled.
		if ( ! $this->is_type_enabled( get_post_type() ) ) {
			return $text;
		}

		// Do not continue if pre-checks are not passed.
		if ( ! $this->pre_check_passed() ) {
			return $text;
		}

		$process_cache = true;

		if ( 'post' === $type && doing_filter( 'get_the_excerpt' ) ) {
			$process_cache = false;
		}

		// Return from cache if already cached.
		if ( $process_cache && $this->can_cache_content( get_post_type() ) ) {
			$cached_content = $this->get_item_cache( get_the_ID(), $type );
			if ( ! empty( $cached_content ) ) {
				return $cached_content;
			}
		}

		// Process the content and add links.
		$text = $this->process_text( $text );

		if ( $process_cache ) {
			// Set to cache, so we don't re-loop on every page load.
			$this->set_item_cache( get_the_ID(), $text, $type );
		}

		return $text;
	}

	/**
	 * Text processing method
	 *
	 * @param string $text Text to process.
	 *
	 * @return string
	 */
	private function process_text( $text ) {
		global $post;

		// Current post type.
		$current_title = '';
		$current_url   = '';
		$current_type  = get_post_type();

		// Setup current post title and url.
		if ( in_array( $current_type, array( 'post', 'page' ), true ) ) {
			$current_title = $this->is_option_enabled( 'casesens' ) ? $post->post_title : strtolower( $post->post_title );
			$current_url   = trailingslashit( get_permalink( $post->ID ) );
		}

		// Get the flags.
		$max_links      = $this->is_option_enabled( 'link_limit' ) ? $this->get_option( 'link_limit' ) : 0;
		$max_single     = $this->is_option_enabled( 'single_link_limit' ) ? $this->get_option( 'single_link_limit' ) : ( $max_links ? $max_links : - 1 );
		$max_single_url = $this->is_option_enabled( 'maxsingleurl' ) ? $this->get_option( 'maxsingleurl' ) : 0;

		$links = 0;
		$urls  = array();

		// Setup ignored items.
		$ignored_string = (string) $this->get_option( 'ignore', '' );
		$ignored_array  = $this->explode_trim( ',', $ignored_string );
		if ( ! $this->is_option_enabled( 'casesens' ) ) {
			$ignored_array = array_map( 'strtolower', $ignored_array );
		}

		$this->fragments = array();

		// Backup the parts that we don't want to change.
		$text = $this->backup_fragments( $text );

		// Setup custom keyword links if enabled.
		if ( $this->is_option_enabled( 'customkey' ) ) {
			$text = $this->link_custom_keywords( $text, $urls, $links, $max_links, $max_single, $max_single_url, $ignored_array, $current_url );
		}

		// Setup post links.
		$text = $this->link_posts( $text, $urls, $links, $max_links, $max_single, $max_single_url, $ignored_array, $current_title );

		// Setup taxonomy links.
		$text = $this->link_taxonomies( $text, $urls, $links, $max_links, $max_single, $max_single_url, $ignored_array );

		// Restore the parts that we backed up before getting started.
		$text = $this->restore_fragments( $text );

		// Get host.
		$link = wp_parse_url( get_bloginfo( 'wpurl' ) );
		$host = 'http://' . $link['host'];

		// Open the links in new tab if required.
		if ( $this->is_option_enabled( 'blanko' ) ) {
			$text = preg_replace( '%<a(\s+.*?href=\S(?!' . $host . '))%i', '<a target="_blank"\\1', $text );
		}

		// Add nofollow to links if required.
		if ( $this->is_option_enabled( 'nofolo' ) ) {
			$text = preg_replace( '%<a(\s+.*?href=\S(?!' . $host . '))%i', '<a rel="nofollow"\\1', $text );
		}

		return $text;
	}

	/**
	 * Insert custom keywords links to the content.
	 *
	 * @since 3.3.0
	 *
	 * @param string $text           Text to process.
	 * @param array  $urls           URLs.
	 * @param int    $links          Links.
	 * @param int    $max_links      Max links.
	 * @param int    $max_single     Max single.
	 * @param string $max_single_url Max single URL.
	 * @param array  $ignored_array  Ignored items.
	 * @param string $current_url    Current URL.
	 *
	 * @return string
	 */
	private function link_custom_keywords( $text, &$urls, &$links, $max_links, $max_single, $max_single_url, $ignored_array, $current_url ) {
		$kw_array = array();

		$case_sensitive    = $this->is_option_enabled( 'casesens' );
		$prevent_duplicate = $this->is_option_enabled( 'customkey_preventduplicatelink' );

		$custom_keywords = $this->get_option( 'customkey' );

		foreach ( explode( "\n", $custom_keywords ) as $line ) {
			if ( $prevent_duplicate ) {
				$line               = trim( $line );
				$last_delimiter_pos = strrpos( $line, ',' );
				$url                = substr( $line, $last_delimiter_pos + 1 );
				$keywords           = substr( $line, 0, $last_delimiter_pos );

				if ( ! empty( $keywords ) && ! empty( $url ) ) {
					$kw_array[ $keywords ] = trim( $url );
				}
			} else {
				$chunks       = array_map( 'trim', explode( ',', $line ) );
				$total_chunks = count( $chunks );
				if ( $total_chunks > 2 ) {
					$i   = 0;
					$url = $chunks[ $total_chunks - 1 ];

					while ( $i < $total_chunks - 1 ) {
						if ( ! empty( $chunks[ $i ] ) ) {
							$kw_array[ $chunks[ $i ] ] = $url;
						}

						++$i;
					}
				} elseif ( false !== stristr( $line, ',' ) ) {
					list( $keyword, $url ) = array_map( 'trim', explode( ',', $line, 2 ) );
					if ( ! empty( $keyword ) ) {
						$kw_array[ $keyword ] = $url;
					}
				}
			}
		}

		// Add htmlentities and WordPress texturizer alternations for keywords.
		$kw_array_tmp = $kw_array;
		foreach ( $kw_array_tmp as $kw => $url ) {
			$kw_entity = htmlspecialchars( $kw, ENT_QUOTES );
			if ( ! isset( $kw_array[ $kw_entity ] ) ) {
				$kw_array[ $kw_entity ] = $url;
			}

			$kw_entity = wptexturize( $kw );
			if ( ! isset( $kw_array[ $kw_entity ] ) ) {
				$kw_array[ $kw_entity ] = $url;
			}
		}

		if ( $max_links && $links >= $max_links ) {
			return $text;
		}

		foreach ( $kw_array as $name => $url ) {
			if (
				( $this->get_absolute_url( $url ) !== $current_url )
				&& ! in_array( $case_sensitive ? $name : strtolower( $name ), $ignored_array, true )
				&& ( ! $max_single_url || $urls[ $url ] < $max_single_url )
				&& $this->strpos( $text, $name ) !== false
			) {
				/**
				 * Filters hook to short circuit the custom keyword linking.
				 *
				 * @param bool $skip Should skip?.
				 * @param string $name Keyword name.
				 * @param string $url Keyword URL.
				 * @param string $text Processing text.
				 *
				 * @since 3.3.0
				 */
				if ( apply_filters( 'smartcrawl_autolinks_skip_keyword_linking', false, $name, $url, $text ) ) {
					continue;
				}

				$name = preg_quote( $name, '/' );

				if ( $prevent_duplicate ) {
					$name = str_replace( ',', '|', $name );
				}

				$max_single = $prevent_duplicate ? 1 : (int) $max_single;
				$arguments  = array(
					'target' => $this->is_option_enabled( 'target_blank' ) ? '_blank' : '',
					'rel'    => $this->is_option_enabled( 'rel_nofollow' ) ? 'nofollow' : '',
				);
				$replace    = '$1<a ' . \smartcrawl_autolinks_construct_attributes( $arguments ) . ' href="' . $url . '">$2</a>$3';
				$regex      = str_replace( 'KEYWORD', $name, $this->get_post_regex() );

				if ( $this->is_autolink_on_fly_empty( $text, $regex ) ) {
					continue;
				}

				$new_text = preg_replace( $regex, $replace, $text, $max_single );

				if ( $new_text !== $text ) {
					$replacement_count = count( preg_split( $regex, $text ) ) - 1;
					$replacement_count = $replacement_count > 0 ? $replacement_count : 1;
					$links            += min( $replacement_count, $max_single );
					$text              = $new_text;
					if ( ! isset( $urls[ $url ] ) ) {
						$urls[ $url ] = 1;
					} else {
						++$urls[ $url ];
					}
				}
			}
		}

		return $text;
	}

	/**
	 * Insert post links to the content.
	 *
	 * If any of the post titles matching the words on current post content,
	 * add links to them.
	 *
	 * @since 3.3.0
	 *
	 * @param string $text           Text to process.
	 * @param array  $urls           URLs.
	 * @param int    $links          Links.
	 * @param int    $max_links      Max links.
	 * @param int    $max_single     Max single.
	 * @param string $max_single_url Max single URL.
	 * @param array  $ignored_array  Ignored items.
	 * @param string $current_title  Current title.
	 *
	 * @return string
	 */
	private function link_posts( $text, &$urls, &$links, $max_links, $max_single, $max_single_url, $ignored_array, $current_title ) {
		global $post;

		// Get post items.
		$posts = $this->get_posts();

		$case_sensitive    = $this->is_option_enabled( 'casesens' );
		$prevent_duplicate = $this->is_option_enabled( 'customkey_preventduplicatelink' );

		foreach ( $posts as $postitem ) {
			// No need to self link.
			if ( ! is_tax() && (int) $postitem->ID === $post->ID ) {
				continue;
			}

			if (
				$this->is_type_enabled( $postitem->post_type, 'link_to' ) &&
				( ! $max_links || ( $links < $max_links ) ) &&
				( ( $case_sensitive ? $postitem->post_title : strtolower( $postitem->post_title ) ) !== $current_title ) &&
				( ! in_array( ( $case_sensitive ? $postitem->post_title : strtolower( $postitem->post_title ) ), $ignored_array, true ) )
			) {
				if ( $this->strpos( $text, $postitem->post_title ) !== false ) {
					/**
					 * Filters hook to short circuit the post linking.
					 *
					 * @since 3.3.0
					 *
					 * @param bool     $skip     Should skip?.
					 * @param \WP_Post $post     Current post.
					 * @param object   $postitem Post data to link (ID, post_title, post_type).
					 * @param string   $text     Processing text.
					 */
					if ( apply_filters( 'smartcrawl_autolinks_skip_post_linking', false, $post, $postitem, $text ) ) {
						continue;
					}

					$name = preg_quote( $postitem->post_title, '/' );

					$regex = str_replace( 'KEYWORD', $name, $this->get_post_regex() );

					if ( $prevent_duplicate ) {
						$max_single = 1;
					} elseif ( ! empty( $max_links ) ) {
						$max_single = ( $links + $max_single >= $max_links ) ? $max_links - $links : $max_single;
					}

					$arguments = array(
						'target' => $this->is_option_enabled( 'target_blank' ) ? '_blank' : '',
						'rel'    => $this->is_option_enabled( 'rel_nofollow' ) ? 'nofollow' : '',
					);

					$replace = '$1<a ' . \smartcrawl_autolinks_construct_attributes( $arguments ) . ' href="$$$url$$$">$2</a>$3';

					if ( $this->is_autolink_on_fly_empty( $text, $regex ) ) {
						continue;
					}

					// Backup previously linked text.
					$text = $this->backup_fragments( $text );

					$newtext = preg_replace( $regex, $replace, $text, $max_single );

					if ( $newtext !== $text ) {
						$url = get_permalink( $postitem->ID );
						if ( ! $max_single_url || $urls[ $url ] < $max_single_url ) {
							$replacement_count = count( preg_split( $regex, $text ) ) - 1;
							$replacement_count = $replacement_count > 0 ? $replacement_count : 1;
							$links            += min( $replacement_count, $max_single );
							$text              = str_replace( '$$$url$$$', $url, $newtext );

							if ( ! isset( $urls[ $url ] ) ) {
								$urls[ $url ] = 1;
							} else {
								++$urls[ $url ];
							}
						}
					}
				}
			}
		}

		return $text;
	}

	/**
	 * Insert post links to the content.
	 *
	 * If any of the post titles matching the words on current post content,
	 * add links to them.
	 *
	 * @since 3.3.0
	 *
	 * @param string $text           Text to process.
	 * @param array  $urls           URLs.
	 * @param int    $links          Links.
	 * @param int    $max_links      Max links.
	 * @param int    $max_single     Max single.
	 * @param string $max_single_url Max single URL.
	 * @param array  $ignored_array  Ignored items.
	 *
	 * @return string
	 */
	private function link_taxonomies( $text, &$urls, &$links, $max_links, $max_single, $max_single_url, $ignored_array ) {
		// Get available terms for the taxonomy.
		$terms             = $this->get_terms();
		$prevent_duplicate = $this->is_option_enabled( 'customkey_preventduplicatelink' );

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				if (
					( ! $max_links || ( $links < $max_links ) ) &&
					! in_array( $this->is_option_enabled( 'casesens' ) ? $term->name : strtolower( $term->name ), $ignored_array, true )
				) {
					if ( false === $this->strpos( $text, $term->name ) ) {
						continue;
					}

					/**
					 * Filters hook to short circuit the term linking.
					 *
					 * @since 3.3.0
					 *
					 * @param bool   $skip Should skip?.
					 * @param object $term Term data to link (term_id, name, taxonomy).
					 * @param string $text Processing text.
					 */
					if ( apply_filters( 'smartcrawl_autolinks_skip_term_linking', false, $term, $text ) ) {
						continue;
					}

					$name   = preg_quote( $term->name, '/' );
					$regexp = str_replace( 'KEYWORD', $name, $this->get_post_regex() );

					$arguments = array(
						'target' => $this->is_option_enabled( 'target_blank' ) ? '_blank' : '',
						'rel'    => $this->is_option_enabled( 'rel_nofollow' ) ? 'nofollow' : '',
					);

					$replace = '$1<a ' . \smartcrawl_autolinks_construct_attributes( $arguments ) . ' href="$$$url$$$">$2</a>$3';

					if ( $this->is_autolink_on_fly_empty( $text, $regexp ) ) {
						continue;
					}

					// To prevent duplicate.
					$max_single = $prevent_duplicate ? 1 : $max_single;

					// Backup previously linked text.
					$text = $this->backup_fragments( $text );

					$new_text = preg_replace( $regexp, $replace, $text, $max_single );
					if ( $new_text !== $text ) {
						$url = get_term_link( get_term( $term->term_id, $term->taxonomy ) );
						if ( is_wp_error( $url ) ) {
							continue;
						}
						if ( ! $max_single_url || $urls[ $url ] < $max_single_url ) {
							$replacement_count = count( preg_split( $regexp, $text ) ) - 1;
							$replacement_count = $replacement_count > 0 ? $replacement_count : 1;
							$links            += min( $replacement_count, $max_single );
							$text              = str_replace( '$$$url$$$', $url, $new_text );
							if ( ! isset( $urls[ $url ] ) ) {
								$urls[ $url ] = 1;
							} else {
								++$urls[ $url ];
							}
						}
					}
				}
			}
		}

		return $text;
	}

	/**
	 * Gets all available posts on the site.
	 *
	 * If the posts are not found in cache, we will run a large query
	 * to get the list. As of now only 2000 items are retrieved for performance.
	 *
	 * @since 3.3.0
	 *
	 * @return array
	 */
	private function get_posts() {
		// Get post items cache.
		$posts = $this->cache->get_cache( 'posts', 'wds-autolinks' );

		if ( ! $posts ) {
			global $wpdb;

			// Get character limit for CPT.
			$cpt_char_limit = $this->is_option_enabled( 'cpt_char_limit' ) ? $this->get_option( 'cpt_char_limit' ) : false;
			// Fallback to default.
			$cpt_char_limit = (int) $cpt_char_limit ? (int) $cpt_char_limit : SMARTCRAWL_AUTOLINKS_DEFAULT_CHAR_LIMIT;
			// Enabled post types.
			$enabled = $this->get_enabled_post_types();

			// No need to continue if no post types are enabled.
			if ( empty( $enabled ) ) {
				return array();
			}

			// Setup query.
			$query = $wpdb->prepare(
				"SELECT post_title, ID, post_type FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN (%s) AND LENGTH(post_title) >= %d",
				implode( "','", $enabled ),
				$cpt_char_limit
			);
			// If no-index posts are excluded.
			if ( $this->is_option_enabled( 'exclude_no_index' ) ) {
				$query .= " AND ID NOT IN( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wds_meta-robots-noindex' AND meta_value = '1')";
			}
			$query .= $wpdb->prepare( ' ORDER BY LENGTH(post_title) DESC LIMIT %d', $this->get_query_limit() );

			// Remove unwanted slashes to avoid query error.
			$query = stripslashes( $query );

			$posts = $wpdb->get_results( $query ); // phpcs:ignore

			// Set to cache.
			if ( ! empty( $posts ) ) {
				$this->cache->set_cache( 'posts', $posts, 'wds-autolinks' );
			}
		}

		/**
		 * Filters hook to modify posts loop.
		 *
		 * @since 3.3.0
		 *
		 * @param array $posts Posts.
		 */
		return apply_filters( 'smartcrawl_autolinks_get_posts', $posts );
	}

	/**
	 * Gets all available terms for enabled taxonomies.
	 *
	 * @since 3.3.0
	 *
	 * @return array
	 */
	private function get_terms() {
		global $wpdb;

		// Check cache first.
		$terms = $this->cache->get_cache( 'terms', 'wds-autolinks' );

		if ( ! $terms ) {
			$min_usage      = $this->is_option_enabled( 'minusage' ) ? $this->get_option( 'minusage' ) : 1;
			$tax_char_limit = $this->is_option_enabled( 'tax_char_limit' ) ? $this->get_option( 'tax_char_limit' ) : false;
			$tax_char_limit = (int) $tax_char_limit ? (int) $tax_char_limit : \SMARTCRAWL_AUTOLINKS_DEFAULT_CHAR_LIMIT;
			$minimum_count  = $this->is_option_enabled( 'allow_empty_tax' ) ? 0 : $min_usage;
			// Enabled post types.
			$enabled = $this->get_enabled_taxonomies();

			// No need to continue if no taxonomies are enabled.
			if ( empty( $enabled ) ) {
				return array();
			}

			// Build custom query.
			$query = $wpdb->prepare(
				"SELECT $wpdb->terms.name, $wpdb->terms.term_id, $wpdb->term_taxonomy.taxonomy FROM $wpdb->terms LEFT JOIN $wpdb->term_taxonomy " .
				"ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id " .
				"WHERE $wpdb->term_taxonomy.taxonomy IN (%s) " .
				"AND LENGTH($wpdb->terms.name) >= %d " .
				"AND $wpdb->term_taxonomy.count >= %d " .
				"ORDER BY LENGTH($wpdb->terms.name) DESC LIMIT %d",
				implode( "','", $enabled ),
				$tax_char_limit,
				$minimum_count,
				$this->get_query_limit()
			);

			// Remove unwanted slashes to avoid query error.
			$query = stripslashes( $query );

			$terms = $wpdb->get_results( $query ); // phpcs:ignore

			// Set to cache.
			if ( ! empty( $terms ) ) {
				$this->cache->set_cache( 'terms', $terms, 'wds-autolinks' );
			}
		}

		/**
		 * Filters hook to modify terms list.
		 *
		 * @since 3.3.0
		 *
		 * @param array $terms Terms.
		 */
		return apply_filters( 'smartcrawl_autolinks_get_terms', $terms );
	}

	/**
	 * Gets all enabled post types to link to.
	 *
	 * Only public post types will be selected.
	 *
	 * @since 3.3.0
	 *
	 * @return array
	 */
	private function get_enabled_post_types() {
		$enabled = array();
		// Get all available public post types.
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {
			// Include only if enabled in settings.
			if ( $this->is_type_enabled( $post_type, 'link_to' ) ) {
				// Include to enabled types.
				$enabled[] = $post_type;
			}
		}

		/**
		 * Filters to modify the list of enabled post types.
		 *
		 * @since 3.3.0
		 *
		 * @param array $enabled Enabled post types.
		 */
		return apply_filters( 'smartcrawl_autolinks_get_enabled_post_types', $enabled );
	}

	/**
	 * Gets all enabled post types to link to.
	 *
	 * Only public post types will be selected.
	 *
	 * @since 3.3.0
	 *
	 * @return array
	 */
	private function get_enabled_taxonomies() {
		$enabled = array();
		// Get all available public post types.
		$taxonomies = get_taxonomies( array( 'public' => true ), 'object' );

		foreach ( $taxonomies as $taxonomy ) {
			// Few types that we don't need.
			if ( in_array( $taxonomy->name, array( 'nav_menu', 'link_category', 'post_format' ), true ) ) {
				continue;
			}

			$key = strtolower( $taxonomy->labels->name );

			// Include only if enabled in settings.
			if ( $this->is_type_enabled( $key, 'link_to' ) ) {
				// Include to enabled taxonomies.
				$enabled[] = $taxonomy->name;
			}
		}

		/**
		 * Filters to modify the list of enabled taxonomies.
		 *
		 * @since 3.3.0
		 *
		 * @param array $enabled Enabled taxonomies.
		 */
		return apply_filters( 'smartcrawl_autolinks_get_enabled_taxonomies', $enabled );
	}

	/**
	 * Checks if all requirements passed before processing.
	 *
	 * Checks if:
	 * - If current page is RSS feed, check if it's enabled.
	 * - If current page is not a single page, check if it's enabled.
	 * - If current page is one of the ignored post skip.
	 *
	 * @since 3.3.0
	 *
	 * @param string $type Type (post or comment).
	 *
	 * @return bool
	 */
	private function pre_check_passed( $type = 'post' ) {
		// Allow on RSS feed only if enabled.
		if ( is_feed() && ! $this->is_option_enabled( 'allowfeed' ) ) {
			return false;
		}

		// Get if post is excluded explicitly.
		$excluded = \smartcrawl_get_value( 'autolinks-exclude' );
		if ( ! empty( $excluded ) ) {
			return false;
		}

		if ( 'post' === $type ) {
			// If only for single items, do not process archives.
			if ( $this->is_option_enabled( 'onlysingle' ) && ! ( is_single() || is_page() ) ) {
				return false;
			}

			// Verify if one of the ignored posts.
			$ignored_posts = \smartcrawl_get_array_value( $this->options, 'ignorepost' );
			$ignored_posts = $this->explode_trim( ',', $ignored_posts );
			// Get only post ids.
			$ignored_post_ids = array_filter(
				$ignored_posts,
				function ( $id ) {
					return is_numeric( $id );
				}
			);
			// If any of the ignored post.
			if ( ! empty( $ignored_post_ids ) && ( is_page( $ignored_post_ids ) || is_single( $ignored_post_ids ) ) ) {
				return false;
			}

			// Get ignored URLs.
			$ignored_urls = array_diff( $ignored_posts, $ignored_post_ids );
			if ( empty( $ignored_urls ) ) {
				return true;
			}
			// Get relative url of the current page.
			// @todo Improve below code. We are using too many strip functions.
			$relative_permalink = untrailingslashit( str_replace( untrailingslashit( home_url() ), '', get_the_permalink() ) );
			foreach ( $ignored_urls as $ignored_url ) {
				$ignored_url = untrailingslashit( $ignored_url );
				// Should start with a slash.
				if ( strpos( $ignored_url, '/' ) === 0 ) {
					if (
						// If wildcard URL.
						substr( $ignored_url, - 2, 2 ) === '/*' &&
						// If starting with ignored url.
						strpos( $relative_permalink, rtrim( $ignored_url, '/*' ) ) === 0
					) {
						return false;
					} elseif ( $ignored_url === $relative_permalink ) {
						// If matching ignored url.
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Backup fragments.
	 *
	 * @param string $text Text.
	 *
	 * @return array|string|string[]|null
	 */
	private function backup_fragments( $text ) {
		$utf  = $this->is_utf8_matching_enabled() ? 'u' : '';
		$tags = 'a|script|style';
		// Exclude headings.
		if ( $this->is_option_enabled( 'excludeheading' ) ) {
			$tags .= '|h1|h2|h3|h4|h5|h6';
		}
		// Exclude image captions.
		if ( $this->is_option_enabled( 'exclude_image_captions' ) ) {
			$tags .= '|figcaption';
		}

		return preg_replace_callback(
			'/<\s*(' . $tags . ')[^>]*>.*?<\s*\/\1\s*>/is' . $utf,
			array( $this, 'replace_fragment_with_placeholder' ),
			$text
		);
	}

	/**
	 * Restore backed up fragments.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text.
	 *
	 * @return array|string|string[]
	 */
	private function restore_fragments( $text ) {
		return str_replace(
			array_keys( $this->fragments ),
			array_values( $this->fragments ),
			$text
		);
	}

	/**
	 * Replace fragment with placeholder.
	 *
	 * These placeholders are used to restore the backups.
	 *
	 * @since 1.0.0
	 *
	 * @param array $matches Matches.
	 *
	 * @return string
	 */
	private function replace_fragment_with_placeholder( $matches ) {
		$fragment      = $matches[0];
		$fragment_hash = md5( $fragment );
		$placeholder   = "<!-- WDS_FRAGMENT_PLACEHOLDER_$fragment_hash -->";

		$this->fragments[ $placeholder ] = $fragment;

		return $placeholder;
	}

	/**
	 * Is UTF matching enabled?.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function is_utf8_matching_enabled() {
		$utf8_variations = array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' );
		$is_utf8_site    = (
			( ! defined( '\DB_CHARSET' ) || strpos( \DB_CHARSET, 'utf8' ) !== false ) &&
			in_array( get_option( 'blog_charset', '' ), $utf8_variations, true )
		);

		/**
		 * Filters hook to modify utf8 matching check.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $is_utf8_site Is enabled.
		 */
		return apply_filters( 'smartcrawl_autolinks_utf8_matching_enabled', $is_utf8_site );
	}

	/**
	 * Gets a single post cache data.
	 *
	 * @since 3.3.0
	 *
	 * @param int    $id   Item ID.
	 * @param string $type Type (post or comment).
	 *
	 * @return mixed
	 */
	private function get_item_cache( $id, $type = 'post' ) {
		return $this->cache->get_cache( 'content-' . $id, 'wds-autolinks-' . $type );
	}

	/**
	 * Sets a single post cache.
	 *
	 * @since 3.3.0
	 *
	 * @param int    $id   Post ID.
	 * @param mixed  $data Content to store.
	 * @param string $type Type (post or comment).
	 *
	 * @return void
	 */
	private function set_item_cache( $id, $data, $type = 'post' ) {
		$this->cache->set_cache( 'content-' . $id, $data, 'wds-autolinks-' . $type );
	}

	/**
	 * Delete a single post cache.
	 *
	 * @since 3.3.0
	 *
	 * @param int    $id   Post ID.
	 * @param string $type Type (post or comment).
	 *
	 * @return void
	 */
	private function delete_item_cache( $id, $type = 'post' ) {
		$this->cache->purge_cache( 'content-' . $id, 'wds-autolinks-' . $type );
	}

	/**
	 * Checks if an option is enabled.
	 *
	 * @since 3.3.0
	 *
	 * @param string $key Setting key.
	 *
	 * @return bool
	 */
	private function is_option_enabled( $key ) {
		$option = $this->get_option( $key );

		return ! empty( $option );
	}

	/**
	 * Checks if a type is enabled.
	 *
	 * @since 3.4.3
	 *
	 * @param string $type Type to check.
	 * @param string $key  Setting key.
	 *
	 * @return bool
	 */
	private function is_type_enabled( $type, $key = 'insert' ) {
		$option = (array) $this->get_option( $key, array() );

		$type = 'insert' === $key ? $type : "l$type";

		return in_array( $type, $option, true );
	}

	/**
	 * Checks if auto linking on the fly is enabled and regex matches.
	 *
	 * @since 3.3.0
	 *
	 * @param string $text  Text to process.
	 * @param string $regex Regular expression.
	 *
	 * @return bool
	 */
	private function is_autolink_on_fly_empty( $text, $regex ) {
		return (
			( defined( '\SMARTCRAWL_AUTOLINKS_ON_THE_FLY_CHECK' ) && \SMARTCRAWL_AUTOLINKS_ON_THE_FLY_CHECK )
			&& ! preg_match( $regex, strip_shortcodes( $text ) )
		);
	}

	/**
	 * Gets a single setting value.
	 *
	 * @param string $key     Option name.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 *
	 * @since 3.3.0
	 */
	private function get_option( $key, $default_value = false ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default_value;
	}

	/**
	 * Gets the limit for query.
	 *
	 * @since 3.3.0
	 *
	 * @return int
	 */
	private function get_query_limit() {
		// If you want to increase the no. of items in query define this.
		return defined( 'SMARTCRAWL_AUTOLINKS_GET_POSTS_LIMIT' ) ? intval( \SMARTCRAWL_AUTOLINKS_GET_POSTS_LIMIT ) : 2000;
	}

	/**
	 * Text to trimmed array of strings
	 *
	 * @since 1.0.0
	 *
	 * @param string $separator Separator to break the text on.
	 * @param string $text      Text to break.
	 *
	 * @return array
	 */
	private function explode_trim( $separator, $text ) {
		$arr = empty( $text ) ? array() : explode( $separator, $text );

		$ret = array();
		foreach ( $arr as $e ) {
			$ret[] = trim( $e );
		}

		return $ret;
	}

	/**
	 * Gets regular expression for the content.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	private function get_post_regex() {
		// Don't match.
		$lookahead_parts = array(
			'[^<]+[>]+',        // Name of HTML tags e.g. block in <blockquote>.
			'[\[\]]+',          // Shortcode.
		);

		$negative_lookahead = join( '|', $lookahead_parts );
		$negative_lookahead = "(?!(?:$negative_lookahead))";

		// Base regular expression.
		$regex = "/$negative_lookahead(^|\b|[^<\p{L}\/>])(KEYWORD)([^\p{L}\/>]|\b|$)/msU";

		// If case insensitive.
		if ( ! $this->is_option_enabled( 'casesens' ) ) {
			$regex .= 'i';
		}

		// Enable UTF-8 flag in the regex.
		if ( $this->is_utf8_matching_enabled() ) {
			$regex .= 'u';
		}

		/**
		 * Filters hook to modify post regex.
		 *
		 * @since 3.3.0
		 *
		 * @param string $regex Regex.
		 */
		return apply_filters( 'smartcrawl_autolinks_get_post_regex', $regex );
	}

	/**
	 * Gets absolute URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	private function get_absolute_url( $url ) {
		$is_relative = strpos( $url, '/' ) === 0;

		return $is_relative ? trailingslashit( home_url( $url ) ) : $url;
	}

	/**
	 * Find the position of the first occurrence of a substring in a string.
	 *
	 * Alias for strpos & stripos based on the case sensitive option.
	 *
	 * @since 3.3.0
	 *
	 * @param string $haystack Text to process.
	 * @param string $needle   String to check.
	 *
	 * @return false|int
	 */
	private function strpos( $haystack, $needle ) {
		return $this->is_option_enabled( 'casesens' )
			? strpos( $haystack, $needle )
			: stripos( $haystack, $needle );
	}

	/**
	 * Checks if content cache can be used.
	 *
	 * @since 3.3.2
	 *
	 * @param string $type Post type.
	 *
	 * @return bool
	 */
	private function can_cache_content( $type = 'post' ) {
		// If cache is not disabled.
		$can_cache = ! $this->is_option_enabled( 'disable_content_cache' );

		/**
		 * Filters hook to modify caching of content.
		 *
		 * @since 3.3.2
		 *
		 * @param bool   $can_cache Can cache.
		 * @param string $type      Post type.
		 */
		return apply_filters( 'smartcrawl_autolinks_can_cache_content', $can_cache, $type );
	}

	/**
	 * Gets site service instance.
	 *
	 * @return object
	 */
	private function is_premium_member() {
		return Service::get( Service::SERVICE_SITE )->is_member();
	}

	/**
	 * Get the insert options.
	 *
	 * @return array
	 */
	public function get_insert_options() {
		$result = array();

		foreach ( $this->get_insert_keys() as $key => $label ) {
			$result[ $key ] = array(
				'label' => $label,
				'value' => isset( $this->options['insert'] ) && in_array( $key, $this->options['insert'], true ),
			);
		}

		return $result;
	}

	/**
	 * Get the insert keys.
	 *
	 * @return array
	 */
	private function get_insert_keys() {
		// Add post types.
		foreach ( \smartcrawl_get_post_types() as $post_type => $pt ) {
			$key = strtolower( $pt->name );

			$insert[ $key ] = $pt->labels->name;
		}
		// Add comments.
		$insert['comment'] = __( 'Comments', 'wds' );

		// Add Woo Product category.
		if ( taxonomy_exists( 'product_cat' ) ) {
			$taxonomy = get_taxonomy( 'product_cat' );
			// Add product category.
			$insert['product_cat'] = empty( $taxonomy->label ) ? __( 'Product Categories', 'wds' ) : $taxonomy->label;
		}

		return $insert;
	}

	/**
	 * Get link to options.
	 *
	 * @return array
	 */
	public function get_linkto_options() {
		$result = array();

		foreach ( $this->get_linkto_keys() as $key => $label ) {
			$result[ $key ] = array(
				'label' => $label,
				'value' => isset( $this->options['link_to'] ) && in_array( $key, $this->options['link_to'], true ),
			);
		}

		return $result;
	}

	/**
	 * Get link to keys.
	 *
	 * @return array
	 */
	private function get_linkto_keys() {
		$post_types = array();
		foreach ( \smartcrawl_get_post_types() as $post_type => $pt ) {
			$key                      = strtolower( $pt->name );
			$post_types[ 'l' . $key ] = $pt->labels->name;
		}

		$taxonomies = array();
		foreach ( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
			if ( ! in_array( $taxonomy, array( 'nav_menu', 'link_category', 'post_format' ), true ) ) {
				$tax = get_taxonomy( $taxonomy );
				$key = strtolower( $tax->labels->name );

				$taxonomies[ 'l' . $key ] = $tax->labels->name;
			}
		}

		return array_merge( $post_types, $taxonomies );
	}

	/**
	 * Sets submodule options.
	 *
	 * @param array $options Submodule options.
	 *
	 * @return void
	 */
	public function set_options( $options = array() ) {
		$this->options = $options;

		if ( ! is_array( $this->options ) ) {
			$this->options = array();
		}

		if ( empty( $this->options['ignorepost'] ) ) {
			$this->options['ignorepost'] = '';
		}

		if ( empty( $this->options['ignore'] ) ) {
			$this->options['ignore'] = '';
		}

		if ( empty( $this->options['customkey'] ) ) {
			$this->options['customkey'] = '';
		}

		if ( empty( $this->options['cpt_char_limit'] ) ) {
			$this->options['cpt_char_limit'] = '';
		}

		if ( empty( $this->options['tax_char_limit'] ) ) {
			$this->options['tax_char_limit'] = '';
		}

		if ( ! isset( $this->options['link_limit'] ) ) {
			$this->options['link_limit'] = '';
		}

		if ( ! isset( $this->options['single_link_limit'] ) ) {
			$this->options['single_link_limit'] = '';
		}
	}
}