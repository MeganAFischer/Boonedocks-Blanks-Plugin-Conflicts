<?php
/**
 * Video sitemap.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\Video;

/**
 * Sitemap
 *
 * @todo Remove this class until we can properly develop this feature. We can probably reuse the schema video functionality
 */
class Sitemap {

	const VIDEO_SITEMAP_LIMIT = 50000;

	/**
	 * Provider regex.
	 *
	 * @var array
	 */
	private $_provider_regex = array();

	/**
	 * No video oembed.
	 *
	 * @var string[]
	 */
	private $_no_video_oembed = array(
		'polldaddy',
		'smugmug',
		'flickr',
		'scribd',
		'photobucket',
		'twitter',
	);

	/**
	 * Items.
	 *
	 * @var array
	 */
	private $_items = array();

	/**
	 * Class constructor.
	 */
	private function __construct() {
		if ( ! is_admin() ) {
			$this->_provider_regex = $this->get_provider_regexen();
		}
	}

	/**
	 * Get provider regexen.
	 *
	 * @return array
	 */
	private function get_provider_regexen() {
		$oembed      = $this->get_oembed_providers();
		$static      = $this->get_static_providers();
		$supplements = $this->get_supplement_providers();

		return array_merge(
			$oembed,
			$static,
			$supplements
		);
	}

	/**
	 * Get oEmbed providers.
	 *
	 * @return array
	 */
	private function get_oembed_providers() {
		if ( ! class_exists( '\WP_oEmbed' ) ) {
			// Short out if not available.
			include ABSPATH . WPINC . '/class-oembed.php';
		}
		$embed     = new \WP_oEmbed();
		$providers = array();
		foreach ( $embed->providers as $rx => $provider ) {
			if ( empty( $provider[0] ) ) {
				continue;
			}
			if ( ! $this->filter_non_video_provider_callback( $provider[0] ) ) {
				continue;
			}
			if ( ! empty( $provider[1] ) ) {
				// Kill front delimiter.
				$rx = substr( $rx, 1 );
				// Kill end delimiter and possibly the "i" modifier.
				$end = ( 'i' === substr( $rx, - 1 ) ) ? 2 : 1;
				$rx  = substr( $rx, 0, strlen( $rx ) - $end );
			}
			$providers[] = $rx;
		}

		return $providers;
	}

