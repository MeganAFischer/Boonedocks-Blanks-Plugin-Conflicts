<?php
/**
 * This is what's used to calculate SEO and readability analysis score.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Models;

use SmartCrawl\Checks;
use SmartCrawl\Core_Request;
use SmartCrawl\Html;
use SmartCrawl\Settings;
use SmartCrawl\Cache\Post_Cache;
use SmartCrawl\SmartCrawl_String;
use SmartCrawl\Readability\Formulas\Flesch;

/**
 * Analysis model class
 */
class Analysis extends Model {

	const DATA_ANALYSIS = 'analysis';

	const DATA_READABILITY = 'readability';

	const META_KEY_ANALYSIS = '_wds_analysis';

	const META_KEY_READABILITY = '_wds_readability';

	const DATA_ANALYSIS_EXTRA = 'analysis_extra';

	/**
	 * Keeps track of analyzed post ID
	 *
	 * @var int
	 */
	private $post_id = false;

	/**
	 * Core request instance.
	 *
	 * @var Core_Request $endpoint_remote_handler Request object.
	 */
	protected $endpoint_remote_handler;

	/**
	 * Constructor
	 *
	 * @param int $post_id Optional post ID to analyze.
	 */
	public function __construct( $post_id = false ) {
		$this->set_post_id( $post_id );
	}

	/**
	 * Post ID setter
	 *
	 * @param int $post_id Post ID to set.
	 *
	 * @return bool
	 */
	public function set_post_id( $post_id ) {
		$this->post_id = (int) $post_id;

		return ! ! $this->post_id;
	}

	/**
	 * Gets model type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'analysis';
	}

	/**
	 * Clears cached post data
	 *
	 * @return bool
	 */
	public function clear_cached_data() {
		foreach ( $this->get_known_data_types() as $type ) {
			$this->set_post_data( $type, false );
		}

		return true;
	}

	/**
	 * Returns a list of known data types
	 *
	 * @return array List of known data types
	 */
	public function get_known_data_types() {
		return array(
			self::DATA_ANALYSIS,
			self::DATA_READABILITY,
			self::DATA_ANALYSIS_EXTRA,
		);
	}

	/**
	 * General data setter
	 *
	 * @param string     $data_type Data type to check.
	 * @param array|bool $data      Data to set.
	 *
	 * @return bool
	 */
	public function set_post_data( $data_type, $data ) {
		if ( ! in_array( $data_type, $this->get_known_data_types(), true ) ) {
			return false;
		}

		return update_post_meta( $this->post_id, "_wds_{$data_type}", $data );
	}

	/**
	 * Gets readability level label for the current post
	 *
	 * @param bool $update Force update if there's no data.
	 *
	 * @return string
	 */
	public function get_readability_level( $update = true ) {
		$data = $this->get_post_data( self::DATA_READABILITY );
		if ( empty( $data ) && ! empty( $update ) ) {
			$this->update_readability_data();
			$data = $this->get_post_data( self::DATA_READABILITY );
		}

		if ( empty( $data['raw_score'] ) || ! isset( $data['score'] ) ) {
			return empty( $data['error'] )
				? __( 'Error calculating readability', 'wds' )
				: $data['error'];
		}

		$map = $this->get_readability_levels_map();

		$score = $this->normalize_readability_score( $data['score'] );
		$level = '';
		foreach ( $map as $label => $lvl ) {
			if ( ! is_array( $lvl ) || ! isset( $lvl['min'] ) || ! isset( $lvl['max'] ) ) {
				continue;
			}

			$min = $lvl['min'];
			$max = $lvl['max'];

			if ( $score < $min || $score > $max ) {
				continue;
			}

			$level = $label;
			break;
		}

		return $level;
	}

	/**
	 * Data getter
	 *
	 * @param string $data_type Data type to check.
	 *
	 * @return array|false Post data, or (bool)false on failure
	 */
	public function get_post_data( $data_type ) {
		$data = array();
		if ( ! in_array( $data_type, $this->get_known_data_types(), true ) ) {
			return false;
		}

		return \smartcrawl_get_value( $data_type, $this->post_id );
	}

