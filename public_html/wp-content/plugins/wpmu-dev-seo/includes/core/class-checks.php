<?php
/**
 * Checks hub
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Cache\Post_Cache;
use SmartCrawl\Checks\Check;

/**
 * Checks dispatcher class
 */
class Checks extends Work_Unit {

	use Singleton;

	const ENDPOINT = 'endpoint';

	const POST = 'post';

	/**
	 * Remote handler for frontend post fetching
	 *
	 * @var Core_Request
	 */
	private $endpoint_remote_handler;

	/**
	 * Holds reference to checks.
	 *
	 * @var array
	 */
	private $checks = array(
		// Holds reference to checks that deal with final rendered content.
		'endpoint' => array(
			'imgalts_keywords',
			'content_length',
			'keyword_density',
			'links_count',
			'nofollow_links',
			'para_keywords',
			'subheadings_keywords',
		),
		// Holds reference to checks that deal with raw post data.
		'post'     => array(
			'focus',
			'focus_stopwords',
			'title_keywords',
			'title_length',
			'metadesc_keywords',
			'metadesc_length',
			'slug_keywords',
			'slug_underscores',
			'keywords_used',
			'metadesc_handcraft',
		),
	);

	/**
	 * Extra keyword checks.
	 *
	 * @since 3.4.0
	 *
	 * @var array
	 */
	private $extra_keyword_checks = array(
		'endpoint' => array(
			'subheadings_keywords',
			'keyword_density',
			'bolded_keyword',
		),
		'post'     => array(
			'title_secondary_keywords',
		),
	);

	/**
	 * Holds a reference to all checks that have been
	 * applied in this checks run
	 *
	 * @var array
	 */
	private $applied_checks = array();

	/**
	 * Post ID
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Is current check for primary keyword.
	 *
	 * @since 3.4.0
	 *
	 * @var bool
	 */
	private $is_primary = true;

	/**
	 * Holds current keyword.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	private $keywords = true;

	/**
	 * Static entry point, can be used instead of constructor
	 *
	 * Applies all queued checks to the subject post using only primary keyword.
	 *
	 * @since 3.4.0 Added params $keyword, $primary
	 *
	 * @param int          $post_id ID of the post to check.
	 * @param Core_Request $request Optional Core_Request instance to use - used in testing.
	 * @param string       $keyword Keyword to analyse.
	 * @param bool         $primary Is primary keyword check.
	 *
	 * @return object Checks instance
	 */
	public static function apply( $post_id, $request = false, $keyword = '', $primary = true ) {
		$me = new self();
		$me->set_post_id( $post_id );
		$me->set_keywords( $keyword );

		// Set if primary.
		$me->is_primary = (bool) $primary;

		if ( ! empty( $request ) ) {
			$me->set_remote_handler( $request );
		}

		// Primary keyword checks.
		$me->apply_post_checks();
		$me->apply_endpoint_checks();

		return $me;
	}

	/**
	 * Sets internal post ID
	 *
	 * @param int $post_id Post ID to check.
	 *
	 * @return bool Status
	 */
	public function set_post_id( $post_id ) {
		$this->post_id = (int) $post_id;

		return ! ! $this->post_id;
	}

	/**
	 * Set current keywords.
	 *
	 * @since 3.4.0
	 *
	 * @param string|array $keywords Keywords.
	 *
	 * @return void
	 */
	public function set_keywords( $keywords ) {
		$this->keywords = $this->get_focus( $keywords );
	}

	/**
	 * Set remote request handler.
	 *
	 * @param Core_Request $request Request.
	 *
	 * @return void
	 */
	public function set_remote_handler( $request ) {
		$this->endpoint_remote_handler = $request;
	}

	/**
	 * Applies check tests to post-specific queue.
	 *
	 * @return bool Overall status
	 */
	public function apply_post_checks() {
		$checks = $this->is_primary ? $this->checks : $this->extra_keyword_checks;

		$subject = $this->apply_filters( 'subject-post', false, $this->keywords, $this->is_primary );
		if ( empty( $subject ) ) {
			$subject = get_post( $this->post_id );
		}

		return $this->apply_checks( $checks['post'], $subject );
	}

