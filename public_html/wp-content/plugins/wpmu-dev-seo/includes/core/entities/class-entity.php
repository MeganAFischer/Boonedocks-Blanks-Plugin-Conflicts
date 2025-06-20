<?php
/**
 * Abstract class for Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

use SmartCrawl\Settings;

/**
 * Entity Class.
 */
abstract class Entity {
	const NOINDEX_KEY_FORMAT          = 'meta_robots-noindex-%s';
	const NOFOLLOW_KEY_FORMAT         = 'meta_robots-nofollow-%s';
	const SUBSEQUENT_PAGES_KEY_FORMAT = 'meta_robots-%s-subsequent_pages';

	/**
	 * Meta title.
	 *
	 * @var string
	 */
	private $meta_title;
	/**
	 * Meta description.
	 *
	 * @var string
	 */
	private $meta_description;
	/**
	 * Robots meta tag.
	 *
	 * @var string
	 */
	private $robots;
	/**
	 * Canonical URL.
	 *
	 * @var string
	 */
	private $canonical_url;
	/**
	 * Schema array.
	 *
	 * @var array
	 */
	private $schema;
	/**
	 * OpenGraph tags.
	 *
	 * @var array
	 */
	private $opengraph_tags;
	/**
	 * Is OpenGraph Enabled.
	 *
	 * @var bool
	 */
	private $opengraph_enabled;
	/**
	 * OpenGraph title.
	 *
	 * @var string
	 */
	private $opengraph_title;
	/**
	 * OpenGraph description.
	 *
	 * @var string
	 */
	private $opengraph_description;
	/**
	 * OpenGraph images.
	 *
	 * @var array
	 */
	private $opengraph_images;
	/**
	 * Is Twitter enabled.
	 *
	 * @var bool
	 */
	private $twitter_enabled;
	/**
	 * Twitter title.
	 *
	 * @var string
	 */
	private $twitter_title;
	/**
	 * Twitter description.
	 *
	 * @var string
	 */
	private $twitter_description;
	/**
	 * Twitter images.
	 *
	 * @var array
	 */
	private $twitter_images;

	/**
	 * Retrieves the meta title.
	 *
	 * @return string The meta title.
	 */
	public function get_meta_title() {
		if ( is_null( $this->meta_title ) ) {
			$this->meta_title = $this->load_meta_title();
		}

		$filtered_value = apply_filters_deprecated(
			'wds_title',
			array( $this->meta_title ),
			'6.6.1',
			'smartcrawl_get_meta_title',
			__( 'Please use our new filter `smartcrawl_get_meta_title` in SmartCrawl.', 'wds' )
		);

		return apply_filters( 'smartcrawl_get_meta_title', $filtered_value );
	}

	/**
	 * Abstract method to load the meta title.
	 *
	 * @return void
	 */
	abstract protected function load_meta_title();

	/**
	 * Retrieves the meta description.
	 *
	 * @return string The meta description.
	 */
	public function get_meta_description() {
		if ( is_null( $this->meta_description ) ) {
			$this->meta_description = $this->load_meta_description();
		}

		$filtered_value = apply_filters_deprecated(
			'wds_metadesc',
			array( $this->meta_description ),
			'6.6.1',
			'smartcrawl_get_meta_description',
			__( 'Please use our new filter `smartcrawl_get_meta_description` in SmartCrawl.', 'wds' )
		);

		return apply_filters( 'smartcrawl_get_meta_description', $filtered_value );
	}

	/**
	 * Abstract method to load the meta description.
	 *
	 * @return void
	 */
	abstract protected function load_meta_description();

	/**
	 * Retrieves the value of the robots meta tag.
	 *
	 * If the robots meta tag value is not set, it will call the `load_robots` method to load the value.
	 * The loaded value will be stored in the `$robots` property for future use.
	 *
	 * @return string The value of the robots meta tag.
	 */
	public function get_robots() {
		if ( is_null( $this->robots ) ) {
			$this->robots = $this->load_robots();
		}

		return $this->robots;
	}

	/**
	 * Abstract method to load robots meta tag.
	 *
	 * @return string
	 */
	abstract protected function load_robots();

	/**
	 * Retrieves the canonical URL.
	 *
	 * If the canonical URL is not yet loaded, it calls the load_canonical_url method
	 * to load and store the canonical URL before returning it.
	 *
	 * @return string The canonical URL.
	 */
	public function get_canonical_url() {
		if ( is_null( $this->canonical_url ) ) {
			$this->canonical_url = $this->load_canonical_url();
		}

		return $this->canonical_url;
	}