	/**
	 * Updates the post readability data
	 *
	 * @return bool Readability status
	 */
	public function update_readability_data() {
		if ( ! Settings::get_setting( 'analysis-readability' ) ) {
			return false;
		}

		$this->set_default_remote_handler();
		$request = new Core_Request();
		$content = $this->endpoint_remote_handler->get_rendered_post( $this->post_id );
		$str     = is_wp_error( $content )
			? ''
			: Html::plaintext( $content );

		if ( empty( $str ) ) {
			$raw_result = false;
			$error      = __( 'No content to check', 'wds' );
		} else {
			$flesch = $this->get_flesch( $str );
			if ( $flesch->is_language_supported() ) {
				$raw_result = $flesch->get_score();
				$error      = false;
			} else {
				$raw_result = false;
				$error      = __( 'Your language is currently not supported', 'wds' );
			}
		}
		$result = 0;

		if ( false === $raw_result ) {
			$error = ! empty( $error ) ? $error : __( 'Error calculating readability', 'wds' );
		} else {
			$result = $this->normalize_readability_score( $raw_result );
		}

		$is_readable = $result > $this->get_readability_threshold();

		$this->set_post_data(
			self::DATA_READABILITY,
			array(
				'score'       => $result,
				'raw_score'   => $raw_result,
				'is_readable' => $is_readable,
				'error'       => $error,
			)
		);

		return ! empty( $result ) && $is_readable;
	}

	/**
	 * Sets default handler, if it hasn't been set already
	 *
	 * @return bool
	 */
	public function set_default_remote_handler() {
		if ( ! empty( $this->endpoint_remote_handler ) ) {
			return true;
		}

		$this->endpoint_remote_handler = new Core_Request();

		return true;
	}

	/**
	 * Ensures readability sore is within mappable range
	 *
	 * @param float $score Raw score.
	 *
	 * @return float Normalized score, within mappable range
	 */
	public function normalize_readability_score( $score ) {
		$map = $this->get_readability_levels_map();

		$minimum = 999;
		$maximum = 0;
		foreach ( $map as $label => $lvl ) {
			if ( ! is_array( $lvl ) || ! isset( $lvl['min'] ) || ! isset( $lvl['max'] ) ) {
				continue;
			}

			$min = $lvl['min'];
			if ( $min < $minimum ) {
				$minimum = $min;
			}

			$max = $lvl['max'];
			if ( $max > $maximum ) {
				$maximum = $max;
			}
		}
		if ( $score < $minimum ) {
			$score = $minimum;
		}
		if ( $score > $maximum ) {
			$score = $maximum;
		}

		return $score;
	}

	/**
	 * Gets mapped readability levels
	 *
	 * @return array
	 */
	public function get_readability_levels_map() {
		$very_easy        = __( 'Very easy to read', 'wds' );
		$easy             = __( 'Easy to read', 'wds' );
		$fairly_easy      = __( 'Fairly easy to read', 'wds' );
		$plain            = __( 'Standard', 'wds' );
		$fairly_difficult = __( 'Fairly difficult to read', 'wds' );
		$difficult        = __( 'Difficult to read', 'wds' );
		$confusing        = __( 'Very difficult to read', 'wds' );

		$easy_tag             = esc_html__( 'Easy', 'wds' );
		$plain_tag            = esc_html__( 'Standard', 'wds' );
		$difficult_tag        = esc_html__( 'Difficult', 'wds' );
		$fairly_difficult_tag = esc_html__( 'Fairly difficult', 'wds' );

		return array(
			$very_easy        => array(
				'min' => 90,
				'max' => 100,
				'tag' => $easy_tag,
			),
			$easy             => array(
				'min' => 80,
				'max' => 89.9,
				'tag' => $easy_tag,
			),
			$fairly_easy      => array(
				'min' => 70,
				'max' => 79.9,
				'tag' => $easy_tag,
			),
			$plain            => array(
				'min' => 60,
				'max' => 69.9,
				'tag' => $plain_tag,
			),
			$fairly_difficult => array(
				'min' => 50,
				'max' => 59.9,
				'tag' => $fairly_difficult_tag,
			),
			$difficult        => array(
				'min' => 30,
				'max' => 49.9,
				'tag' => $difficult_tag,
			),
			$confusing        => array(
				'min' => 0,
				'max' => 29.9,
				'tag' => $difficult_tag,
			),
		);
	}

	/**
	 * Gets maximum readability level
	 *
	 * Content over this value is considered complex.
	 *
	 * @return float
	 */
	public function get_readability_threshold() {
		return 60.0;
	}

