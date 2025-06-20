<?php
/**
 * Import/export model for config.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Configs;

/**
 * Model IO class.
 */
class Model_IO {
	// todo: when we upgrade controller db structure we should consider the below functionalities.
	const OPTIONS         = 'options';
	const IGNORES         = 'ignores';
	const EXTRA_URLS      = 'extra_urls';
	const POSTMETA        = 'postmeta';
	const TAXMETA         = 'taxmeta';
	const REDIRECTS       = 'redirects';
	const REDIRECT_TYPES  = 'redirect_types'; // Deprecated. For configs coming from old versions.
	const IGNORE_URLS     = 'ignore_urls';
	const IGNORE_POST_IDS = 'ignore_post_ids';

	/**
	 * Intermediate staging area
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Intermediate staging area
	 *
	 * @var array
	 */
	private $ignores = array();

	/**
	 * Intermediate staging area
	 *
	 * @var array
	 */
	private $extra_urls = array();

	/**
	 * Intermediate staging area
	 *
	 * @var array
	 */
	private $postmeta = array();

	/**
	 * Intermediate staging area
	 *
	 * @var array
	 */
	private $taxmeta = array();

	/**
	 * Intermediate staging area
	 *
	 * @var array
	 */
	private $redirects = array();

	/**
	 * Intermediate staging area
	 *
	 * @var array
	 */
	private $redirect_types = array();

	/**
	 * Intermediate staging area
	 *
	 * @var array
	 */
	private $ignore_urls = array();

	/**
	 * Intermediate staging area
	 *
	 * @var array
	 */
	private $ignore_post_ids = array();

	/**
	 * Config Version.
	 *
	 * @var string
	 */
	private $version = '';

	/**
	 * The Site URL of config.
	 *
	 * @var string
	 */
	private $url = '';

	/**
	 * Returns a list of known sections.
	 *
	 * @return array List of IO sections.
	 */
	public function get_sections() {
		return array(
			self::OPTIONS,
			self::IGNORES,
			self::EXTRA_URLS,
			self::POSTMETA,
			self::TAXMETA,
			self::REDIRECTS,
			self::REDIRECT_TYPES,
			self::IGNORE_URLS,
			self::IGNORE_POST_IDS,
		);
	}

	/**
	 * Sets the property value.
	 *
	 * @param string $what IO section to set.
	 * @param array  $value Value to set.
	 *
	 * @return bool Status
	 */
	public function set( $what, $value ) {
		if ( ! in_array( $what, $this->get_sections(), true ) ) {
			return false;
		}

		$this->$what = $value;

		return (bool) $this->$what;
	}

	/**
	 * Encodes all loaded parameters into a JSON string.
	 *
	 * @return string JSON
	 */
	public function get_json() {
		return wp_json_encode( $this->get_all() );
	}

	/**
	 * Sets the version for the config model.
	 *
	 * @param string $version The version as string.
	 *
	 * @return void
	 */
	public function set_version( $version ) {
		$this->version = $version;
	}

	/**
	 * Retrieves the version of the model.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Sets the URL.
	 *
	 * @param string $url The URL to set.
	 *
	 * @return void
	 */
	public function set_url( $url ) {
		$this->url = $url;
	}

	/**
	 * Gets the URL
	 *
	 * @return string The URL.
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Gets all loaded parameters
	 *
	 * @return array Everything
	 */
	public function get_all() {
		$ret = array(
			'version' => $this->get_version(),
			'url'     => $this->get_url(),
		);

		foreach ( $this->get_sections() as $sect ) {
			$ret[ $sect ] = $this->get( $sect );
		}

		return $ret;
	}

	/**
	 * Gets loaded options.
	 *
	 * @param string $what Which part to get.
	 *
	 * @return array
	 */
	public function get( $what ) {
		$ret = array();

		if ( ! in_array( $what, $this->get_sections(), true ) ) {
			return $ret;
		}

		$ret = $this->$what;

		return (array) $ret;
	}
}