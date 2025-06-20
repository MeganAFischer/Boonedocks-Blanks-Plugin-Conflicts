<?php
/**
 * Handles imports.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Configs;

use SmartCrawl\Models\Ignores;
use SmartCrawl\Modules\Advanced\Redirects\Database_Table;
use SmartCrawl\Singleton;
use SmartCrawl\Sitemaps\Utils;
use SmartCrawl\Work_Unit;
use function smartcrawl_get_array_value;

/**
 * Imports handling class.
 */
class Import extends Work_Unit {

	use Singleton;

	/**
	 * Model instance.
	 *
	 * @var Model_IO
	 */
	private $model;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->model = new Model_IO();
	}

	/**
	 * Loads all options.
	 *
	 * @param string $json JSON string to load from.
	 *
	 * @return Import Import instance.
	 */
	public static function load( $json ) {
		$me = new self();

		$me->load_all( $json );

		return $me;
	}

	/**
	 * Loads everything.
	 *
	 * @param string $json JSON string to load from.
	 *
	 * @return Model_IO instance
	 */
	public function load_all( $json ) {
		$data = json_decode( $json, true );

		if ( empty( $data ) ) {
			return $this->model;
		}

		$this->model->set_version( (string) smartcrawl_get_array_value( $data, 'version' ) );
		$this->model->set_url( (string) smartcrawl_get_array_value( $data, 'url' ) );

		foreach ( $this->model->get_sections() as $section ) {
			if ( ! isset( $data[ $section ] ) || ! is_array( $data[ $section ] ) ) {
				continue;
			}

			$this->model->set( $section, $data[ $section ] );
		}

		return $this->model;
	}

	/**
	 * Handles staging area saves
	 *
	 * @return bool
	 */
	public function save() {
		foreach ( $this->model->get_sections() as $section ) {
			$method = array( $this, "save_$section" );

			if ( ! is_callable( $method ) ) {
				continue;
			}

			$status = call_user_func( $method );

			if ( ! $status ) {
				$this->add_error( $section, __( 'Import process failed, aborting', 'wds' ) );
				return false;
			}
		}

		\SmartCrawl\Upgrade\Controller::get()->upgrade_advanced_options( $this->model->get_version(), true );

		return true;
	}

	/**
	 * Saves options.
	 *
	 * @return bool
	 */
	public function save_options() {
		foreach ( $this->model->get( Model_IO::OPTIONS ) as $key => $value ) {
			if ( false === $value ) {
				continue;
			}

			if ( 'wds_blog_tabs' === $key ) {
				update_site_option( $key, $value );
			} else {
				update_option( $key, $value );
			}
		}

		return true;
	}

	/**
	 * Saves ignores
	 *
	 * @return bool
	 */
	public function save_ignores() {
		if ( ! $this->has_same_url() ) {
			return true;
		}

		$data    = $this->model->get( Model_IO::IGNORES );
		$ignores = new Ignores();

		foreach ( $data as $key ) {
			$ignores->set_ignore( $key );
		}

		return true;
	}

	/**
	 * Saves extra URLs
	 *
	 * @return bool
	 */
	public function save_extra_urls() {
		if ( ! $this->has_same_url() ) {
			return true;
		}

		$data = $this->model->get( Model_IO::EXTRA_URLS );
		Utils::set_extra_urls( $data );

		return Utils::get_extra_urls() === $data;
	}

	/**
	 * Saves ignored URLs
	 *
	 * @return bool
	 */
	public function save_ignore_urls() {
		if ( ! $this->has_same_url() ) {
			return true;
		}

		$data = $this->model->get( Model_IO::IGNORE_URLS );
		Utils::set_ignore_urls( $data );

		return Utils::get_ignore_urls() === $data;
	}

	/**
	 * Saves ignored post IDs
	 *
	 * @return bool
	 */
	public function save_ignore_post_ids() {
		if ( ! $this->has_same_url() ) {
			return true;
		}

		$data = $this->model->get( Model_IO::IGNORE_POST_IDS );
		Utils::set_ignore_ids( $data );

		return Utils::get_ignore_ids() === $data;
	}

	/**
	 * Saves post meta entries.
	 *
	 * @TODO: actually implement this.
	 *
	 * @return bool
	 */
	public function save_postmeta() {
		return true;
	}

	/**
	 * Saves taxonomy meta entries.
	 *
	 * @TODO: actually implement this
	 *
	 * @return bool
	 */
	public function save_taxmeta() {
		return true;
	}

	/**
	 * Saves redirects
	 *
	 * @return bool
	 */
	public function save_redirects() {
		if ( ! $this->has_same_url() ) {
			return true;
		}

		$redirect_data = $this->model->get( Model_IO::REDIRECTS );
		$redirects     = array_map( array( '\SmartCrawl\Modules\Advanced\Redirects\Item', 'inflate' ), $redirect_data );

		$table = Database_Table::get();
		$table->delete_all();
		$table->insert_redirects( $redirects );

		return true;
	}

	/**
	 * Gets filtering prefix.
	 *
	 * @return string
	 */
	public function get_filter_prefix() {
		return 'wds-import';
	}

	/**
	 * Determines if config data has same url as current url.
	 *
	 * @return bool
	 */
	private function has_same_url() {
		$data = $this->model->get_all();

		if ( empty( $data['url'] ) ) {
			return false;
		}

		return $this->normalize_url( $data['url'] ) === $this->normalize_url( home_url() );
	}

	/**
	 * Custom method to get url without protocol and www.
	 *
	 * @param string $url Urls as string.
	 *
	 * @return string
	 */
	private function normalize_url( $url ) {
		$url = str_replace( array( 'http://', 'https://', 'www.' ), '', $url );

		return untrailingslashit( $url );
	}
}