	/**
	 * Checks readability status of post versus our threshold
	 *
	 * As a side-effect, will update readability meta info if it's
	 * not readily available.
	 *
	 * @return bool Readability status
	 */
	public function is_readable() {
		if ( Checks::is_readability_ignored( $this->post_id ) ) {
			return true;
		}

		$data = $this->get_post_data( self::DATA_READABILITY );
		if ( empty( $data ) || ! is_array( $data ) || empty( $data['is_readable'] ) ) {
			return $this->update_readability_data();
		}

		return ! ! $data['is_readable'];
	}

	/**
	 * Updates post analysis data
	 *
	 * Runs all registered checks against the internal post instance
	 *
	 * @return bool Checks status
	 */
	public function update_analysis_data() {
		if ( ! Settings::get_setting( 'analysis-seo' ) ) {
			return false;
		}

		$this->set_default_remote_handler();

		// Get post object.
		$post = Post_Cache::get()->get_post( $this->post_id );
		if ( empty( $post ) ) {
			return false;
		}

		$primary_keyword = $post->get_primary_keyword();
		$extra_keywords  = $post->get_extra_keywords();
		// We need at least primary keyword.
		if ( empty( $primary_keyword ) ) {
			return false;
		}

		/**
		 * Checks class.
		 *
		 * @var $checks       Checks
		 */
		$checks = Checks::apply( $this->post_id, $this->endpoint_remote_handler, $primary_keyword );

		// Set primary keyword analysis data.
		$this->set_post_data(
			self::DATA_ANALYSIS,
			array(
				'errors'     => $checks->get_errors(),
				'percentage' => $checks->get_percentage(),
				'checks'     => $checks->get_applied_checks(),
			)
		);

		$has_errors = false;
		$extra_data = array();

		// Set extra keywords analysis data.
		if ( ! empty( $extra_keywords ) ) {
			foreach ( $extra_keywords as $keyword ) {
				$extra_checks = Checks::apply( $this->post_id, $this->endpoint_remote_handler, $keyword, false );
				// Add to array.
				$extra_data[ sanitize_html_class( $keyword ) ] = array(
					'errors'     => $extra_checks->get_errors(),
					'percentage' => $extra_checks->get_percentage(),
					'checks'     => $extra_checks->get_applied_checks(),
				);

				if ( ! $extra_checks->get_status() ) {
					$has_errors = true;
				}
			}
		}

		// Extra keyword analysis data.
		$this->set_post_data( self::DATA_ANALYSIS_EXTRA, $extra_data );

		return $checks->get_status() && ! $has_errors;
	}

	/**
	 * Sets endpoint remote handler.
	 *
	 * The handler will be used for readability data computation.
	 * Used in tests.
	 *
	 * @param object $request A Core_Request instance.
	 */
	public function set_remote_handler( $request ) {
		$this->endpoint_remote_handler = $request;
	}

	/**
	 * Gets the overall SEO analysis for site posts
	 *
	 * @return array
	 */
	public function get_overall_seo_analysis() {
		$overall_analysis = array();
		$cutoff           = 100;
		$checked          = 0;
		$passed           = 0;
		$post_types       = $this->get_post_types_for_overview();

		foreach ( $post_types as $type ) {
			$checked_in_type = 0;
			$passed_in_type  = 0;
			$posts           = $this->get_posts_for_overview( $type, self::META_KEY_ANALYSIS );

			foreach ( $posts as $post ) {
				$this->set_post_id( $post->ID );
				$has_seo_data = $this->has_post_data( self::DATA_ANALYSIS );
				if ( $has_seo_data ) {

					++$checked;
					++$checked_in_type;

					$seo_data   = $this->get_post_data( self::DATA_ANALYSIS );
					$percentage = intval( \smartcrawl_get_array_value( $seo_data, 'percentage' ) );

					if ( $percentage >= $cutoff ) {
						++$passed_in_type;
						++$passed;
					}
				}
			}

			$overall_analysis['post-types'][ $type ] = array(
				'total'  => $checked_in_type,
				'passed' => $passed_in_type,
			);
		}

		$overall_analysis['total']  = $checked;
		$overall_analysis['passed'] = $passed;

		return $overall_analysis;
	}