	/**
	 * Abstract method to load the canonical URL.
	 *
	 * @return string
	 */
	abstract protected function load_canonical_url();

	/**
	 * Retrieves the schema.
	 *
	 * If the schema is null, it loads the schema using the load_schema method,
	 * then returns the loaded schema.
	 *
	 * @return array The loaded schema.
	 */
	public function get_schema() {
		if ( is_null( $this->schema ) ) {
			$this->schema = $this->load_schema();
		}

		return $this->schema;
	}

	/**
	 * Abstract method to load the schema.
	 *
	 * @return array
	 */
	abstract protected function load_schema();

	/**
	 * Retrieves the OpenGraph tags.
	 *
	 * If the OpenGraph tags have not been loaded yet,
	 * loads the OpenGraph tags.
	 *
	 * @return array The OpenGraph tags.
	 */
	public function get_opengraph_tags() {
		if ( is_null( $this->opengraph_tags ) ) {
			$this->opengraph_tags = $this->load_opengraph_tags();
		}

		return $this->opengraph_tags;
	}

	/**
	 * Loads the OpenGraph tags.
	 *
	 * @return array The OpenGraph tags.
	 */
	protected function load_opengraph_tags() {
		$tags = array(
			'og:type'        => 'object',
			'og:url'         => $this->get_canonical_url(),
			'og:title'       => $this->get_opengraph_title(),
			'og:description' => $this->get_opengraph_description(),
		);

		return $this->add_opengraph_image_tags( $tags );
	}

	/**
	 * Adds OpenGraph image tags to the given tags array.
	 *
	 * @param array $tags The current OpenGraph tags.
	 *
	 * @return array The updated OpenGraph tags with image tags.
	 */
	private function add_opengraph_image_tags( $tags ) {
		$included_urls = array();
		$images        = $this->get_opengraph_images();
		$images        = ! empty( $images ) && is_array( $images )
			? $images
			: array();

		foreach ( $images as $image ) {
			$url    = \smartcrawl_get_array_value( $image, 0 );
			$width  = \smartcrawl_get_array_value( $image, 1 );
			$height = \smartcrawl_get_array_value( $image, 2 );

			if ( ! $width || ! $height ) {
				$attachment = \smartcrawl_get_attachment_by_url( trim( $url ) );
				if ( $attachment ) {
					$width  = $attachment['width'];
					$height = $attachment['height'];
				}
			}

			if ( array_search( $url, $included_urls, true ) !== false ) {
				continue;
			}

			$image_tags = array();
			if ( $url ) {
				$image_tags['og:image'] = $url;
			}
			if ( $width ) {
				$image_tags['og:image:width'] = $width;
			}
			if ( $height ) {
				$image_tags['og:image:height'] = $height;
			}

			if ( $image_tags ) {
				$tags[] = $image_tags;
			}
		}

		return $tags;
	}

	/**
	 * Checks if the opengraph is enabled.
	 *
	 * If the opengraph is not loaded yet, it calls the `load_opengraph_enabled` method
	 * to load the opengraph enabled value and stores it in the `opengraph_enabled` property.
	 *
	 * @return bool Returns true if opengraph is enabled, false otherwise.
	 */
	public function is_opengraph_enabled() {
		if ( is_null( $this->opengraph_enabled ) ) {
			$this->opengraph_enabled = $this->load_opengraph_enabled();
		}

		return $this->opengraph_enabled;
	}

	/**
	 * Abstract method to load the Opengraph enabled status.
	 *
	 * @return bool
	 */
	abstract protected function load_opengraph_enabled();

	/**
	 * Retrieves the OpenGraph title.
	 *
	 * This method checks if $opengraph_title property is null and loads it using the `load_opengraph_title` method if necessary.
	 * Once the $opengraph_title property is set, it returns the filtered value of $opengraph_title.
	 *
	 * @return string
	 */
	public function get_opengraph_title() {
		if ( is_null( $this->opengraph_title ) ) {
			$this->opengraph_title = $this->load_opengraph_title();
		}

		$filtered_value = apply_filters_deprecated(
			'wds_custom_og_title',
			array( $this->opengraph_title ),
			'6.6.1',
			'smartcrawl_get_opengraph_title',
			__( 'Please use our new filter `smartcrawl_get_opengraph_title` in SmartCrawl.', 'wds' )
		);

		return apply_filters( 'smartcrawl_get_opengraph_title', $filtered_value );
	}