	/**
	 * Applies check tests to endpoint-specific queue.
	 *
	 * @return bool Overall status
	 */
	public function apply_endpoint_checks() {
		$checks = $this->is_primary ? $this->checks : $this->extra_keyword_checks;

		if ( empty( $checks['endpoint'] ) ) {
			return true;
		}

		$subject = $this->apply_filters( 'subject-endpoint', false, $this->keywords, $this->is_primary );
		if ( empty( $subject ) ) {
			$subject = $this->get_endpoint_content();
		}
		if ( false === $subject ) {
			$this->add_error( 'checks', __( 'We encountered an error fetching your content', 'wds' ) );

			return false;
		}

		return $this->apply_checks( $checks['endpoint'], $subject );
	}

	/**
	 * Applies the checks in queue.
	 *
	 * @param array $checks  A list of checks to apply.
	 * @param mixed $subject Subject to apply the checks to.
	 *
	 * @return bool Overall status
	 */
	public function apply_checks( $checks, $subject ) {
		$overall_result = true;

		$keywords = $this->get_focus( $this->keywords );
		$language = $this->get_seo_analysis_language();
		foreach ( $checks as $check_id ) {
			/**
			 * Check.
			 *
			 * @var $check Check
			 */
			$check = $this->get_check( $check_id );
			if ( empty( $check ) ) {
				continue;
			}

			$check->set_subject( $subject );
			$check->set_focus( $keywords );
			$check->set_language( $language );
			$check->set_post_id( $this->post_id );
			$check->set_primary( $this->is_primary );

			$is_ignored = $this->is_ignored_check( $check_id );
			$result     = true;
			if ( ! $is_ignored ) {
				$result = $check->apply();
				if ( ! $result ) {
					$overall_result = false;
					$this->add_error( $check_id, $check->get_status_msg() );
				}
			}
			if ( ! $check->is_hidden() ) {
				$this->applied_checks[ $check_id ] = array(
					'status'  => $result,
					'ignored' => $is_ignored,
					'result'  => $check->get_result(),
				);
			}
		}

		return $overall_result;
	}

	/**
	 * Gets a list of keywords.
	 *
	 * @since 3.4.0 Param $primary_keyword added.
	 *
	 * @param string $keyword Is check for primary keyword.
	 *
	 * @return array A list of expected keywords
	 */
	public function get_focus( $keyword = '' ) {
		if ( empty( $keyword ) ) {
			$post = Post_Cache::get()->get_post( $this->post_id );
			if ( empty( $post ) ) {
				return array();
			}

			// For backward compatibility use primary keyword as backup.
			$keyword = array( $post->get_primary_keyword() );
		}

		// Make sure it's array.
		$keywords = (array) $keyword;

		return (array) $this->apply_filters( 'focus', $keywords, $this->post_id );
	}

	/**
	 * Instantiates check according to check ID
	 *
	 * @param string $check_id Check to be instantiated.
	 *
	 * @return bool|object Smartcrawl_Check_abstract object instance on success,
	 *                     (bool)false on failure
	 */
	public function get_check( $check_id ) {
		$cname = $this->get_check_class_name( $check_id );
		if ( ! class_exists( $cname ) ) {
			return false;
		}

		return new $cname();
	}

	/**
	 * Get class name for the check.
	 *
	 * @param string $check_id Check ID.
	 *
	 * @return string
	 */
	private function get_check_class_name( $check_id ) {
		$cname = str_replace( '_', ' ', $check_id );
		$cname = str_replace( ' ', '_', ucwords( $cname ) );

		return sprintf( '\SmartCrawl\Checks\%s', $cname );
	}

	/**
	 * Checks whether a check is to be ignored for the current post
	 *
	 * @param string $check_id ID of the check.
	 *
	 * @return bool
	 */
	public function is_ignored_check( $check_id ) {
		$ignored = self::get_ignored_checks( $this->post_id );

		return in_array( $check_id, $ignored, true );
	}

	/**
	 * Gets a list of post-specific ignored checks
	 *
	 * Ignored checks are skipped in analysis
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array A list of check IDs
	 */
	public static function get_ignored_checks( $post_id ) {
		// Make sure meta is fetched from the post, not a revision. The same thing is done in WP function update_post_meta.
		$revision_parent = wp_is_post_revision( $post_id );
		if ( $revision_parent ) {
			$post_id = $revision_parent;
		}
		$ignored = get_post_meta( $post_id, '_wds_ignored_checks', true );

		return is_array( $ignored ) && ! empty( $ignored )
			? $ignored
			: array();
	}

