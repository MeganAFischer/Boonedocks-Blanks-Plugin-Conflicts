<?php
/**
 * File containing the SmartCrawl_String class for SmartCrawl plugin.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

/**
 * Class SmartCrawl_String
 *
 * Provides string manipulation utilities for the SmartCrawl plugin.
 *
 * @package SmartCrawl
 */
class SmartCrawl_String {

	/**
	 * The string to be manipulated.
	 *
	 * @var string
	 */
	private $string;

	/**
	 * The syllable count of the string.
	 *
	 * @var int
	 */
	private $syllable_count;

	/**
	 * Helper for syllable operations.
	 *
	 * @var Syllable
	 */
	private $syllable_helper;

	/**
	 * Sentences with punctuation.
	 *
	 * @var string[]
	 */
	private $sentences_with_punctuation;

	/**
	 * Converts the string to uppercase.
	 *
	 * @var string Uppercase string.
	 */
	private $sentences;

	/**
	 * Words in the string.
	 *
	 * @var string[]
	 */
	private $words;

	/**
	 * Paragraphs in the string.
	 *
	 * @var string[]
	 */
	private $paragraphs;

	/**
	 * Keywords in the string.
	 *
	 * @var string[]
	 */
	private $keywords;

	/**
	 * Language code for the string.
	 *
	 * @var string
	 */
	private $language_code;

	/**
	 * Stop words for the language.
	 *
	 * @var string[]
	 */
	private $language_stopwords;

	/**
	 * Constructor for the SmartCrawl_String class.
	 *
	 * @param string $string        The string to analyse.
	 * @param string $language_code Language code.
	 */
	public function __construct( $string, $language_code = 'en' ) {
		$this->string = $string;

		$this->syllable_helper = new Syllable( $language_code );
		$this->language_code   = $language_code;
	}

	/**
	 * Converts the string to uppercase.
	 *
	 * @return string Uppercase string.
	 */
	public function uppercase() {
		return String_Utils::uppercase( $this->string );
	}

	/**
	 * Returns a substring of the string.
	 *
	 * @param int $start  Starting position.
	 * @param int $length Length of the substring.
	 *
	 * @return string Substring.
	 */
	public function substr( $start = 0, $length = null ) {
		return String_Utils::substr( $this->string, $start, $length );
	}

	/**
	 * Returns the length of the string.
	 *
	 * @return int Length of the string.
	 */
	public function length() {
		return String_Utils::len( $this->string );
	}

	/**
	 * Finds the position of the first occurrence of a substring.
	 *
	 * @param string $needle Substring to find.
	 * @param int    $offset Offset to start searching from.
	 *
	 * @return int Position of the substring.
	 */
	public function pos( $needle, $offset = 0 ) {
		return String_Utils::pos( $this->string, $needle, $offset );
	}

	/**
	 * Splits the string into words.
	 *
	 * @return array List of words.
	 */
	public function get_words() {
		if ( is_null( $this->words ) ) {
			$this->words = String_Utils::words( $this->string );
		}

		return $this->words;
	}

	/**
	 * Splits the string into sentences.
	 *
	 * @return array List of sentences.
	 */
	public function get_sentences() {
		if ( is_null( $this->sentences ) ) {
			$this->sentences = String_Utils::sentences( $this->string, false );
		}

		return $this->sentences;
	}

	/**
	 * Returns sentences with punctuation.
	 *
	 * @return array List of sentences with punctuation.
	 */
	public function get_sentences_with_punctuation() {
		if ( is_null( $this->sentences_with_punctuation ) ) {
			$this->sentences_with_punctuation = String_Utils::sentences( $this->string, true );
		}

		return $this->sentences_with_punctuation;
	}

	/**
	 * Splits the string into paragraphs.
	 *
	 * @return array List of paragraphs.
	 */
	public function get_paragraphs() {
		if ( is_null( $this->paragraphs ) ) {
			$this->paragraphs = String_Utils::paragraphs( $this->string );
		}

		return $this->paragraphs;
	}

