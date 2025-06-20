<?php
/**
 * Handles config export.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Configs;

use SmartCrawl\Modules\Advanced\Redirects\Database_Table;
use SmartCrawl\Models\Ignores;
use SmartCrawl\Settings;
use SmartCrawl\Sitemaps\Utils;

/**
 * Config Export class
 */
class Export {

	/**
	 * Model instance
	 *
	 * @var Model_IO
	 */
	protected $model;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->model = new Model_IO();
	}

	/**
	 * Loads all options
	 *
	 * @return Model_IO instance
	 */
	public static function load() {
		$me = new self();

		$me->load_all();

		return $me->model;
	}

	/**
	 * Loads everything
	 *
	 * @return Model_IO instance
	 */
	public function load_all() {
		$this->model->set_version( SMARTCRAWL_VERSION );
		$this->model->set_url( home_url() );

		foreach ( $this->model->get_sections() as $section ) {
			$method = array( $this, "load_$section" );
			if ( ! is_callable( $method ) ) {
				continue;
			}

			call_user_func( $method );
		}

		return $this->model;
	}

	/**
	 * Loads options
	 *
	 * @return Model_IO instance
	 */
	public function load_options() {
		$options = array();

		$components = Settings::get_all_components();

		foreach ( $components as $component ) {
			$option_name = $this->get_option_name( $component );

			if ( $option_name ) {
				$options[ $option_name ] = Settings::get_component_options( $component );
			}
		}

		$options[ Settings::ADVANCED_MODULE ] = get_option( Settings::ADVANCED_MODULE );
		$options['wds_settings_options']      = Settings::get_local_settings();

		$options['wds_blog_tabs'] = get_site_option( 'wds_blog_tabs' );

		$this->model->set( Model_IO::OPTIONS, $options );

		return $this->model;
	}

	/**
	 * Gets option name
	 *
	 * @param string $comp Partial.
	 *
	 * @return string|false Options key
	 */
	public function get_option_name( $comp ) {
		if ( in_array( $comp, Settings::get_all_components(), true ) ) {
			return "wds_{$comp}_options";
		}

		return false;
	}

	/**
	 * Loads ignores
	 *
	 * @return Model_IO instance
	 */
	public function load_ignores() {
		$model = new Ignores();
		$this->model->set( Model_IO::IGNORES, $model->get_all() );

		return $this->model;
	}

	/**
	 * Loads extra sitemap URLs
	 *
	 * @return Model_IO instance
	 */
	public function load_extra_urls() {
		$this->model->set( Model_IO::EXTRA_URLS, Utils::get_extra_urls() );

		return $this->model;
	}

	/**
	 * Loads ignore sitemap URLs.
	 *
	 * This is used in `load_all` method.
	 *
	 * @return Model_IO instance
	 */
	public function load_ignore_urls() {
		$this->model->set( Model_IO::IGNORE_URLS, Utils::get_ignore_urls() );

		return $this->model;
	}

	/**
	 * Loads extra sitemap post IDs.
	 *
	 * This is used in `load_all` method.
	 *
	 * @return Model_IO instance
	 */
	public function load_ignore_post_ids() {
		$this->model->set( Model_IO::IGNORE_POST_IDS, Utils::get_ignore_ids() );

		return $this->model;
	}

	/**
	 * Loads all stored postmeta.
	 *
	 * This is used in `load_all` method.
	 *
	 * @return Model_IO instance
	 */
	public function load_postmeta() {
		return $this->model;
	}

	/**
	 * Loads all stored taxmeta for the current site
	 *
	 * This is used in `load_all` method.
	 *
	 * @return Model_IO instance
	 */
	public function load_taxmeta() {
		return $this->model;
	}

	/**
	 * Loads all stored redirects for the current site
	 *
	 * This is used in `load_all` method.
	 *
	 * @return Model_IO instance
	 */
	public function load_redirects() {
		$table = Database_Table::get();

		$this->model->set( Model_IO::REDIRECTS, $table->get_deflated_redirects() );

		return $this->model;
	}
}