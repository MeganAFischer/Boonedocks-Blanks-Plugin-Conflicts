<?php
/**
 * File containing the Youtube_Data_Fetcher class for SmartCrawl plugin.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

/**
 * Class Youtube_Data_Fetcher
 *
 * Provides utilities to fetch data from YouTube.
 *
 * @package SmartCrawl
 */
class Youtube_Data_Fetcher {

	/**
	 * Base URL for YouTube API.
	 *
	 * @var string
	 */
	public static $api_base = 'https://www.googleapis.com/youtube/v3';

	/**
	 * Base URL for YouTube thumbnails.
	 *
	 * @var string
	 */
	public static $thumbnail_base = 'https://i.ytimg.com/vi/';

	/**
	 * Fetches video information from YouTube.
	 *
	 * @param string      $url YouTube video URL.
	 * @param string|bool $api_key API key for YouTube Data API.
	 *
	 * @return array|false Video information on success, false on failure.
	 */
	public static function get_video_info( $url, $api_key = false ) {
		if ( empty( $api_key ) ) {
			$api_key = self::get_api_key();
		}

		if ( empty( $api_key ) ) {
			return false;
		}

		$playlist = self::get_playlist_id( $url );

		if ( $playlist ) {
			$result = self::remote_get(
				self::$api_base . '/playlistItems?' . http_build_query(
					array(
						'part'       => 'snippet',
						'playlistId' => $playlist,
						'key'        => $api_key,
						'maxResults' => 1,
					)
				)
			);

			if ( empty( $result['items'][0]['snippet']['resourceId']['videoId'] ) ) {
				return null;
			}

			$vid = $result['items'][0]['snippet']['resourceId']['videoId'];
		} else {
			$vid = self::get_video_id( $url );
		}

		if ( ! $vid ) {
			return false;
		}

		$result = self::remote_get(
			self::$api_base . '/videos?' . http_build_query(
				array(
					'part' => 'contentDetails,snippet',
					'id'   => $vid,
					'key'  => $api_key,
				)
			)
		);

		if ( empty( $result['items'][0]['contentDetails'] ) ) {
			return null;
		}

		$video_details = $result['items'][0]['contentDetails'];

		$interval                      = new \DateInterval( $video_details['duration'] );
		$video_details['duration_sec'] = $interval->h * 3600 + $interval->i * 60 + $interval->s;

		$video_details['thumbnail']['default']       = self::$thumbnail_base . $vid . '/default.jpg';
		$video_details['thumbnail']['mqDefault']     = self::$thumbnail_base . $vid . '/mqdefault.jpg';
		$video_details['thumbnail']['hqDefault']     = self::$thumbnail_base . $vid . '/hqdefault.jpg';
		$video_details['thumbnail']['sdDefault']     = self::$thumbnail_base . $vid . '/sddefault.jpg';
		$video_details['thumbnail']['maxresDefault'] = self::$thumbnail_base . $vid . '/maxresdefault.jpg';

		$snippet = array();
		if ( ! empty( $result['items'][0]['snippet'] ) ) {
			$snippet = $result['items'][0]['snippet'];
		}

		$video_details['url'] = $url;

		return array_merge( $video_details, $snippet );
	}

	/**
	 * Performs an HTTP request using the GET method and returns its response from Youtube.
	 *
	 * @param string $url Youtube API url.
	 *
	 * @return array|false
	 */
	private static function remote_get( $url ) {
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			Logger::error( $response->get_error_message() );

			return;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( false === $result ) {
			Logger::error( "Failed to retrieve data from YouTube API for {$url}" );

			return false;
		}

		return $result;
	}

	/**
	 * Retrieves the API key for YouTube Data API.
	 *
	 * @return string|false API key on success, false on failure.
	 */
	private static function get_api_key() {
		$options    = Settings::get_component_options( Settings::COMP_SCHEMA );
		$connect_yt = (bool) \smartcrawl_get_array_value( $options, 'schema_enable_yt_api' );
		$api_key    = (string) \smartcrawl_get_array_value( $options, 'schema_yt_api_key' );
		if ( $connect_yt && $api_key ) {
			return trim( $api_key );
		}

		return false;
	}

	/**
	 * Checks if the URL is a shortened YouTube URL.
	 *
	 * @param string $url YouTube URL.
	 *
	 * @return bool True if the URL is a shortened YouTube URL, false otherwise.
	 */
	private static function is_short_url( $url ) {
		return wp_parse_url( $url, PHP_URL_HOST ) === 'youtu.be';
	}

	/**
	 * Retrieves playlist id from YouTube url.
	 *
	 * @param string $url YouTube url.
	 *
	 * @return string|false Playlist ID i
	 */
	private static function get_playlist_id( $url ) {
		if ( wp_parse_url( $url, PHP_URL_PATH ) === '/playlist' ) {
			parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $playlist_id );

			return \smartcrawl_get_array_value( $playlist_id, 'list' );
		}

		return false;
	}

	/**
	 * Retrieves video ID from YouTube URL.
	 *
	 * @param string $url YouTube URL.
	 *
	 * @return string|false Video ID on success, false on failure.
	 */
	private static function get_video_id( $url ) {
		if ( self::is_short_url( $url ) ) {
			$url_parts = explode( '/', $url );

			return array_pop( $url_parts );
		} else {
			parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $youtube_id );

			return \smartcrawl_get_array_value( $youtube_id, 'v' );
		}
	}
}