	/**
	 * Abstract method to load the OpenGraph title.
	 *
	 * @return string
	 */
	abstract protected function load_opengraph_title();

	/**
	 * Returns the OpenGraph description.
	 *
	 * If the OpenGraph description is null, it loads the description by calling the `load_opengraph_description` method.
	 * The loaded description is then filtered before returning it.
	 *
	 * @return string The filtered OpenGraph description.
	 */
	public function get_opengraph_description() {
		if ( is_null( $this->opengraph_description ) ) {
			$this->opengraph_description = $this->load_opengraph_description();
		}

		$filtered_value = apply_filters_deprecated(
			'wds_custom_og_description',
			array( $this->opengraph_description ),
			'6.6.1',
			'smartcrawl_get_opengraph_description',
			__( 'Please use our new filter `smartcrawl_get_opengraph_description` in SmartCrawl.', 'wds' )
		);

		return apply_filters( 'smartcrawl_get_opengraph_description', $filtered_value );
	}

	/**
	 * Abstract method to load the OpenGraph description.
	 *
	 * This is an abstract method that must be implemented by child classes.
	 * It is responsible for loading and returning the OpenGraph description.
	 * The implementation details for loading the description will vary depending on the specific use case.
	 *
	 * @return string The loaded OpenGraph description.
	 */
	abstract protected function load_opengraph_description();

	/**
	 * Retrieves the OpenGraph images.
	 *
	 * If the OpenGraph images are null, it loads the images by calling the `load_opengraph_images` method.
	 * The loaded images are then filtered before returning them.
	 *
	 * @return array An array of filtered OpenGraph images.
	 */
	public function get_opengraph_images() {
		if ( is_null( $this->opengraph_images ) ) {
			$this->opengraph_images = $this->load_opengraph_images();
		}

		$filtered_value = apply_filters_deprecated(
			'wds_custom_og_image',
			array( $this->opengraph_images ),
			'6.6.1',
			'smartcrawl_get_opengraph_images',
			__( 'Please use our new filter `smartcrawl_get_opengraph_images` in SmartCrawl.', 'wds' )
		);

		return apply_filters( 'smartcrawl_get_opengraph_images', $filtered_value );
	}

	/**
	 * Abstract method to load the OpenGraph images.
	 *
	 * This is an abstract method that must be implemented by child classes.
	 * It is responsible for loading the OpenGraph images.
	 *
	 * @return array An array of OpenGraph images.
	 */
	abstract protected function load_opengraph_images();

	/**
	 * Checks if Twitter is enabled.
	 *
	 * If the `twitter_enabled` property is null, it loads the Twitter enabled status by calling the `load_twitter_enabled` method.
	 *
	 * @return bool The Twitter enabled status.
	 */
	public function is_twitter_enabled() {
		if ( is_null( $this->twitter_enabled ) ) {
			$this->twitter_enabled = $this->load_twitter_enabled();
		}

		return $this->twitter_enabled;
	}

	/**
	 * Abstract method to load Twitter enabled status.
	 *
	 * This method is implemented in child classes to determine if Twitter is enabled or not.
	 * It should return a boolean value indicating whether Twitter is enabled or not.
	 *
	 * @return bool True if Twitter is enabled, false otherwise.
	 */
	abstract protected function load_twitter_enabled();

	/**
	 * Retrieves the Twitter title.
	 *
	 * If the Twitter title is null, it loads the title by calling the `load_twitter_title` method.
	 * The loaded title is then filtered before returning it.
	 *
	 * @return string The filtered Twitter title.
	 */
	public function get_twitter_title() {
		if ( is_null( $this->twitter_title ) ) {
			$this->twitter_title = $this->load_twitter_title();
		}

		$filtered_value = apply_filters_deprecated(
			'wds_custom_twitter_title',
			array( $this->twitter_title ),
			'6.6.1',
			'smartcrawl_get_twitter_title',
			__( 'Please use our new filter `smartcrawl_get_twitter_title` in SmartCrawl.', 'wds' )
		);

		return apply_filters( 'smartcrawl_get_twitter_title', $filtered_value );
	}

	/**
	 * Abstract method to load the Twitter title.
	 *
	 * This method is implemented by child classes.
	 * It should be used to retrieve the Twitter title of the entity.
	 *
	 * @return string The Twitter title.
	 */
	abstract protected function load_twitter_title();