	/**
	 * Filter non-video provider callback.
	 *
	 * @param string $provider Provider URL.
	 *
	 * @return bool
	 */
	private function filter_non_video_provider_callback( $provider ) {
		foreach ( $this->_no_video_oembed as $skip ) {
			if ( preg_match( '/' . preg_quote( $skip, '/' ) . '/i', $provider ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get static providers.
	 *
	 * @return array
	 */
	private function get_static_providers() {
		return array(
			'//(www\\.)?youtube.com/watch.*',
			'//youtu.be/.*',
			'//blip.tv/.*',
			'//(.*\\.)?vimeo\\.com/.*',
			'//(www\\.)?dailymotion\\.com/.*',
			'//(www\\.)?hulu\\.com/watch/.*',
			'//(www\\.)?viddler\\.com/.*',
			'//qik.com/.*',
			'//revision3.com/.*',
			'//wordpress.tv/.*',
			'//(www\\.)?funnyordie\\.com/videos/.*',
		);
	}

	/**
	 * Get supplement providers.
	 *
	 * @return array
	 */
	private function get_supplement_providers() {
		return array(
			'//(www\\.)?youtube.com/.*', // For IFRAME embeds.
		);
	}

	/**
	 * Serve the sitemap.
	 */
	public static function serve() {
		$me = new Sitemap();
		$me->add_hooks();

		add_action( 'save_post', array( $me, 'clean_posts_cache' ) );
		add_action( 'deleted_post', array( $me, 'clean_posts_cache' ) );
		add_action( 'wp_trash_post', array( $me, 'clean_posts_cache' ) );
	}

	/**
	 * Add hooks.
	 */
	private function add_hooks() {
		if ( is_admin() ) {
			return;
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) && preg_match( '~' . preg_quote( '/video-sitemap.xml', '/' ) . '(\.gz)?$~i', sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) {
			$this->update_items_list();
			$this->serve_video_sitemap();
		}
	}

	/**
	 * Update items list.
	 */
	private function update_items_list() {
		global $wpdb;

		$posts = wp_cache_get( 'wds-select-items-list', 'wds-video-sitemaps' );
		if ( ! $posts ) {
			$likes = join( "' OR post_content REGEXP '", $this->_provider_regex );
			$limit = self::VIDEO_SITEMAP_LIMIT;
			$sql   = "SELECT * FROM $wpdb->posts WHERE post_status='publish' AND (post_content REGEXP '$likes') LIMIT $limit";
			$posts = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		wp_cache_add( 'wds-select-items-list', $posts, 'wds-video-sitemaps', 86400 );

		foreach ( $posts as $post ) {
			// Exclude by post types.
			// ...
			// Exclude by taxonomies.
			// ...
			$this->add_video_item( $post );
		}
	}

	/**
	 * Add video item.
	 *
	 * @param object $raw Raw post object.
	 */
	private function add_video_item( $raw ) {
		$player = $this->extract_player_loc( $raw->ID, $raw->post_content );
		if ( ! $player ) {
			return;
		}

		$image = apply_filters( 'wds-video_sitemaps-thumbnail_url-default', '' );
		if ( ! $image ) {
			$image = $this->extract_thumbnail_from_player_src( $raw->ID, $player );
			$image = apply_filters( 'wds-video_sitemaps-thumbnail_url', $image, $raw->ID, $player );
			$image = $image ? $image : apply_filters( 'wds-video_sitemaps-thumbnail_url-fallback', $image );
		}
		if ( ! $image ) {
			// No thumbnail image, we can't add this item.
			return;
		}
		$loc                  = urldecode( get_permalink( $raw->ID ) );
		$this->_items[ $loc ] = array(
			'title'            => $raw->post_title,
			'description'      => substr( wp_strip_all_tags( $raw->post_content ), 0, 12 ),
			'thumbnail_loc'    => $image,
			'player_loc'       => $player,
			'publication_date' => mysql2date( 'Y-m-d', $raw->post_date ),
		);
	}

	/**
	 * Extract player location.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $body    Post content.
	 *
	 * @return string|false
	 */
	private function extract_player_loc( $post_id, $body ) {
		$post_id = (int) $post_id;
		$markup  = false;
		$src     = false;

		// We get all the post meta.
		$all_post_meta = get_post_meta( $post_id );

		// Now iterate through the meta and see if we can find an _oembed* element.
		foreach ( $all_post_meta as $key => $value ) {
			if ( preg_match( '/^_oembed.*/', $key ) && $value ) {
				// We have found an oembed meta, let's save it.
				$markup = $value;
				break;
			}
		}

		if ( $markup ) {
			$matches = array();
			preg_match( '/src=[\'"](.*?)[\'"]/', $markup, $matches );
			if ( empty( $matches[1] ) ) {
				return apply_filters( 'wds-video_sitemaps-player_loc', false, $post_id, $body );
			}
			$src = $matches[1];
		} else {
			// No eombed video found, heuristics should kick in.
			foreach ( $this->_provider_regex as $rx ) {
				$matches = array();
				if ( ! preg_match( '#(' . $rx . '?)[\'"]#i', $body, $matches ) ) {
					continue;
				}
				$src = substr( $matches[0], 0, - 1 );
				break;
			}
		}

		return apply_filters( 'wds-video_sitemaps-player_loc', $src, $post_id, $body );
	}

	/**
	 * Extract thumbnail from player source.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $src     Player source URL.
	 *
	 * @return string|false
	 */
	private function extract_thumbnail_from_player_src( $post_id, $src ) {
		$host = wp_parse_url( $src, PHP_URL_HOST );
		$path = wp_parse_url( $src, PHP_URL_PATH );

		$path_parts = explode( '/', $path );
		$video_id   = end( $path_parts );

		if ( preg_match( '/youtu\.?be/', $host ) ) {
			// YouTube.
			return $video_id
				? "http://img.youtube.com/vi/$video_id/hqdefault.jpg"
				: '';
		} elseif ( preg_match( '/vimeo/', $host ) ) {
			// Vimeo requires us to an API call, so...
			// First find out video ID and check cache.
			$thumbnail = get_post_meta( $post_id, '_vimeo_thumbnail_id-' . $video_id, true );
			if ( $thumbnail ) {
				return $thumbnail;
			}

			// Next, check if we're to do this.
			if ( ! ( defined( '\SMARTCRAWL_VIDEO_SITEMAP_ALLOW_API_CALLS' ) && \SMARTCRAWL_VIDEO_SITEMAP_ALLOW_API_CALLS ) ) {
				return false;
			}

			// No cache - fetch from remote API and update cache for next time.
			$response = wp_remote_get( "http://vimeo.com/api/v2/video/$video_id.php" );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}
			if ( ! empty( $body[0]['thumbnail_medium'] ) ) {
				$thumbnail = $body[0]['thumbnail_medium'];
				update_post_meta( $post_id, '_vimeo_thumbnail_id-' . $video_id, $thumbnail );
			}

			return $thumbnail;
		} elseif ( preg_match( '/blip\.tv/', $host ) ) {
			// Blip.tv.
			$thumbnail = get_post_meta( $post_id, '_blip_thumbnail_id-' . $video_id, true );
			if ( $thumbnail ) {
				return $thumbnail;
			}

			// Blip.tv - same deal as Vimeo, remote call is needed.
			if ( ! ( defined( '\SMARTCRAWL_VIDEO_SITEMAP_ALLOW_API_CALLS' ) && \SMARTCRAWL_VIDEO_SITEMAP_ALLOW_API_CALLS ) ) {
				return false;
			}

			// No cache - fetch from remote API and update cache for next time.
			$response = wp_remote_get( "http://blip.tv/players/episode/$video_id?skin=rss" );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}
			$body = wp_remote_retrieve_body( $response );
			preg_match( '/<blip:picture>(.*)<\/blip:picture>/i', $body, $matches );
			if ( ! empty( $matches[1] ) ) {
				$thumbnail = $matches[1];
				update_post_meta( $post_id, '_blip_thumbnail_id-' . $video_id, $thumbnail );
			}

			return $thumbnail;
		} elseif ( preg_match( '/wordpress\./', $host ) ) {
			// WordPress.tv.
			$thumbnail = get_post_meta( $post_id, '_wordpresstv_thumbnail_id-' . $video_id, true );
			if ( $thumbnail ) {
				return $thumbnail;
			}

			// Dispatch an oEmbed call, if allowed and needed.
			if ( ! ( defined( '\SMARTCRAWL_VIDEO_SITEMAP_ALLOW_API_CALLS' ) && \SMARTCRAWL_VIDEO_SITEMAP_ALLOW_API_CALLS ) ) {
				return false;
			}

			$post = get_post( $post_id );

			preg_match( '#(//wordpress.tv/[\-_/.a-z0-9]+)#i', $post->post_content, $matches );

			if ( empty( $matches[1] ) ) {
				return false;
			}

			$response = wp_remote_get( '//public-api.wordpress.com/oembed/1.0/?format=json&url=' . rawurlencode( $matches[1] ) . '&for=' . rawurlencode( site_url() ) );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! $body ) {
				return false;
			}

			if ( ! empty( $body['thumbnail_url'] ) ) {
				$thumbnail = $body['thumbnail_url'];
				update_post_meta( $post_id, '_wordpresstv_thumbnail_id-' . $video_id, $thumbnail );
			}

			return $thumbnail;
		}

		// Default.
		return apply_filters( 'wds-video_sitemaps-thumbnail_url-' . $host, '', $src, $post_id );
	}

	/**
	 * Serve video sitemap.
	 */
	private function serve_video_sitemap() {
		if ( ! $this->_items ) {
			return false;
		}

		$map = $this->prepare_sitemap();

		if ( ! $map ) {
			return false;
		}

		header( 'Content-type: text/xml' );
		echo esc_html( $map );
		die;
	}

	/**
	 * Prepare sitemap.
	 *
	 * @return string|false
	 */
	private function prepare_sitemap() {
		if ( ! $this->_items ) {
			return false;
		}

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

		foreach ( $this->_items as $loc => $item ) {
			$xml .= '<url>';
			$xml .= "<loc>$loc</loc>";

			$keys = array_keys( $item );
			$xml .= '<video>';
			foreach ( $keys as $key ) {
				$value = empty( $item[ $key ] ) ? '' : htmlspecialchars( $item[ $key ] );
				$xml  .= "<video:$key>$value</video:$key>\n";
			}
			$xml .= '</video>';

			$xml .= '</url>';
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * Clean posts cache.
	 */
	public function clean_posts_cache() {
		wp_cache_delete( 'wds-select-items-list', 'wds-video-sitemaps' );
	}

}