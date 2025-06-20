<?php
/**
 * Utils class for handling various utility functions in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * Class Utils
 *
 * Provides utility functions for schema generation.
 */
class Utils {

	use Singleton;

	/**
	 * Social options array.
	 *
	 * @var array
	 */
	private $social_options;

	/**
	 * Schema options array.
	 *
	 * @var array
	 */
	private $schema_options;

	/**
	 * Converts a URL to an ID.
	 *
	 * @param string $url The URL to convert.
	 * @param string $id The ID to append.
	 * @return string The converted URL with ID.
	 */
	public function url_to_id( $url, $id ) {
		/**
		 * Rewrite.
		 */
		global $wp_rewrite;
		if ( $wp_rewrite->using_permalinks() ) {
			$url = trailingslashit( $url );
		}

		return $url . $id;
	}

	/**
	 * Retrieves a schema option by key.
	 *
	 * @param string $key The key of the schema option.
	 * @return mixed|string|null The value of the schema option.
	 */
	public function get_schema_option( $key ) {
		$value = \smartcrawl_get_array_value( $this->get_schema_options(), $key );
		if ( is_string( $value ) ) {
			return sanitize_text_field( trim( $value ) );
		}

		return $value;
	}

	/**
	 * Retrieves all schema options.
	 *
	 * @return array The schema options array.
	 */
	private function get_schema_options() {
		if ( empty( $this->schema_options ) ) {
			$schema               = Settings::get_component_options( Settings::COMP_SCHEMA );
			$this->schema_options = is_array( $schema ) ? $schema : array();
		}

		return $this->schema_options;
	}

	/**
	 * Retrieves all social options.
	 *
	 * @return array The social options array.
	 */
	public function get_social_options() {
		if ( empty( $this->social_options ) ) {
			$social               = Settings::get_component_options( Settings::COMP_SOCIAL );
			$this->social_options = is_array( $social ) ? $social : array();
		}

		return $this->social_options;
	}

	/**
	 * Retrieves a social option by key.
	 *
	 * @param string $key The key of the social option.
	 * @return mixed|string|null The value of the social option.
	 */
	public function get_social_option( $key ) {
		$value = \smartcrawl_get_array_value( $this->get_social_options(), $key );
		if ( is_string( $value ) ) {
			return sanitize_text_field( trim( $value ) );
		}

		return $value;
	}

	/**
	 * Retrieves the schema for a media item image.
	 *
	 * @param int    $media_item_id The media item ID.
	 * @param string $schema_id The schema ID.
	 *
	 * @return array The media item image schema.
	 */
	public function get_media_item_image_schema( $media_item_id, $schema_id ) {
		if ( ! $media_item_id ) {
			return array();
		}

		$media_item = $this->get_attachment_image_source( $media_item_id );
		if ( ! $media_item ) {
			return array();
		}

		return $this->get_image_schema(
			$schema_id,
			$media_item[0],
			$media_item[1],
			$media_item[2],
			wp_get_attachment_caption( $media_item_id )
		);
	}

	/**
	 * Retrieves the attachment image source.
	 *
	 * @param int $media_item_id The media item ID.
	 * @return array|false The attachment image source array or false on failure.
	 */
	public function get_attachment_image_source( $media_item_id ) {
		$media_item = wp_get_attachment_image_src( $media_item_id, 'full' );
		if ( ! $media_item || count( $media_item ) < 3 ) {
			return false;
		}

		return $media_item;
	}

	/**
	 * Retrieves the image schema.
	 *
	 * @param string $id The schema ID.
	 * @param string $url The image URL.
	 * @param string $width The image width.
	 * @param string $height The image height.
	 * @param string $caption The image caption.
	 * @return array The image schema array.
	 */
	public function get_image_schema( $id, $url, $width = '', $height = '', $caption = '' ) {
		$image_schema = array(
			'@type' => 'ImageObject',
			'@id'   => $id,
			'url'   => $url,
		);

		if ( $height ) {
			$image_schema['height'] = $height;
		}

		if ( $width ) {
			$image_schema['width'] = $width;
		}

		if ( $caption ) {
			$image_schema['caption'] = $caption;
		}

		return $image_schema;
	}