	/**
	 * Retrieves the Twitter description.
	 *
	 * If the Twitter description is null, it loads the description by calling the `load_twitter_description` method.
	 * The loaded description is then filtered before returning it.
	 *
	 * @return string The filtered Twitter description.
	 */
	public function get_twitter_description() {
		if ( is_null( $this->twitter_description ) ) {
			$this->twitter_description = $this->load_twitter_description();
		}

		$filtered_value = apply_filters_deprecated(
			'wds_custom_twitter_description',
			array( $this->twitter_description ),
			'6.6.1',
			'smartcrawl_get_twitter_description',
			__( 'Please use our new filter `smartcrawl_get_twitter_description` in SmartCrawl.', 'wds' )
		);

		return apply_filters( 'smartcrawl_get_twitter_description', $filtered_value );
	}

	/**
	 * Abstract method to load the Twitter description.
	 *
	 * This method should be implemented in a subclass to load the Twitter description.
	 * It should return the loaded description.
	 *
	 * @return string The loaded Twitter description.
	 */
	abstract protected function load_twitter_description();

	/**
	 * Retrieves the Twitter images.
	 *
	 * If the Twitter images are null, it loads the images by calling the `load_twitter_images` method.
	 * The loaded images are filtered before returning them.
	 *
	 * @return array The Twitter images.
	 */
	public function get_twitter_images() {
		if ( is_null( $this->twitter_images ) ) {
			$this->twitter_images = $this->load_twitter_images();
		}

		$filtered_value = apply_filters_deprecated(
			'wds_custom_twitter_image',
			array( $this->twitter_images ),
			'6.6.1',
			'smartcrawl_get_twitter_images',
			__( 'Please use our new filter `smartcrawl_get_twitter_images` in SmartCrawl.', 'wds' )
		);

		return apply_filters( 'smartcrawl_get_twitter_images', $filtered_value );
	}

	/**
	 * Abstract method to load the Twitter images.
	 *
	 * This method must be implemented by the extending class.
	 * It should handle the logic for loading the Twitter images.
	 *
	 * @return array The loaded Twitter images.
	 */
	abstract protected function load_twitter_images();

	/**
	 * Abstract method to retrieve the macros for the given subject.
	 *
	 * This method should be implemented by the extending class.
	 *
	 * @param string $subject The subject for which to retrieve the macros.
	 *
	 * @return array The macros for the given subject.
	 */
	abstract public function get_macros( $subject = '' );

	/**
	 * Loads the meta title from the onpage options based on the provided location.
	 *
	 * The function retrieves the meta title from the onpage options by calling the `get_onpage_option` method and concatenating the provided location.
	 *
	 * @param string $location The location to fetch the meta title for.
	 *
	 * @return string The meta title for the provided location.
	 */
	protected function load_meta_title_from_options( $location ) {
		return $this->get_onpage_option( 'title-' . $location );
	}

	/**
	 * Loads the meta description from the on-page options based on the given location.
	 *
	 * @param string $location The location to retrieve the meta description for.
	 *
	 * @return string The meta description for the given location.
	 */
	protected function load_meta_desc_from_options( $location ) {
		return $this->get_onpage_option( 'metadesc-' . $location );
	}

	/**
	 * Checks if OpenGraph is enabled for the specified location.
	 *
	 * It retrieves the on-page option for OpenGraph activation based on the provided location.
	 *
	 * @param string $location The location identifier.
	 *
	 * @return bool Whether OpenGraph is enabled for the specified location or not.
	 */
	protected function is_opengraph_enabled_for_location( $location ) {
		return $this->get_onpage_option( 'og-active-' . $location );
	}

	/**
	 * Loads the OpenGraph title from the on-page options.
	 *
	 * It retrieves the OpenGraph title value based on the specified location.
	 * The location is used to construct the key used to fetch the value from the on-page options.
	 *
	 * @param string $location The location of the OpenGraph title.
	 *
	 * @return string|null The OpenGraph title from the on-page options or null if not found.
	 */
	protected function load_opengraph_title_from_options( $location ) {
		return $this->get_onpage_option( 'og-title-' . $location );
	}

	/**
	 * Loads the OpenGraph description from on-page options.
	 *
	 * Retrieves the OpenGraph description for a specific location from the onpage options.
	 *
	 * @param string $location The location identifier for the OpenGraph description.
	 *
	 * @return string|null The OpenGraph description for the specified location.
	 */
	protected function load_opengraph_description_from_options( $location ) {
		return $this->get_onpage_option( 'og-description-' . $location );
	}

