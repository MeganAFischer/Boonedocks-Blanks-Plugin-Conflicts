<?php
/**
 * Class Cache Manager
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Cache;

use SmartCrawl\Singleton;
use SmartCrawl\Controllers;

/**
 * Class Manager
 */
class Manager extends Controllers\Controller {

	use Singleton;

	/**
	 * Post cache instance.
	 *
	 * @var Post_Cache $post_cache
	 */
	private $post_cache;

	/**
	 * Term cache instance.
	 *
	 * @var Term_Cache $term_cache
	 */
	private $term_cache;

	/**
	 * Manager constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->post_cache = Post_Cache::get();
		$this->term_cache = Term_Cache::get();
	}

	/**
	 * Initialize the class.
	 *
	 * @return mixed|void
	 */
	protected function init() {
		add_action( 'save_post', array( $this, 'invalidate_post' ) );
		add_action( 'delete_post', array( $this, 'invalidate_post' ) );

		add_action( 'edit_term', array( $this, 'invalidate_term' ) );
		add_action( 'deleted_term_taxonomy', array( $this, 'invalidate_term' ) );
	}

	/**
	 * Invalidate post cache.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function invalidate_post( $post_id ) {
		$this->post_cache->purge( $post_id );
	}

	/**
	 * Invalidate term cache.
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return void
	 */
	public function invalidate_term( $term_id ) {
		$this->term_cache->purge( $term_id );
	}
}