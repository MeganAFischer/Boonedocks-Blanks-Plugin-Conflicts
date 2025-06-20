<?php
/**
 * Media class for handling media schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Entities;
use SmartCrawl\Html;
use SmartCrawl\Schema;

/**
 * Class Media
 *
 * Handles media schema fragments.
 */
class Media extends Fragment {

	/**
	 * Post entity.
	 *
	 * @var Entities\Post
	 */
	private $post;

	/**
	 * Schema utilities.
	 *
	 * @var Schema\Utils
	 */
	private $utils;

	/**
	 * Schema data.
	 *
	 * @var array
	 */
	private $schema = array();

	/**
	 * Media schema data controller.
	 *
	 * @var Media
	 */
	private $media_schema_data_controller;

	/**
	 * Constructor.
	 *
	 * @param Entities\Post $post The post entity.
	 */
	public function __construct( $post ) {
		$this->post                         = $post;
		$this->utils                        = Schema\Utils::get();
		$this->media_schema_data_controller = Schema\Media::get();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array The raw schema data.
	 */
	protected function get_raw() {
		$wp_post = $this->post->get_wp_post();

		if ( $this->is_audio_enabled() || $this->is_video_enabled() ) {
			$this->media_schema_data_controller->maybe_refresh_wp_embeds_cache( $wp_post );
			$cache = $this->media_schema_data_controller->get_cache( $wp_post->ID );

			if ( ! empty( $cache ) ) {
				$this->add_oembed_schema( $cache );
			}
		}

		$this->add_attachment_schema( $wp_post );

		return $this->schema;
	}

	/**
	 * Adds oEmbed schema data.
	 *
	 * @param array $cache The cache data.
	 *
	 * @return void
	 */
	private function add_oembed_schema( $cache ) {
		$enable_audio = $this->is_audio_enabled();
		$audio_data   = \smartcrawl_get_array_value( $cache, 'audio' );
		if ( $enable_audio && ! empty( $audio_data ) && is_array( $audio_data ) ) {
			foreach ( $audio_data as $audio_datum ) {
				$this->add_schema(
					$this->get_audio_schema( $audio_datum )
				);
			}
		}

		$enable_youtube = (bool) $this->utils->get_schema_option( 'schema_enable_yt_api' );
		$youtube_data   = \smartcrawl_get_array_value( $cache, 'youtube' );
		$youtube_data   = empty( $youtube_data ) ? array() : $youtube_data;

		$enable_video = $this->is_video_enabled();
		$video_data   = \smartcrawl_get_array_value( $cache, 'video' );
		if ( $enable_video && ! empty( $video_data ) && is_array( $video_data ) ) {
			foreach ( $video_data as $video_id => $video_datum ) {
				if ( $enable_youtube && array_key_exists( $video_id, $youtube_data ) ) {
					$this->add_schema(
						$this->get_youtube_schema( $youtube_data[ $video_id ], $video_datum )
					);
				} else {
					$this->add_schema(
						$this->get_video_schema( $video_datum )
					);
				}
			}
		}
	}

	/**
	 * Retrieves audio schema data.
	 *
	 * @param array $data The audio data.
	 *
	 * @return array The audio schema data.
	 */
	private function get_audio_schema( $data ) {
		return $this->media_data_to_schema(
			array(
				'title'         => 'name',
				'url'           => 'url',
				'description'   => 'description',
				'thumbnail_url' => 'thumbnailUrl',
			),
			$data,
			Schema\Type_Constants::TYPE_AUDIO_OBJECT
		);
	}

	/**
	 * Retrieves video schema data.
	 *
	 * @param array $data The video data.
	 *
	 * @return array The video schema data.
	 */
	private function get_video_schema( $data ) {
		$schema = $this->media_data_to_schema(
			array(
				'title'            => 'name',
				'url'              => 'url',
				'description'      => 'description',
				'upload_date'      => 'uploadDate',
				'thumbnail_url'    => array( 'thumbnail', 'url' ),
				'thumbnail_width'  => array( 'thumbnail', 'width' ),
				'thumbnail_height' => array( 'thumbnail', 'height' ),
			),
			$data,
			Schema\Type_Constants::TYPE_VIDEO_OBJECT
		);

		$duration = $this->get_duration( $data );
		if ( $duration ) {
			$schema['duration'] = $duration;
		}

		return $this->add_embed_url_property( $schema, $data );
	}

	/**
	 * Retrieves YouTube schema data.
	 *
	 * @param array $data The YouTube data.
	 * @param array $embed_data The embed data.
	 *
	 * @return array The YouTube schema data.
	 */
	private function get_youtube_schema( $data, $embed_data ) {
		$schema = $this->media_data_to_schema(
			array(
				'title'                => 'name',
				'url'                  => 'url',
				'description'          => 'description',
				'publishedAt'          => 'uploadDate',
				'duration'             => 'duration',
				'defaultAudioLanguage' => 'inLanguage',
				'definition'           => 'videoQuality',
			),
			$data,
			Schema\Type_Constants::TYPE_VIDEO_OBJECT
		);

		$schema = $this->add_youtube_thumbnail_data( $data, $schema );

		$tags = \smartcrawl_get_array_value( $data, 'tags' );
		if ( $tags && is_array( $tags ) ) {
			$schema['keywords'] = join( ',', $tags );
		}

		return $this->add_embed_url_property( $schema, $embed_data );
	}

	/**
	 * Adds embed URL property to schema.
	 *
	 * @param array $schema The schema data.
	 * @param array $embed_data The embed data.
	 *
	 * @return array The updated schema data.
	 */
	private function add_embed_url_property( $schema, $embed_data ) {
		if ( isset( $embed_data['html'] ) ) {
			$src_attribute = Html::find_attributes( 'iframe', 'src', $embed_data['html'] );
			if ( ! empty( $src_attribute ) ) {
				$schema['embedUrl'] = array_shift( $src_attribute );
			}
		}

		return $schema;
	}

	/**
	 * Converts seconds to duration format.
	 *
	 * @param int $seconds The duration in seconds.
	 *
	 * @return string The duration in ISO 8601 format.
	 */
	private function seconds_to_duration( $seconds ) {
		$mins = (int) gmdate( 'i', $seconds );
		$secs = (int) gmdate( 's', $seconds );

		return "PT{$mins}M{$secs}S";
	}

	/**
	 * Retrieves duration from data.
	 *
	 * @param array $data The data containing duration.
	 *
	 * @return string The duration in ISO 8601 format.
	 */
	private function get_duration( $data ) {
		$seconds = \smartcrawl_get_array_value( $data, 'duration' );
		if ( ! $seconds ) {
			return '';
		}

		return $this->seconds_to_duration( $seconds );
	}

	/**
	 * Converts media data to schema format.
	 *
	 * @param array $mapping The mapping of source keys to target keys.
	 * @param array $data The media data.
	 * @param array $type The schema type.
	 *
	 * @return array The schema data.
	 */
	private function media_data_to_schema( $mapping, $data, $type ) {
		$schema = array(
			'@type' => $type,
		);
		foreach ( $mapping as $source_key => $target_key ) {
			$source_value = \smartcrawl_get_array_value( $data, $source_key );
			if ( $source_value ) {
				\smartcrawl_put_array_value( $source_value, $schema, $target_key );
			}
		}
		if ( ! empty( $schema['author'] ) ) {
			$schema['author'] = array( '@type' => Schema\Type_Constants::TYPE_PERSON ) + $schema['author'];
		}
		if ( ! empty( $schema['publisher'] ) ) {
			$schema['publisher'] = array( '@type' => Schema\Type_Constants::TYPE_ORGANIZATION ) + $schema['publisher'];
		}
		if ( ! empty( $schema['thumbnail'] ) ) {
			$schema['thumbnail']    = array( '@type' => Schema\Type_Constants::TYPE_IMAGE ) + $schema['thumbnail'];
			$schema['thumbnailUrl'] = $schema['thumbnail']['url'];
		}

		return $schema;
	}

	/**
	 * Adds YouTube thumbnail data to schema.
	 *
	 * @param array $data The YouTube data.
	 * @param array $schema The schema data.
	 *
	 * @return array The updated schema data.
	 */
	private function add_youtube_thumbnail_data( $data, array $schema ) {
		$thumbnails            = \smartcrawl_get_array_value( $data, 'thumbnails' );
		$thumbnail_url_default = '';

		foreach ( $thumbnails as $thumbnail_size => $thumbnail ) {
			$thumbnail_url         = \smartcrawl_get_array_value( $thumbnail, 'url' );
			$schema['thumbnail'][] = array(
				'@type'  => Schema\Type_Constants::TYPE_IMAGE,
				'url'    => $thumbnail_url,
				'width'  => \smartcrawl_get_array_value( $thumbnail, 'width' ),
				'height' => \smartcrawl_get_array_value( $thumbnail, 'height' ),
			);

			if ( 'default' === $thumbnail_size ) {
				$thumbnail_url_default = $thumbnail_url;
			}
		}
		if ( $thumbnail_url_default ) {
			$schema['thumbnailUrl'] = $thumbnail_url_default;
		}

		return $schema;
	}

	/**
	 * Adds attachment schema data.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return void
	 */
	private function add_attachment_schema( $post ) {
		$src_attributes = Html::find_attributes( 'video, audio', 'src', $post->post_content );

		foreach ( $src_attributes as $html_element => $src_url ) {
			$attachment_id = attachment_url_to_postid( $src_url );
			if ( ! $attachment_id ) {
				continue;
			}

			$attachment = get_post( $attachment_id );

			if ( $this->is_mime_type_video( $attachment ) ) {
				$this->add_schema(
					$this->get_video_attachment_schema( $attachment, $html_element )
				);
			}

			if ( $this->is_mime_type_audio( $attachment ) ) {
				$this->add_schema(
					$this->get_audio_attachment_schema( $attachment )
				);
			}
		}
	}

	/**
	 * Retrieves video attachment schema data.
	 *
	 * @param \WP_Post $attachment The attachment object.
	 * @param string   $video_element_html The video element HTML.
	 *
	 * @return array The video attachment schema data.
	 */
	private function get_video_attachment_schema( $attachment, $video_element_html ) {
		$attachment_url    = wp_get_attachment_url( $attachment->ID );
		$attachment_schema = $this->get_attachment_schema( Schema\Type_Constants::TYPE_VIDEO_OBJECT, $attachment, $attachment_url );

		// Video poster image.
		$poster_image_url = $this->get_video_poster_attribute( $video_element_html );
		if ( $poster_image_url ) {
			$attachment_schema['thumbnailUrl'] = $poster_image_url;
		}

		return $attachment_schema;
	}

	/**
	 * Retrieves video poster attribute.
	 *
	 * @param string $video_element_html The video element HTML.
	 *
	 * @return string The poster URL.
	 */
	private function get_video_poster_attribute( $video_element_html ) {
		$poster_values = Html::find_attributes( 'video', 'poster', $video_element_html );

		if ( count( $poster_values ) > 0 ) {
			return array_shift( $poster_values );
		}

		return '';
	}

	/**
	 * Retrieves audio attachment schema data.
	 *
	 * @param \WP_Post $attachment The attachment object.
	 *
	 * @return array The audio attachment schema data.
	 */
	private function get_audio_attachment_schema( $attachment ) {
		return $this->get_attachment_schema(
			Schema\Type_Constants::TYPE_AUDIO_OBJECT,
			$attachment,
			wp_get_attachment_url( $attachment->ID )
		);
	}

	/**
	 * Checks if attachment is a video.
	 *
	 * @param \WP_Post $attachment The attachment object.
	 *
	 * @return bool True if the attachment is a video, false otherwise.
	 */
	private function is_mime_type_video( $attachment ) {
		return strpos( $attachment->post_mime_type, 'video/' ) !== false;
	}

	/**
	 * Checks if attachment is an audio.
	 *
	 * @param \WP_Post $attachment The attachment object.
	 *
	 * @return bool True if the attachment is an audio, false otherwise.
	 */
	private function is_mime_type_audio( $attachment ) {
		return strpos( $attachment->post_mime_type, 'audio/' ) !== false;
	}

	/**
	 * Retrieves attachment schema data.
	 *
	 * @param string   $type The schema type.
	 * @param \WP_Post $attachment The attachment object.
	 * @param string   $attachment_url The attachment URL.
	 *
	 * @return array The attachment schema data.
	 */
	private function get_attachment_schema( $type, $attachment, $attachment_url ) {
		$description = $attachment->post_excerpt
			? $attachment->post_excerpt
			: $attachment->post_content;

		return array(
			'@type'       => $type,
			'name'        => $attachment->post_title,
			'description' => wp_strip_all_tags( $description ),
			'uploadDate'  => $attachment->post_date,
			'contentUrl'  => $attachment_url,
		);
	}

	/**
	 * Adds schema data.
	 *
	 * @param array $schema The schema data.
	 *
	 * @return void
	 */
	private function add_schema( $schema ) {
		$this->schema[] = $schema;
	}

	/**
	 * Checks if audio is enabled.
	 *
	 * @return bool True if audio is enabled, false otherwise.
	 */
	private function is_audio_enabled() {
		return (bool) $this->utils->get_schema_option( 'schema_enable_audio' );
	}

	/**
	 * Checks if video is enabled.
	 *
	 * @return bool True if video is enabled, false otherwise.
	 */
	private function is_video_enabled() {
		return (bool) $this->utils->get_schema_option( 'schema_enable_video' );
	}
}