	/**
	 * Loads OpenGraph images from options based on the location.
	 *
	 * @param string $location The location parameter.
	 *
	 * @return array The filtered images.
	 */
	protected function load_opengraph_images_from_options( $location ) {
		$images = $this->get_onpage_option( 'og-images-' . $location );

		if ( empty( $images ) || ! is_array( $images ) ) {
			return array();
		}

		return array_filter(
			$images,
			function ( $image ) {
				return wp_get_attachment_image_src( $image );
			}
		);
	}

	/**
	 * Checks if Twitter is enabled for a specific location.
	 *
	 * @param string $location The location parameter.
	 *
	 * @return bool True if Twitter is enabled, false otherwise.
	 */
	protected function is_twitter_enabled_for_location( $location ) {
		return $this->get_onpage_option( 'twitter-active-' . $location );
	}

	/**
	 * Loads Twitter title from on-page options based on the location.
	 *
	 * @param string $location The location parameter.
	 *
	 * @return string The Twitter title.
	 */
	protected function load_twitter_title_from_options( $location ) {
		return $this->get_onpage_option( 'twitter-title-' . $location );
	}

	/**
	 * Loads Twitter description from on-page options based on the location.
	 *
	 * @param string $location The location parameter.
	 *
	 * @return string The Twitter description.
	 */
	protected function load_twitter_description_from_options( $location ) {
		return $this->get_onpage_option( 'twitter-description-' . $location );
	}

	/**
	 * Loads Twitter images from on-page options based on the location.
	 *
	 * @param string $location The location parameter.
	 *
	 * @return array The filtered images.
	 */
	protected function load_twitter_images_from_options( $location ) {
		$images = $this->get_onpage_option( 'twitter-images-' . $location );

		if ( empty( $images ) || ! is_array( $images ) ) {
			return array();
		}

		return array_filter(
			$images,
			function ( $image ) {
				return wp_get_attachment_image_src( $image );
			}
		);
	}

	/**
	 * Retrieves an on-page option value based on the given key.
	 *
	 * @param string $key The key of the option value.
	 *
	 * @return mixed|null The option value or null if it doesn't exist.
	 */
	protected function get_onpage_option( $key ) {
		$options = $this->get_onpage_options();

		return \smartcrawl_get_array_value( $options, $key );
	}

	/**
	 * Sanitizes a string by trimming, stripping tags, and normalizing whitespace.
	 *
	 * @param mixed $string The input string to sanitize.
	 *
	 * @return string The sanitized string.
	 */
	protected function sanitize_string( $string ) {
		if ( ! is_string( $string ) ) {
			return '';
		}

		$sanitization_functions = array( 'trim', 'wp_strip_all_tags', '\smartcrawl_normalize_whitespace' );

		foreach ( $sanitization_functions as $function ) {
			$string = call_user_func( $function, $string );

			if ( empty( $string ) ) {
				return '';
			}
		}

		return $string;
	}

	/**
	 * Checks if the robots meta tag has "noindex".
	 *
	 * @return bool
	 */
	public function is_noindex() {
		return strpos( $this->get_robots(), 'noindex' ) !== false;
	}

	/**
	 * Checks if the robots meta tag has "nofollow".
	 *
	 * @return bool
	 */
	public function is_nofollow() {
		return strpos( $this->get_robots(), 'nofollow' ) !== false;
	}

	/**
	 * Retrieves the noindex setting for a specific type.
	 *
	 * @param string $type Entity type.
	 *
	 * @return bool The noindex setting value.
	 */
	protected function get_noindex_setting( $type ) {
		$options = $this->get_onpage_options();

		return (bool) \smartcrawl_get_array_value(
			$options,
			sprintf( self::NOINDEX_KEY_FORMAT, $type )
		);
	}

	/**
	 * Retrieves the nofollow setting for a specific type.
	 *
	 * @param string $type Entity type.
	 *
	 * @return bool The nofollow setting for the specified place.
	 */
	protected function get_nofollow_setting( $type ) {
		$options = $this->get_onpage_options();

		return (bool) \smartcrawl_get_array_value(
			$options,
			sprintf( self::NOFOLLOW_KEY_FORMAT, $type )
		);
	}

