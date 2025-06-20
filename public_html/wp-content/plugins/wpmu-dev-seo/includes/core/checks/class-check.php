<?php
/**
 * Abstractions related to checks
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Cache\String_Cache;
use SmartCrawl\SmartCrawl_String;
use SmartCrawl\String_Utils;

/**
 * Check abstraction class
 */
abstract class Check {

	/**
	 * Holds subject reference
	 *
	 * @var string|\WP_Post
	 */
	private $subject = '';
	/**
	 * Holds a list of expected keywords (as strings)
	 *
	 * This is an internal normalized focus keywords representation,
	 * where the key phrases are also normalized to words.
	 *
	 * @var array
	 */
	private $focus = array();
	/**
	 * Holds a list of raw keywords
	 *
	 * As opposed to $_focus, this one holds raw,
	 * denormalized version of focus key words|phrases.
	 *
	 * @var array
	 */
	private $raw_focus_keywords = array();

	/**
	 * Language.
	 *
	 * @var string
	 */
	private $language = 'en';

	/**
	 * Holds post id reference
	 *
	 * @var int
	 */
	private $post_id = 0;

	/**
	 * Holds the flag for primary keyword status.
	 *
	 * @since 3.4.0
	 *
	 * @var bool
	 */
	private $is_primary = true;

	/**
	 * Holds the flag for hidden check result.
	 *
	 * @since 3.4.0
	 *
	 * @var bool
	 */
	private $is_hidden = false;

	/**
	 * Constructor
	 *
	 * Accepts optional current working markup parameter
	 *
	 * @param string $markup Current working markup (optional).
	 */
	public function __construct( $markup = '' ) {
		$this->set_subject( $markup );
	}

	/**
	 * Sets working markup
	 *
	 * @param string|\WP_Post $subject Markup to work with.
	 *
	 * @return void
	 */
	public function set_subject( $subject = '' ) {
		$this->subject = $subject;
	}

	/**
	 * Sets current working post id.
	 *
	 * @param int $id Post ID.
	 *
	 * @return void
	 */
	public function set_post_id( $id ) {
		$this->post_id = $id;
	}

	/**
	 * Sets flag for primary keyword.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $is_primary Is primary keyword.
	 */
	public function set_primary( $is_primary = true ) {
		$this->is_primary = (bool) $is_primary;
	}

	/**
	 * Sets hidden flag.
	 *
	 * If hidden the result won't be included.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $is_hidden Is hidden.
	 */
	protected function set_hidden( $is_hidden = true ) {
		$this->is_hidden = (bool) $is_hidden;
	}

	/**
	 * Applies the check
	 *
	 * @return bool
	 */
	abstract public function apply();

	/**
	 * Check result
	 *
	 * @return array
	 */
	public function get_result() {
		return array();
	}

	/**
	 * Gets current working markup
	 *
	 * @return string|\WP_Post Working markup
	 */
	public function get_markup() {
		return $this->subject;
	}

	/**
	 * Gets current working post id.
	 *
	 * @since 3.4.0
	 *
	 * @return int
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * Returns raw, non-internal focus keywords
	 *
	 * @return array
	 */
	public function get_raw_focus() {
		return (array) $this->raw_focus_keywords;
	}

	/**
	 * Check if current processing check is for primary keyword.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function is_primary_keyword() {
		return $this->is_primary;
	}

	/**
	 * Check if current check if hidden.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return $this->is_hidden;
	}

	/**
	 * Retrieves keyword label based on primary or secondary.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected function get_keyword_label() {
		return $this->is_primary_keyword() ? __( 'primary keyphrase', 'wds' ) : __( 'secondary keyphrase', 'wds' );
	}

	/**
	 * Checks if subject string length is within constraints
	 *
	 * @param string $str Subject string.
	 * @param int    $min Optional minimum length.
	 * @param int    $max Optional maximum length.
	 *
	 * @return bool|int (bool)true if within constraints
	 *                  negative integer if shorter than $min
	 *                  positive integer if longer than $max
	 */
	public function is_within_char_length( $str, $min, $max ) {
		$str = '' . $str;
		$min = (int) $min;
		$max = (int) $max;

		if ( $min && String_Utils::len( $str ) < $min ) {
			return - 1;
		}
		if ( $max && String_Utils::len( $str ) > $max ) {
			return 1;
		}

		return true;
	}

	/**
	 * Checks whether we have some keywords in place
	 *
	 * @param string $raw Subject string.
	 *
	 * @return bool
	 */
	public function has_focus( $raw ) {
		$string   = String_Cache::get()->get_string( $raw, $this->get_language() );
		$kws      = $string->get_keywords();
		$expected = $this->get_focus();

		// We don't seem to have any focus keywords, so... yeah.
		if ( empty( $expected ) ) {
			return true;
		}

		$diff = array_diff( $expected, array_keys( $kws ) );

		return count( $expected ) !== count( $diff );
	}

	/**
	 * Returns list of expected keywords
	 *
	 * @return array
	 */
	public function get_focus() {
		return (array) $this->focus;
	}

	/**
	 * Sets expected keywords
	 *
	 * Converts keywords collection to internal representation,
	 * abstracting away key phrases and normalizing everything
	 * to a list of words which can be checked.
	 *
	 * @param array $keywords List of expected keywords.
	 *
	 * @return bool
	 */
	public function set_focus( $keywords = array() ) {
		$this->raw_focus_keywords = $keywords;
		$this->focus              = $this->prepare_focus( $keywords );

		return ! ! $this->focus;
	}

	/**
	 * Prepare focus keywords.
	 *
	 * @param array $keywords Keywords.
	 *
	 * @return string[]
	 */
	protected function prepare_focus( $keywords ) {
		$kwds = array();
		foreach ( $keywords as $k ) {
			$keyword_string = new SmartCrawl_String( $k, $this->get_language() );
			$kwds           = array_merge( $kwds, $keyword_string->get_keywords() );
		}

		return array_unique( array_keys( $kwds ) );
	}

	/**
	 * Set current check language.
	 *
	 * @param string $language Language key.
	 *
	 * @return void
	 */
	public function set_language( $language ) {
		$this->language = $language;
	}

	/**
	 * Retrieves current check language.
	 *
	 * @return string
	 */
	public function get_language() {
		return $this->language;
	}
}