	/**
	 * Fetches local endpoint via HTTP API
	 *
	 * @return bool|string Endpoint content as string, or
	 *                     (bool)false if something went wrong
	 */
	public function get_endpoint_content() {
		if ( empty( $this->endpoint_remote_handler ) || ! ( $this->endpoint_remote_handler instanceof Core_Request ) ) {
			$this->set_remote_handler( new Core_Request() );
		}
		$content = $this->endpoint_remote_handler->get_rendered_post( $this->post_id );
		if ( is_wp_error( $content ) ) {
			return false;
		}

		return (string) $content;
	}

	/**
	 * Whether the readability check for this post is ignored
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public static function is_readability_ignored( $post_id ) {
		$ignored = self::get_ignored_checks( $post_id );

		return in_array( 'readability', $ignored, true );
	}

	/**
	 * Adds a single check to the ignored checks stack
	 *
	 * @param int    $post_id  ID of the post.
	 * @param string $check_id ID of the check.
	 *
	 * @return bool
	 */
	public static function add_ignored_check( $post_id, $check_id ) {
		$ignored   = self::get_ignored_checks( $post_id );
		$ignored[] = $check_id;

		return self::set_ignored_checks( $post_id, $ignored );
	}

	/**
	 * Updates a list of ignored checks
	 *
	 * @param int   $post_id ID of the post.
	 * @param array $checks  Full list of checks.
	 *
	 * @return bool
	 */
	public static function set_ignored_checks( $post_id, $checks ) {
		if ( ! is_array( $checks ) ) {
			return false;
		}
		$checks = array_filter( array_map( 'trim', array_unique( $checks ) ) );

		return update_post_meta( $post_id, '_wds_ignored_checks', $checks );
	}

	/**
	 * Removes a single check from the ignored checks stack
	 *
	 * @param int    $post_id  ID of the post.
	 * @param string $check_id ID of the check.
	 *
	 * @return bool
	 */
	public static function remove_ignored_check( $post_id, $check_id ) {
		$ignored = self::get_ignored_checks( $post_id );
		$key     = array_search( $check_id, $ignored, true );

		if ( false === $key ) {
			return false;
		}
		unset( $ignored[ $key ] );

		return self::set_ignored_checks( $post_id, $ignored );
	}

	/**
	 * Gets checks that have been applied in this run
	 *
	 * @return array
	 */
	public function get_applied_checks() {
		return $this->applied_checks;
	}

	/**
	 * Calculates approximate checks success percentage
	 *
	 * Approximate because the result is rounded to integer
	 *
	 * @return int Success percentage
	 */
	public function get_percentage() {
		if ( $this->get_status() ) {
			return 100;
		}

		$cnum = count( $this->get_checks() );
		$enum = count( $this->get_errors() );
		$err  = (int) ( ( $enum / $cnum ) * 100 );

		return 100 - $err;
	}

	/**
	 * Checks whether we're all good and without issues
	 *
	 * @return bool
	 */
	public function get_status() {
		$errors = $this->get_errors();

		return empty( $errors );
	}

	/**
	 * Gets the list of checks to be performed for primary keyword.
	 *
	 * @param string $which Which checks to perform.
	 *
	 * @return array List of check IDs
	 */
	public function get_checks( $which = false ) {
		if ( self::ENDPOINT === $which ) {
			return $this->checks['endpoint'];
		}
		if ( self::POST === $which ) {
			return $this->checks['post'];
		}

		return array_merge( $this->checks['endpoint'], $this->checks['post'] );
	}

	/**
	 * Gets the list of checks to be performed on extra keywords.
	 *
	 * @since 3.4.0
	 *
	 * @param string $which Which checks to perform.
	 *
	 * @return array List of check IDs
	 */
	public function get_extra_checks( $which = false ) {
		if ( self::ENDPOINT === $which ) {
			return $this->extra_keyword_checks['endpoint'];
		}
		if ( self::POST === $which ) {
			return $this->extra_keyword_checks['post'];
		}

		return array_merge( $this->extra_keyword_checks['endpoint'], $this->extra_keyword_checks['post'] );
	}

	/**
	 * Gets filtering prefix
	 *
	 * @return string
	 */
	public function get_filter_prefix() {
		return 'wds-checks';
	}

	/**
	 * Get language for analysing.
	 *
	 * @return string
	 */
	private function get_seo_analysis_language() {
		$locale = str_replace( array( '-', '_' ), '-', get_locale() );
		$parts  = explode( '-', $locale );

		return apply_filters( 'wds_post_seo_analysis_language', $parts[0], $this->post_id );
	}
}