	/**
	 * Applies macros to a given subject.
	 *
	 * @param string $subject The subject string.
	 * @param string $module The module parameter. Default is 'general'.
	 *
	 * @return string The subject string with macros applied.
	 */
	public function apply_macros( $subject, $module = 'general' ) {
		if ( strpos( $subject, '%%' ) === false ) {
			return $subject;
		}

		$macros = array_merge(
			$this->get_general_macros(),
			$this->get_macros( $subject )
		);

		$macro_keys = array_keys( $macros );

		$macro_keys = apply_filters_deprecated(
			'wds-known_macros-keys',
			array( $macro_keys ),
			'6.4.2',
			'smartcrawl_known_macros_keys',
			__( 'Please use our new filter `smartcrawl_known_macros_keys` in SmartCrawl.', 'wds' )
		);

		$macro_keys = apply_filters( 'smartcrawl_known_macros_keys', $macro_keys );

		$macro_values = array_values( $macros );

		$macro_values = apply_filters_deprecated(
			'wds-known_macros-values',
			array( $macro_values ),
			'6.4.2',
			'smartcrawl_known_macros_values',
			__( 'Please use our new filter `smartcrawl_known_macros_values` in SmartCrawl.', 'wds' )
		);

		$macro_values = apply_filters( 'smartcrawl_known_macros_values', $macro_values );

		$macros = array_combine( $macro_keys, $macro_values );

		$macros = apply_filters_deprecated(
			'wds-known_macros',
			array( $macros, $module ),
			'smartcrawl_known_macros',
			__( 'Please use our new filter `smartcrawl_known_macros` in SmartCrawl.', 'wds' )
		);

		$macros = apply_filters(
			'smartcrawl_known_macros',
			$macros,
			$module
		);

		foreach ( $macros as $macro => $get_replacement ) {
			if ( strpos( $subject, $macro ) === false ) {
				continue;
			}

			$subject = str_replace(
				$macro,
				$this->resolve_macro( $macro, $get_replacement ),
				$subject
			);
		}

		return preg_replace( '/%%[a-zA-Z0-9!@#$^&*_+-=:;~]+%%/', '', $subject );
	}

	/**
	 * Retrieves resolved macros.
	 *
	 * @return array The resolved macros.
	 */
	public function get_resolved_macros() {
		$resolved = array();

		foreach ( $this->get_macros() as $macro => $get_replacement ) {
			$resolved[ $macro ] = $this->resolve_macro( $macro, $get_replacement );
		}

		return $resolved;
	}

	/**
	 * Resolves a macro by getting its replacement value.
	 *
	 * @param string   $macro The macro to resolve.
	 * @param callable $get_replacement The callback function to get the replacement value.
	 *
	 * @return string The processed macro replacement value.
	 */
	private function resolve_macro( $macro, $get_replacement ) {
		if ( is_callable( $get_replacement ) ) {
			$replacement = call_user_func( $get_replacement );
		} elseif ( is_scalar( $get_replacement ) ) {
			$replacement = $get_replacement;
		} else {
			$replacement = '';
		}

		$replacement = apply_filters_deprecated(
			'wds-macro-variable_replacement',
			array( $replacement, $macro ),
			'smartcrawl_replace_macro',
			__( 'Please use our new filter `smartcrawl_replace_macro` in SmartCrawl.', 'wds' )
		);

		$replacement = apply_filters( 'smartcrawl_replace_macro', $replacement, $macro );

		return $this->process_macro_replacement_value( $replacement );
	}

	/**
	 * Retrieves general macros.
	 *
	 * @return array The array containing the general macros.
	 */
	private function get_general_macros() {
		global $wp_query;
		$paged         = intval( $wp_query->get( 'paged' ) );
		$max_num_pages = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
		/* translators: 1: Current page number, 2: Total page number */
		$page_x_of_y = esc_html__( 'Page %1$s of %2$s', 'wds' );
		$options     = $this->get_onpage_options();
		$preset_sep  = ! empty( $options['preset-separator'] ) ? $options['preset-separator'] : 'pipe';
		$separator   = ! empty( $options['separator'] ) ? $options['separator'] : \smartcrawl_get_separators( $preset_sep );
		$pagenum     = $paged;

		if ( 0 === $pagenum ) {
			$pagenum = $max_num_pages > 1 ? 1 : '';
		}

		return array(
			'%%sitename%%'         => get_bloginfo( 'name' ),
			'%%sitedesc%%'         => get_bloginfo( 'description' ),
			'%%page%%'             => 0 !== $paged ? sprintf( $page_x_of_y, $paged, $max_num_pages ) : '',
			'%%spell_page%%'       => 0 !== $paged ? sprintf( $page_x_of_y, \smartcrawl_spell_number( $paged ), \smartcrawl_spell_number( $max_num_pages ) ) : '',
			'%%pagetotal%%'        => $max_num_pages > 1 ? $max_num_pages : '',
			'%%spell_pagetotal%%'  => $max_num_pages > 1 ? \smartcrawl_spell_number( $max_num_pages ) : '',
			'%%pagenumber%%'       => empty( $pagenum ) ? '' : $pagenum,
			'%%spell_pagenumber%%' => empty( $pagenum ) ? '' : \smartcrawl_spell_number( $pagenum ),
			'%%currenttime%%'      => date_i18n( get_option( 'time_format' ) ),
			'%%currentdate%%'      => date_i18n( get_option( 'date_format' ) ),
			'%%currentmonth%%'     => date_i18n( 'F' ),
			'%%currentyear%%'      => date_i18n( 'Y' ),
			'%%sep%%'              => $separator,
		);
	}