	/**
	 * Gets a list of post types to be used in overview
	 *
	 * @return array
	 */
	private function get_post_types_for_overview() {
		$post_types = get_post_types( array( 'public' => true ) );

		// Media doesn't support analysis.
		$excluded = array( 'attachment' );
		// Remove excluded items.
		$post_types = is_array( $post_types ) ? array_diff( $post_types, $excluded ) : array();

		/**
		 * Filter to modify the list of post types for analysis overview.
		 *
		 * @since 3.6.0
		 *
		 * @param array $post_types Post types.
		 * @param array $excluded   Excluded types.
		 */
		return apply_filters( 'wds_get_post_types_for_overview', $post_types, $excluded );
	}

	/**
	 * Gets posts sample for the overview calculations
	 *
	 * @param string $type     Post type to query.
	 * @param string $meta_key Meta key to be used in the query (overview analysis part).
	 *
	 * @return array
	 */
	private function get_posts_for_overview( $type, $meta_key ) {
		return get_posts(
			array(
				'posts_per_page' => 100,
				'post_type'      => $type,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
				'meta_key'       => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
	}

	/**
	 * Data presence checker
	 *
	 * @param string $data_type Data type to check.
	 *
	 * @return bool
	 */
	public function has_post_data( $data_type ) {
		$data = $this->get_post_data( $data_type );

		return false !== $data;
	}

	/**
	 * Gets the overall readability analysis for site posts
	 *
	 * @return array
	 */
	public function get_overall_readability_analysis() {
		$overall_analysis = array();
		$checked          = 0;
		$passed           = 0;
		$post_types       = $this->get_post_types_for_overview();

		foreach ( $post_types as $type ) {

			$type_overview = array();
			$posts         = $this->get_posts_for_overview( $type, self::META_KEY_READABILITY );

			foreach ( $posts as $post ) {
				$this->set_post_id( $post->ID );
				$data_available = $this->has_post_data( self::DATA_READABILITY );
				if ( $data_available ) {

					$data       = $this->get_post_data( self::DATA_READABILITY );
					$post_score = intval( \smartcrawl_get_array_value( $data, 'score' ) );

					$readability_state = $this->get_kincaid_readability_state( $post_score, false );

					$type_overview[ $readability_state ] = intval( \smartcrawl_get_array_value( $type_overview, $readability_state ) ) + 1;

					if ( 'warning' === $readability_state || 'success' === $readability_state ) {
						++$passed;
					}

					if ( 'invalid' !== $readability_state ) {
						++$checked;
					}
				}
			}

			$overall_analysis['post-types'][ $type ] = $type_overview;
		}

		$overall_analysis['total']  = $checked;
		$overall_analysis['passed'] = $passed;

		return $overall_analysis;
	}

	/**
	 * Takes readability score and returns a string representing readability status.
	 *
	 * @param float $readability_score   Score for which range needs to be found.
	 * @param bool  $readability_ignored Whether readability is marked as ignored by the user.
	 *
	 * @return string A string representing readability status.
	 */
	public function get_kincaid_readability_state( $readability_score, $readability_ignored ) {
		$readability_state = '';
		if ( 0 === intval( $readability_score ) || $readability_ignored ) {
			$readability_state = 'invalid';
		} elseif ( $readability_score < 50 ) {
			$readability_state = 'error';
		} elseif ( $readability_score < 60 ) {
			$readability_state = 'warning';
		} elseif ( $readability_score <= 100 ) {
			$readability_state = 'success';
		}

		return $readability_state;
	}

	/**
	 * Get readability analysis language.
	 *
	 * @return mixed|null
	 */
	private function get_readability_lang() {
		$locale = str_replace( array( '-', '_' ), '-', get_locale() );
		$parts  = explode( '-', $locale );

		return apply_filters( 'wds_post_readability_language', $parts[0], $this->post_id );
	}

	/**
	 * Get readability analysis flesch instance.
	 *
	 * @param string $content Content to be analyzed.
	 * @param string $lang   Language to check (By default current language).
	 *
	 * @return Flesch
	 *
	 * @since 3.4.0
	 */
	private function get_flesch( $content, $lang = '' ) {
		$lang               = empty( $lang ) ? $this->get_readability_lang() : $lang;
		$readability_string = new SmartCrawl_String( $content, $lang );

		return new Flesch( $readability_string, $lang );
	}

	/**
	 * Check if a language is supported for readability analysis.
	 *
	 * @since 3.4.0
	 *
	 * @param string $lang Language to check (By default current language).
	 *
	 * @return bool
	 */
	public function is_readability_lang_supported( $lang = '' ) {
		$flesch = $this->get_flesch( '', $lang );

		return $flesch->is_language_supported();
	}
}