	/**
	 * Applies filters to a value.
	 *
	 * @param string $filter The filter name.
	 * @param mixed  ...$args The arguments to pass to the filter.
	 *
	 * @return mixed The filtered value.
	 */
	public function apply_filters( $filter, ...$args ) {
		return apply_filters( "wds-schema-$filter", ...$args ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
	}

	/**
	 * Resets the options.
	 *
	 * @return void
	 */
	public function reset_options() {
		$this->schema_options = array();
		$this->social_options = array();
	}

	/**
	 * Retrieves the webpage ID.
	 *
	 * @param string $url The URL of the webpage.
	 * @return string The webpage ID.
	 */
	public function get_webpage_id( $url ) {
		return $this->url_to_id( $url, '#schema-webpage' );
	}

	/**
	 * Retrieves the website ID.
	 *
	 * @return string The website ID.
	 */
	public function get_website_id() {
		return $this->url_to_id( get_site_url(), '#schema-website' );
	}

	/**
	 * Retrieves custom schema types.
	 *
	 * @param \WP_Post|null $post The post object.
	 * @param bool          $is_front_page Indicates if the current page is the front page.
	 *
	 * @return array The custom schema types array.
	 */
	public function get_custom_schema_types( $post = null, $is_front_page = false ) {
		$custom_types = array();
		$schema_types = \SmartCrawl\Schema\Types::get()->get_schema_types();
		foreach ( $schema_types as $schema_type ) {
			$type = \SmartCrawl\Schema\Types\Type::create( $schema_type, $post, $is_front_page );

			if ( $type->is_active() && $type->conditions_met() ) {
				$custom_types[ $type->get_type() ][] = $type->get_schema();
			}
		}

		return $custom_types;
	}

	/**
	 * Adds custom schema types to the schema.
	 * TODO: make sure webpage_id is passed where necessary
	 *
	 * @param array  $schema The schema array.
	 * @param array  $custom_types The custom types array.
	 * @param string $webpage_id The webpage ID.
	 *
	 * @return array The updated schema array.
	 */
	public function add_custom_schema_types( $schema, $custom_types, $webpage_id ) {
		foreach ( $custom_types as $type_key => $type_collection ) {
			if ( 'Article' === $type_key ) {
				// Article schemas will be handled separately.
				continue;
			}

			foreach ( $type_collection as $custom_type ) {
				$schema[] = $custom_type;
			}
		}

		$article_schemas = \smartcrawl_get_array_value( $custom_types, 'Article' );
		if ( ! empty( $article_schemas ) && is_array( $article_schemas ) ) {
			foreach ( $article_schemas as $article_schema ) {
				$article_schema['mainEntityOfPage'] = $webpage_id;

				$schema[] = $article_schema;
			}
		}

		return $schema;
	}

	/**
	 * Checks if the schema type is person.
	 *
	 * @return bool True if the schema type is person, false otherwise.
	 */
	public function is_schema_type_person() {
		return $this->get_social_option( 'schema_type' ) === 'Person';
	}

	/**
	 * Retrieves a special page by key.
	 *
	 * @param string $key The key of the special page.
	 * @return array|false|\WP_Post The special page object or false on failure.
	 */
	public function get_special_page( $key ) {
		$page_id = (int) $this->get_schema_option( $key );
		if ( ! $page_id ) {
			return false;
		}

		$special_page = get_post( $page_id );
		if ( ! $special_page || is_wp_error( $special_page ) ) {
			return false;
		}

		return $special_page;
	}

	/**
	 * Retrieves the full name of a user.
	 *
	 * @param \WP_User $user The user object.
	 * @return mixed The full name of the user.
	 */
	public function get_user_full_name( $user ) {
		return $this->apply_filters( 'user-full_name', $user->get_full_name(), $user );
	}

	/**
	 * Retrieves the organization name.
	 *
	 * @return mixed|string|void The organization name.
	 */
	public function get_organization_name() {
		$organization_name = $this->get_social_option( 'organization_name' );

		return $organization_name
			? $organization_name
			: get_bloginfo( 'name' );
	}

	/**
	 * Retrieves the personal brand name.
	 *
	 * @return mixed|string The personal brand name.
	 */
	public function get_personal_brand_name() {
		return $this->first_non_empty_string(
			$this->get_schema_option( 'person_brand_name' ),
			$this->get_social_option( 'override_name' ),
			$this->get_user_full_name( \SmartCrawl\Models\User::owner() )
		);
	}

	/**
	 * Retrieves the first non-empty string from the arguments.
	 *
	 * @param mixed ...$args The arguments to check.
	 * @return mixed|string The first non-empty string.
	 */
	public function first_non_empty_string( ...$args ) {
		foreach ( $args as $arg ) {
			if ( ! empty( $arg ) ) {
				return $arg;
			}
		}

		return '';
	}

	/**
	 * Retrieves the organization description.
	 *
	 * @return mixed|string|void The organization description.
	 */
	public function get_organization_description() {
		$description = $this->get_textarea_schema_option( 'organization_description' );

		return $description ? $description : get_bloginfo( 'description' );
	}

	/**
	 * Retrieves a textarea schema option by key.
	 *
	 * @param string $key The key of the schema option.
	 * @return mixed|string|null The value of the schema option.
	 */
	public function get_textarea_schema_option( $key ) {
		$value = $this->get_schema_option( $key );
		if ( is_string( $value ) ) {
			return sanitize_textarea_field( trim( $value ) );
		}

		return $value;
	}

	/**
	 * Checks if the author gravatar is enabled.
	 *
	 * @return bool True if the author gravatar is enabled, false otherwise.
	 */
	public function is_author_gravatar_enabled() {
		return (bool) $this->get_schema_option( 'schema_enable_author_gravatar' );
	}

	/**
	 * Retrieves the contact point schema.
	 *
	 * @param string $phone The contact phone number.
	 * @param int    $contact_page_id The contact page ID.
	 * @param string $contact_type The contact type.
	 *
	 * @return array|string[] The contact point schema array.
	 */
	public function get_contact_point( $phone, $contact_page_id, $contact_type = '' ) {
		$schema = array();
		if ( $phone ) {
			$schema['telephone'] = $phone;
		}

		if ( $contact_page_id ) {
			$contact_page_url = get_permalink( $contact_page_id );
			if ( $contact_page_url ) {
				$schema['url'] = $contact_page_url;
			}
		}

		if ( $schema ) {
			$other_values = array( '@type' => 'ContactPoint' );
			if ( $contact_type ) {
				$other_values['contactType'] = $contact_type;
			}
			$schema = $other_values + $schema;
		}

		return $schema;
	}

	/**
	 * Retrieves the social URLs.
	 *
	 * @return array The social URLs array.
	 */
	public function get_social_urls() {
		$urls   = array();
		$social = $this->get_social_options();
		foreach ( $social as $key => $value ) {
			if ( preg_match( '/_url$/', $key ) && ! empty( trim( $value ) ) ) {
				$urls[] = $this->get_social_option( $key );
			}
		}

		$twitter_username = $this->get_social_option( 'twitter_username' );
		if ( $twitter_username ) {
			$urls[] = "https://twitter.com/$twitter_username";
		}

		return $urls;
	}

	/**
	 * Retrieves the current URL.
	 *
	 * @since 3.5.0
	 *
	 * @return string The current URL.
	 */
	public function get_current_url() {
		global $wp;

		return add_query_arg( $wp->query_vars, home_url( $wp->request ) );
	}
}