	/**
	 * Processes a macro replacement value.
	 *
	 * @param mixed $replacement The value to process.
	 *
	 * @return string The processed value.
	 */
	private function process_macro_replacement_value( $replacement ) {
		if ( '<' === $replacement ) {
			return $replacement;
		}

		if ( ! is_scalar( $replacement ) ) {
			return '';
		}

		return wp_strip_all_tags( $replacement );
	}

	/**
	 * Retrieves the on-page options.
	 *
	 * @return array The on-page options.
	 */
	private function get_onpage_options() {
		$onpage_options = Settings::get_component_options( Settings::COMP_ONPAGE );

		return empty( $onpage_options ) ? array() : $onpage_options;
	}

	/**
	 * Finds dynamic replacements in a subject string based on provided term and meta data.
	 *
	 * @param string   $subject The subject string to search in.
	 * @param callable $get_terms A callback function to retrieve term data.
	 * @param callable $get_meta A callback function to retrieve meta data.
	 *
	 * @return array The merged array of term description replacements, term name replacements, and meta replacements.
	 */
	protected function find_dynamic_replacements( $subject, $get_terms, $get_meta ) {
		$term_desc_replacements = $this->find_term_field_replacements( $subject, $get_terms, 'ct_desc_', 'description' );
		$subject                = str_replace( array_keys( $term_desc_replacements ), '', $subject );

		$term_name_replacements = $this->find_term_field_replacements( $subject, $get_terms, 'ct_', 'name' );
		$subject                = str_replace( array_keys( $term_name_replacements ), '', $subject );

		$meta_replacements = $this->find_meta_replacements( $subject, $get_meta );

		return array_merge( $term_desc_replacements, $term_name_replacements, $meta_replacements );
	}

	/**
	 * Finds term field replacements in a subject string.
	 *
	 * @param string   $subject The subject string to search in.
	 * @param callable $get_terms The function to get terms for a taxonomy.
	 * @param string   $prefix The prefix used in the placeholders.
	 * @param string   $term_field The term field to retrieve.
	 *
	 * @return array The array of replacements for the placeholders.
	 */
	private function find_term_field_replacements( $subject, $get_terms, $prefix, $term_field ) {
		$pattern      = "/(%%{$prefix}[a-zA-Z0-9!@#$^&*_+-=:;~]+%%)/";
		$matches      = array();
		$replacements = array();
		$match_result = preg_match_all( $pattern, $subject, $matches, PREG_PATTERN_ORDER );

		if ( ! empty( $match_result ) ) {
			$placeholders = array_shift( $matches );

			foreach ( array_unique( $placeholders ) as $placeholder ) {
				$taxonomy_name = str_replace( array( "%%$prefix", '%%' ), '', $placeholder );

				$replacements[ $placeholder ] = function () use ( $get_terms, $term_field, $taxonomy_name ) {
					$taxonomy = get_taxonomy( $taxonomy_name );
					if ( empty( $taxonomy ) ) {
						return '';
					}

					$terms = call_user_func( $get_terms, $taxonomy_name );
					if ( empty( $terms ) ) {
						return '';
					}
					$term = array_shift( $terms );

					return wp_strip_all_tags( get_term_field( $term_field, $term, $taxonomy_name ) );
				};
			}
		}

		return $replacements;
	}

