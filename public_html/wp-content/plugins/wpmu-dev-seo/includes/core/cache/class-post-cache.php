<?php
/**
 * Singleton class for caching posts.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Cache;

use SmartCrawl\Singleton;
use SmartCrawl\Entities\Post;

/**
 * Post_Cache Class
 */
class Post_Cache {

	use Singleton;

	/**
	 * Stores cached data.
	 *
	 * @var array $cache
	 */
	private $cache = array();

	/**
	 * Retrieves a Post object given a post ID.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return Post|null The Post object if found, null otherwise.
	 */
	public function get_post( $post_id ) {
		if ( ! is_numeric( $post_id ) ) {
			return null;
		}

		if ( empty( $this->cache[ $post_id ] ) ) {
			$post = new Post( $post_id );

			if ( ! $post->get_wp_post() ) {
				return null;
			}

			$this->cache[ $post_id ] = $post;
		}

		return $this->cache[ $post_id ];
	}

	/**
	 * Removes a Post object from the cache given a post ID.
	 *
	 * @param int $post_id The ID of the post to remove from cache.
	 *
	 * @return void
	 */
	public function purge( $post_id ) {
		unset( $this->cache[ $post_id ] );
	}

	/**
	 * Purges all items from the cache.
	 *
	 * @return void
	 */
	public function purge_all() {
		$this->cache = array();
	}
}