	/**
	 * Converts the string to lowercase.
	 *
	 * @return string Lowercase string.
	 */
	public function lowercase() {
		return String_Utils::lowercase( $this->string );
	}

	/**
	 * Checks if the string starts with a given substring.
	 *
	 * @param string $needle Substring to check.
	 *
	 * @return bool True if the string starts with the substring, false otherwise.
	 */
	public function starts_with( $needle ) {
		return String_Utils::starts_with( $this->string, $needle );
	}

	/**
	 * Checks if the string ends with a given substring.
	 *
	 * @param string $needle Substring to check.
	 *
	 * @return bool True if the string ends with the substring, false otherwise.
	 */
	public function ends_with( $needle ) {
		return String_Utils::ends_with( $this->string, $needle );
	}

	/**
	 * Checks if the string contains stop words.
	 *
	 * @return bool True if the string contains stop words, false otherwise.
	 */
	public function has_stopwords() {
		$has   = false;
		$stops = $this->get_language_stopwords();
		$words = $this->get_words();

		foreach ( $words as $word ) {
			if ( ! in_array( $word, $stops, true ) ) {
				continue;
			}
			$has = true;
			break;
		}

		return $has;
	}

	/**
	 * Gets the stop words for the current language.
	 *
	 * @return array List of stop words.
	 */
	public function get_language_stopwords() {
		if ( is_null( $this->language_stopwords ) ) {
			$this->language_stopwords = $this->import_language_stopwords();
		}

		return $this->language_stopwords;
	}

	/**
	 * Imports stop words for a given language.
	 *
	 * @return array
	 */
	private function import_language_stopwords() {
		if ( empty( $this->language_code ) ) {
			return array();
		}

		$stop_words_file = sprintf(
			SMARTCRAWL_PLUGIN_DIR . 'core/resources/stop-words/%s.php',
			$this->language_code
		);

		if ( ! file_exists( $stop_words_file ) ) {
			return array();
		}

		return include $stop_words_file;
	}

	/**
	 * Extracts keywords from the string.
	 *
	 * @param int|bool $limit Optional limit of keywords to return.
	 *
	 * @return array List of keywords.
	 */
	public function get_keywords( $limit = false ) {
		if ( is_null( $this->keywords ) ) {
			$this->keywords = $this->find_keywords( $this->string );
		}

		$keywords = $this->keywords;

		return ! empty( $limit )
			? array_slice( $keywords, 0, $limit )
			: $keywords;
	}

	/**
	 * Finds keywords in the string.
	 *
	 * @param array $string List of keywords to find.
	 *
	 * @return array List of found keywords.
	 */
	private function find_keywords( $string ) {
		$keywords = array();
		if ( empty( $string ) ) {
			return $keywords;
		}

		$words = String_Utils::words( $string );
		if ( empty( $words ) ) {
			return $keywords;
		}

		$stopwords = $this->get_language_stopwords();

		foreach ( $words as $word ) {
			if ( in_array( $word, $stopwords, true ) ) {
				continue;
			}
			if ( empty( $keywords[ $word ] ) ) {
				$keywords[ $word ] = 0;
			}
			++$keywords[ $word ];
		}
		arsort( $keywords );

		return $keywords;
	}

	/**
	 * Returns the number of sentences in the string.
	 *
	 * @return int Number of sentences.
	 */
	public function get_sentence_count() {
		return count( $this->get_sentences() );
	}

	/**
	 * Returns the number of words in the string.
	 *
	 * @return int Number of words.
	 */
	public function get_word_count() {
		return count( $this->get_words() );
	}

	/**
	 * Returns the syllable count of the string.
	 *
	 * @return int Syllable count.
	 */
	public function get_syllable_count() {
		if ( is_null( $this->syllable_count ) ) {
			$this->syllable_count = $this->syllable_helper->count_syllables( $this->string );
		}

		return $this->syllable_count;
	}
}