	/**
	 * Find meta replacements in a subject string.
	 *
	 * @param string   $subject The subject string to search for meta replacements.
	 * @param callable $get_meta The function used to get the meta value.
	 *
	 * @return array The array of replacements.
	 */
	private function find_meta_replacements( $subject, $get_meta ) {
		$prefix       = 'cf_';
		$pattern      = "/(%%{$prefix}[a-zA-Z0-9!@#$^&*_+-=:;~]+%%)/";
		$matches      = array();
		$replacements = array();
		$match_result = preg_match_all( $pattern, $subject, $matches, PREG_PATTERN_ORDER );
		if ( ! empty( $match_result ) ) {
			$placeholders = array_shift( $matches );
			foreach ( array_unique( $placeholders ) as $placeholder ) {
				$meta_key = str_replace( array( "%%$prefix", '%%' ), '', $placeholder );

				$replacements[ $placeholder ] = function () use ( $get_meta, $meta_key ) {
					$meta_value = call_user_func( $get_meta, $meta_key );
					if ( empty( $meta_value ) || ! is_scalar( $meta_value ) ) {
						return '';
					}

					return wp_strip_all_tags( $meta_value );
				};

			}
		}

		return $replacements;
	}

	/**
	 * Loads a string value based on option key part, meta value and fallback.
	 *
	 * @param string   $option_key_part The option key part.
	 * @param callable $load_from_meta The callback to load value from meta.
	 * @param callable $load_from_options The callback to load value from options.
	 * @param callable $load_fallback Determines if fallback value should be loaded.
	 *
	 * @return string The loaded string value.
	 */
	protected function load_string_value( $option_key_part, $load_from_meta, $load_from_options, $load_fallback ) {
		if ( ! $option_key_part ) {
			return '';
		}

		// Checks if a meta value is available for this item.
		$from_meta = call_user_func( $load_from_meta );

		if ( $from_meta ) {
			$from_meta = $this->sanitize_string( $from_meta );

			if ( $from_meta ) {
				return $this->apply_macros( $from_meta );
			}
		}

		return $this->load_option_string_value( $option_key_part, $load_from_options, $load_fallback );
	}

	/**
	 * Loads an option value as a string.
	 *
	 * @param string   $option_key_part The key part of the option.
	 * @param callable $load_from_options A callback function to load the option from options.
	 * @param callable $load_fallback A callback function to load the fallback value.
	 *
	 * @return string The loaded option value as a string.
	 */
	protected function load_option_string_value( $option_key_part, $load_from_options, $load_fallback ) {
		if ( ! $option_key_part ) {
			return '';
		}

		// Check if an option is available.
		$from_options = call_user_func( $load_from_options, $option_key_part );
		if ( $from_options ) {
			$from_options = $this->sanitize_string( $from_options );
			if ( $from_options ) {
				return $this->apply_macros( $from_options );
			}
		}

		// Use fallback value.
		$fallback = call_user_func( $load_fallback );
		if ( $fallback ) {
			$fallback = $this->sanitize_string( $fallback );
			if ( $fallback ) {
				return $this->apply_macros( $fallback );
			}
		}

		return '';
	}

	/**
	 * Determines whether to show robots on subsequent pages only based on the location.
	 *
	 * @param string $location The location parameter.
	 *
	 * @return bool Whether to show robots on subsequent pages only.
	 */
	protected function show_robots_on_subsequent_pages_only( $location ) {
		return (bool) \smartcrawl_get_array_value(
			$this->get_onpage_options(),
			sprintf( self::SUBSEQUENT_PAGES_KEY_FORMAT, $location )
		);
	}

	/**
	 * Converts an array of image IDs to URLs.
	 *
	 * @param array $image_ids The image IDs.
	 *
	 * @return array The image URLs.
	 */
	protected function image_ids_to_urls( $image_ids ) {
		$image_ids = is_array( $image_ids ) || ! empty( $image_ids )
			? $image_ids
			: array();

		$images = array();
		foreach ( $image_ids as $image_id ) {
			$images = $this->image_id_to_url( $images, $image_id );
		}

		return $images;
	}

	/**
	 * Converts an image ID to URL and adds it to the array of images.
	 *
	 * @param array $images The array of images.
	 * @param mixed $image_id The image ID or URL.
	 *
	 * @return array The updated array of images.
	 */
	protected function image_id_to_url( $images, $image_id ) {
		if ( empty( $images ) || ! is_array( $images ) ) {
			$images = array();
		}

		if ( is_numeric( $image_id ) ) {
			$attachment     = wp_get_attachment_image_src( $image_id, 'full' );
			$attachment_url = \smartcrawl_get_array_value( $attachment, 0 );
			if ( $attachment_url ) {
				$images[ $attachment_url ] = $attachment;
			}
		} else {
			$images[ $image_id ] = array( $image_id );
		}

		return $images;
	}
}