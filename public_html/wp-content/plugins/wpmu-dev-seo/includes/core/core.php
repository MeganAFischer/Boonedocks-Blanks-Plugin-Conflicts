<?php
/**
 * Core helper functions
 *
 * Procedures smartcrawl_get_value(), smartcrawl_replace_vars(), smartcrawl_get_term_meta()
 * inspired by WordPress SEO by Joost de Valk (http://yoast.com/wordpress/seo/).
 *
 * @package SmartCrawl
 */

use SmartCrawl\Controllers\White_Label;

/**
 * Gets post meta value
 *
 * @param string $val     Key root to check.
 * @param int    $post_id Optional post ID.
 *
 * @return mixed
 */
function smartcrawl_get_value( $val, $post_id = false ) {
	if ( ! $post_id ) {
		global $post;
		$post_id = isset( $post ) ? $post->ID : false;
	}
	if ( ! $post_id ) {
		return false;
	}

	$meta_value = get_post_meta( $post_id, '_wds_' . $val, true );

	return empty( $meta_value ) ? false : $meta_value;
}

/**
 * Sets post meta value
 *
 * @param string $meta    Key root to check.
 * @param mixed  $val     Value to set.
 * @param int    $post_id Optional post ID.
 *
 * @return void
 */
function smartcrawl_set_value( $meta, $val, $post_id ) {
	update_post_meta( $post_id, "_wds_$meta", $val );
}

/**
 * Gets separator, or separators list
 *
 * @param string $key Optional separator key.
 *
 * @return string|array
 */
function smartcrawl_get_separators( $key = null ) {
	$separators = array(
		'dot'           => '·',
		'dot-l'         => '•',
		'dash'          => '-',
		'dash-l'        => '—',
		'pipe'          => '|',
		'forward-slash' => '/',
		'back-slash'    => '\\',
		'tilde'         => '~',
		'greater-than'  => '>',
		'less-than'     => '<',
		'caret-right'   => '›',
		'caret-left'    => '‹',
		'arrow-right'   => '→',
		'arrow-left'    => '←',
	);

	if ( null === $key || empty( $separators[ $key ] ) ) {
		return $separators;
	} else {
		return $separators[ $key ];
	}
}

/**
 * Returns the number as an anglicized string
 *
 * Adapted from original code by Hugh Bothwell (hugh_bothwell@hotmail.com)
 *
 * @param int $num Number to convert.
 *
 * @return string
 */
function smartcrawl_spell_number( $num ) {
	$num = (int) $num;    // make sure it's an integer.

	if ( $num < 0 ) {
		return 'negative' . _wds_hb_convert_tri( - $num, 0 );
	}
	if ( 0 === $num ) {
		return 'zero';
	}

	return _wds_hb_convert_tri( $num, 0 );
}

/**
 * Recursive fn, converts three digits per pass
 *
 * Adapted from original code by Hugh Bothwell (hugh_bothwell@hotmail.com)
 *
 * @param int $num Number to convert.
 * @param int $tri Triplet to check.
 *
 * @return string
 */
function _wds_hb_convert_tri( $num, $tri ) {
	$ones = array(
		'',
		' one',
		' two',
		' three',
		' four',
		' five',
		' six',
		' seven',
		' eight',
		' nine',
		' ten',
		' eleven',
		' twelve',
		' thirteen',
		' fourteen',
		' fifteen',
		' sixteen',
		' seventeen',
		' eighteen',
		' nineteen',
	);

	$tens = array(
		'',
		'',
		' twenty',
		' thirty',
		' forty',
		' fifty',
		' sixty',
		' seventy',
		' eighty',
		' ninety',
	);

	$triplets = array(
		'',
		' thousand',
		' million',
		' billion',
		' trillion',
		' quadrillion',
		' quintillion',
		' sextillion',
		' septillion',
		' octillion',
		' nonillion',
	);

	// chunk the number, ...rxyy.
	$r = (int) ( $num / 1000 );
	$x = (int) ( $num / 100 ) % 10;
	$y = $num % 100;

	// init the output string.
	$str = '';

	// do hundreds.
	if ( $x > 0 ) {
		$str = $ones[ $x ] . ' hundred';
	}

	// do ones and tens.
	if ( $y < 20 ) {
		$str .= $ones[ $y ];
	} else {
		$str .= $tens[ (int) ( $y / 10 ) ] . $ones[ $y % 10 ];
	}

	// add triplet modifier only if there is some output to be modified...
	if ( '' !== $str ) {
		$str .= $triplets[ $tri ];
	}

	// continue recursing?.
	if ( $r > 0 ) {
		return _wds_hb_convert_tri( $r, $tri + 1 ) . $str;
	} else {
		return $str;
	}
}

/**
 * Gets excerpt trimmed to length
 *
 * @param string $excerpt  Optional excerpt.
 * @param string $contents Contents.
 *
 * @return string
 */
function smartcrawl_get_trimmed_excerpt( $excerpt, $contents ) {
	$string = $excerpt ? $excerpt : $contents;

	// Check if we have the excerpt cached.
	$cache_key = 'wds-' . md5( $string );
	$cached    = wp_cache_get( $cache_key, 'smartcrawl' );
	if ( is_string( $cached ) ) {
		return $cached;
	}

	// Remove shortcodes but keep the content.
	$string = smartcrawl_remove_shortcodes( $string );
	// Strip all HTML tags.
	$string = wp_strip_all_tags( $string );
	// Encode any HTML entities like > and <.
	$string = esc_attr( $string );
	// Normalize whitespace.
	$string = smartcrawl_normalize_whitespace( $string );
	// Truncate length.
	$string = smartcrawl_truncate_meta_description( $string );

	wp_cache_set( $cache_key, $string, 'smartcrawl', 60 );

	return $string;
}

/**
 * Manage to normalize whitespaces in string.
 *
 * @param string $str - The value being managed.
 *
 * @return string
 */
function smartcrawl_normalize_whitespace( $str ) {
	// Replace whitespace characters with simple spaces.
	$str = str_replace( array( "\r", "\n", "\t" ), ' ', $str );
	// Replace each set of multiple consecutive spaces with a single space.
	$str = preg_replace( '/ +/', ' ', $str );

	return trim( $str );
}

/**
 * Removes the shortcode tags but keeps the content within them. Will convert [shortcode attr="val"]Some text![/shortcode] to: Some text!.
 *
 * @see get_shortcode_regex()
 *
 * @param string $content Content.
 */
function smartcrawl_remove_shortcodes( $content ) {
	if ( strpos( $content, '[' ) === false ) {
		return $content;
	}

	preg_match_all( '/\[([a-zA-Z0-9_-]+)/', $content, $shortcode_matches );
	if ( empty( $shortcode_matches[1] ) ) {
		return $content;
	}
	$shortcode_tags = array_values( array_unique( $shortcode_matches[1] ) );

	$pattern = get_shortcode_regex( $shortcode_tags );

	return preg_replace_callback( "/$pattern/s", '\smartcrawl_extract_shortcode_contents', $content );
}

/**
 * Our callback function for making get_shortcode_regex() replacements.
 *
 * @see get_shortcode_regex()
 *
 * @param array $matches This array contains data in the following format:
 *                       array(
 *                       0 => full matched string
 *                       1 => the character [ for escaped shortcodes e.g. [[foo]]
 *                       2 => the actual shortcode tag
 *                       3 => shortcode attributes
 *                       4 => ?
 *                       5 => The content nested inside the opening and closing shortcode tags
 *                       6 => the character ] for escaped shortcodes
 *                       );.
 *
 * @return bool|mixed|string
 */
function smartcrawl_extract_shortcode_contents( $matches ) {
	if ( empty( $matches ) || count( $matches ) < 7 ) {
		// Not the expected regex for some reason. Try returning the full match or fall back to empty string.
		return isset( $matches[0] ) ? $matches[0] : '';
	}

	// Allow [[foo]] syntax for escaping a tag.
	if ( '[' === $matches[1] && ']' === $matches[6] ) {
		// Return the whole matched string without the surrounding square brackets that were there for escaping.
		return substr( $matches[0], 1, - 1 );
	}

	$omitted = apply_filters( 'wds-omitted-shortcodes', array() );

	if (
		! empty( $matches[5] )
		&& ! in_array( $matches[2], $omitted, true )
	) {
		// Call the removal method on the content nested in the current shortcode.
		// This will continue recursively until we have removed all shortcodes.
		return smartcrawl_remove_shortcodes( trim( $matches[5] ) . ' ' );
	}

	// Just remove the content-less, non-escaped shortcodes.
	return '';
}

/**
 * Truncate meta title by title max length.
 *
 * @param string $str The value to be truncated.
 *
 * @return string
 */
function smartcrawl_truncate_meta_title( $str ) {
	return smartcrawl_truncate_meta( $str, smartcrawl_title_max_length() );
}

/**
 * Truncate meta description by title max length.
 *
 * @param string $str The value to be truncated.
 *
 * @return string
 */
function smartcrawl_truncate_meta_description( $str ) {
	return smartcrawl_truncate_meta( $str, smartcrawl_metadesc_max_length() );
}

/**
 * Handle to truncate meta title and description.
 *
 * @param string $str The value being truncated.
 * @param int    $limit Max length of string.
 *
 * @return string
 */
function smartcrawl_truncate_meta( $str, $limit ) {
	$pattern             = sprintf( '/.{%d,}/um', $limit + 1 );
	$replacement_pattern = sprintf( '/(.{0,%d}).*/um', $limit - 4 ); // -4 for 1 space plus ...

	return ( preg_match( $pattern, $str ) )
		? preg_replace( $replacement_pattern, '$1', $str ) . ' ...'
		: $str;
}

/**
 * Gets taxonomy term meta value
 *
 * @param object $term     Term object.
 * @param string $taxonomy Taxonomy the term belongs to.
 * @param string $meta_key Meta key to check.
 *
 * @return mixed
 */
function smartcrawl_get_term_meta( $term, $taxonomy, $meta_key ) {
	if ( is_numeric( $term ) || is_string( $term ) ) {
		$term = get_term_by(
			is_numeric( $term ) ? 'id' : 'slug',
			$term,
			$taxonomy
		);
		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}
	}

	$term_id    = $term->term_id;
	$tax_meta   = get_option( 'wds_taxonomy_meta' );
	$meta_value = smartcrawl_get_array_value( $tax_meta, array( $taxonomy, $term_id, $meta_key ) );

	return apply_filters( "wds-taxonomy-meta-$meta_key", $meta_value, $term_id, $taxonomy );
}

/**
 * Blog template settings handler
 *
 * @param string $and_clause Query gathered this far.
 *
 * @return string
 */
function smartcrawl_blog_template_settings( $and_clause ) {
	$and_clause .= " AND `option_name` != 'wds_sitemap_options'"; // Added singular.

	return $and_clause;
}

add_filter( 'blog_template_exclude_settings', 'smartcrawl_blog_template_settings' );


/**
 * Checks user persmission level against minumum requirement
 * for displaying SEO metabox.
 *
 * @return bool
 */
function user_can_see_seo_metabox() {
	$smartcrawl_options = SmartCrawl\Settings::get_options();
	$capability         = ( defined( 'SMARTCRAWL_SEO_METABOX_ROLE' ) && SMARTCRAWL_SEO_METABOX_ROLE )
		? SMARTCRAWL_SEO_METABOX_ROLE
		: ( ! empty( $smartcrawl_options['seo_metabox_permission_level'] ) ? $smartcrawl_options['seo_metabox_permission_level'] : false );
	$capability         = apply_filters( 'wds-capabilities-seo_metabox', $capability );
	$able               = false;

	if ( is_array( $capability ) ) {
		foreach ( $capability as $cap ) {
			$able = current_user_can( $cap );
			if ( $able ) {
				break;
			}
		}
	} else {
		$able = current_user_can( $capability );
	}

	return $able;
}

/**
 * Checks user persmission level against minumum requirement
 * for displaying Moz urlmetrics metabox.
 *
 * @return bool
 */
function user_can_see_urlmetrics_metabox() {
	$smartcrawl_options = SmartCrawl\Settings::get_options();
	$capability         = ( defined( 'SMARTCRAWL_URLMETRICS_METABOX_ROLE' ) && SMARTCRAWL_URLMETRICS_METABOX_ROLE )
		? SMARTCRAWL_URLMETRICS_METABOX_ROLE
		: ( ! empty( $smartcrawl_options['urlmetrics_metabox_permission_level'] ) ? $smartcrawl_options['urlmetrics_metabox_permission_level'] : false );
	$capability         = apply_filters( 'wds-capabilities-urlmetrics_metabox', $capability );
	$able               = false;

	if ( is_array( $capability ) ) {
		foreach ( $capability as $cap ) {
			$able = current_user_can( $cap );
			if ( $able ) {
				break;
			}
		}
	} else {
		$able = current_user_can( $capability );
	}

	return $able;
}

/**
 * Checks user persmission level against minumum requirement
 * for displaying the 301 redirection field within SEO metabox.
 *
 * @return bool
 */
function user_can_see_seo_metabox_301_redirect() {
	$smartcrawl_options = SmartCrawl\Settings::get_options();
	$capability         = ( defined( 'SMARTCRAWL_SEO_METABOX_301_ROLE' ) && SMARTCRAWL_SEO_METABOX_301_ROLE )
		? SMARTCRAWL_SEO_METABOX_301_ROLE
		: ( ! empty( $smartcrawl_options['seo_metabox_301_permission_level'] ) ? $smartcrawl_options['seo_metabox_301_permission_level'] : false );
	$capability         = apply_filters( 'wds-capabilities-seo_metabox_301_redirect', $capability );
	$able               = false;

	if ( is_array( $capability ) ) {
		foreach ( $capability as $cap ) {
			$able = current_user_can( $cap );
			if ( $able ) {
				break;
			}
		}
	} else {
		$able = current_user_can( $capability );
	}

	return $able;
}

/**
 * Attempt to hide metaboxes by default by adding them to "hidden" array.
 *
 * Metaboxes are still added to "Screen Options".
 * If user chooses to show/hide them, respect her decision.
 *
 * @param array $arg Whatever's been already hidden.
 *
 * @return array
 *
 * @deprecated 1.0.9
 * @noinspection PhpUnused
 */
function smartcrawl_process_default_hidden_meta_boxes( $arg ) {
	_deprecated_function( __FUNCTION__, '1.0.9' );

	$arg[] = 'wds-wds-meta-box';
	$arg[] = 'wds_seomoz_urlmetrics';

	return $arg;
}

/**
 * Hide ALL wds metaboxes.
 *
 * Respect wishes for other metaboxes.
 * Still accessble from "Screen Options".
 *
 * @param array $arg Whatever's been already hidden.
 *
 * @return array
 */
function smartcrawl_hide_metaboxes( $arg ) {
	// Hide WP defaults, if nothing else.
	if ( empty( $arg ) ) {
		$arg = array(
			'slugdiv',
			'trackbacksdiv',
			'postcustom',
			'postexcerpt',
			'commentstatusdiv',
			'commentsdiv',
			'authordiv',
			'revisionsdiv',
		);
	}
	$arg[] = 'wds-wds-meta-box';
	$arg[] = 'wds_seomoz_urlmetrics';

	return $arg;
}

/**
 * Register metabox hiding for other boxes.
 *
 * @deprecated 1.0.9
 * @noinspection PhpUnused
 */
function smartcrawl_register_metabox_hiding() {
	_deprecated_function( __FUNCTION__, '1.0.9' );

	$post_types = get_post_types();
	foreach ( $post_types as $type ) {
		add_filter( 'get_user_option_metaboxhidden_' . $type, 'smartcrawl_hide_metaboxes' );
	}
}

/**
 * Forces metaboxes to start collapsed.
 *
 * It properly merges the WDS boxes with the rest of the users collapsed boxes.
 * For info on registering, see `register_metabox_collapsed_state`.
 *
 * @param array $closed Whatever's been closed this far.
 *
 * @return array
 */
function force_metabox_collapsed_state( $closed ) {
	$closed = is_array( $closed ) ? $closed : array();

	return array_merge(
		$closed,
		array(
			'wds-wds-meta-box',
			'wds_seomoz_urlmetrics',
		)
	);
}

/**
 * Registers WDS boxes state.
 * Collapsed state is tracked per post type.
 * This is why we have this separate hook to register state change processing.
 */
function register_metabox_collapsed_state() {
	global $post;
	if ( $post && $post->post_type ) {
		add_filter( 'get_user_option_closedpostboxes_' . $post->post_type, 'force_metabox_collapsed_state' );
	}
}

add_filter( 'post_edit_form_tag', 'register_metabox_collapsed_state' );

/**
 * Checks if transient is stuck
 *
 * Stuck transient has no expiry time.
 * If so found, removes it.
 *
 * @param string $key Transient key.
 *
 * @return bool
 */
function smartcrawl_kill_stuck_transient( $key ) {
	global $_wp_using_ext_object_cache;
	if ( $_wp_using_ext_object_cache ) {
		return true;
	} // In object cache, nothing to do.

	$key        = "_transient_$key";
	$alloptions = wp_load_alloptions();
	// If option is in alloptions, it is autoloaded and thus has no timeout - kill it.
	if ( isset( $alloptions[ $key ] ) ) {
		return delete_option( $key );
	}

	return true;
}

/**
 * Check for boolean define switches and their values.
 *
 * @param string $switch_value Define name to check.
 *
 * @return bool
 */
function smartcrawl_is_switch_active( $switch_value ) {
	$result = defined( $switch_value ) ? constant( $switch_value ) : false;

	/**
	 * Checks if a define switch is toggled on
	 *
	 * Used in tests.
	 *
	 * @param bool   $result Whether the switch is turned on.
	 * @param string $switch_value Switch name.
	 *
	 * @return bool
	 */
	return (bool) apply_filters( 'smartcrawl_switch_active', $result, $switch_value );
}

/**
 * Check if we're on main BuddyPress site - BuddyPress root blog check.
 *
 * @return bool Are we on the main BuddyPress site?
 */
function smartcrawl_is_main_bp_site() {
	if ( is_multisite() && defined( 'BP_VERSION' ) && ( defined( 'BP_ROOT_BLOG' ) && BP_ROOT_BLOG ) ) {
		global $blog_id;

		return intval( BP_ROOT_BLOG ) === intval( $blog_id );
	}

	return is_main_site();
}

/**
 * Converts an argument map to HTML attributes string.
 *
 * @param array $args A hash of arguments.
 *
 * @return string Constructed attributes string
 */
function smartcrawl_autolinks_construct_attributes( $args = array() ) {
	$ret = array();

	if ( empty( $args ) ) {
		return '';
	}

	foreach ( $args as $key => $value ) {
		if ( empty( $key ) || empty( $value ) ) {
			continue; // Only accept properly formatted members.
		}
		$ret[] = esc_html( $key ) . '="' . esc_attr( $value ) . '"';
	}

	$attrs_str = trim( join( ' ', $ret ) );

	$attrs_str = apply_filters_deprecated(
		'wds_autolinks_attributes',
		array( $attrs_str ),
		'6.4.2',
		'smartcrawl_autolinks_attributes',
		__( 'Please use our new filter `smartcrawl_autolinks_attributes` in SmartCrawl.', 'wds' )
	);

	return apply_filters( 'smartcrawl_autolinks_attributes', $attrs_str );
}

/**
 * Get a value from an array. If nothing is found for the provided keys, returns null by default.
 *
 * @param array            $haystack The array to search (haystack).
 * @param array|string|int $key   The key to use for the search.
 * @param mixed            $default_value   The default value.
 *
 * @return mixed|null The array value found or null if nothing found.
 */
function smartcrawl_get_array_value( $haystack, $key, $default_value = null ) {
	if ( ! is_array( $key ) ) {
		$key = array( $key );
	}

	if ( ! is_array( $haystack ) ) {
		return $default_value;
	}

	$value = $haystack;
	foreach ( $key as $key_part ) {
		$value = isset( $value[ $key_part ] ) ? $value[ $key_part ] : $default_value;
	}

	return $value;
}

/**
 * Inserts a value in the given array.
 *
 * @param mixed        $value The value to insert.
 * @param array        $haystack The array in which the value is to be inserted. Passed by reference.
 * @param array|string $keys  Key specifying the place where the new value is to be inserted.
 *
 * @return void
 */
function smartcrawl_put_array_value( $value, &$haystack, $keys ) {
	if ( ! is_array( $keys ) ) {
		$keys = array( $keys );
	}

	$pointer = &$haystack;
	foreach ( $keys as $key ) {
		if ( ! isset( $pointer[ $key ] ) ) {
			$pointer         = empty( $pointer ) ? array() : $pointer;
			$pointer[ $key ] = array();
		}
		$pointer = &$pointer[ $key ];
	}
	$pointer = $value;
}

/**
 * Sanitizes a string into relative URL
 *
 * @param string $raw Raw string to process.
 *
 * @return string Root-relative string
 */
function smartcrawl_sanitize_relative_url( $raw ) {
	$raw = preg_match( '/^https?:\/\//', $raw ) || preg_match( '/^\//', $raw )
		? esc_url( $raw )
		: esc_url( "/$raw" );

	$parsed = wp_parse_url( $raw );
	$domain = preg_replace( '/^https?:\/\//', '', home_url() );

	if ( strpos( $raw, $domain ) !== false || empty( $parsed ) ) {
		$raw    = preg_replace( '/^https?:\/\//', '', $raw );
		$result = preg_replace( '/^' . preg_quote( $domain, '/' ) . '/', '', $raw );
	} else {
		$result = ! empty( $parsed['path'] ) ? $parsed['path'] : '/';
		if ( ! empty( $parsed['query'] ) ) {
			$result .= '?' . $parsed['query'];
		}
	}

	return '/' . ltrim( $result, '/' );
}

/**
 * Gets regex for matching against a list of relative URLs
 *
 * @param array $urls A list of relative URLs.
 *
 * @return string
 */
function smartcrawl_get_relative_urls_regex( $urls ) {
	$regex = '';
	if ( ! is_array( $urls ) ) {
		return $regex;
	}

	$processed = array();
	foreach ( $urls as $url ) {
		if ( empty( $url ) ) {
			continue;
		}
		$processed[] = preg_quote( $url, '/' );
	}

	return '/https?:\/\/.*?(' . join( '|', $processed ) . ')\/?$/';
}

/**
 * Convert an attachment URL into a post ID.
 *
 * @since 4.0.0
 *
 * @param string $url The URL to resolve.
 *
 * @return int The found post ID, or 0 on failure.
 */
function smartcrawl_get_attachment_id_by_url( $url ) {
	return attachment_url_to_postid( $url );
}

/**
 * Get attachment from url.
 *
 * @param string $url The URL to resolve.
 *
 * @return array|null Attachment data or null.
 */
function smartcrawl_get_attachment_by_url( $url ) {
	$attachment_id = smartcrawl_get_attachment_id_by_url( $url );
	if ( $attachment_id ) {
		$attachment_image_src = wp_get_attachment_image_src( $attachment_id, 'full' );

		return $attachment_image_src
			? array(
				'url'    => $attachment_image_src[0],
				'width'  => $attachment_image_src[1],
				'height' => $attachment_image_src[2],
			)
			: null;
	}

	return null;
}

/**
 * Get post types having archive.
 *
 * @return int[]|string[]
 */
function smartcrawl_get_archive_post_types() {
	return array_keys( smartcrawl_get_archive_post_type_labels() );
}

/**
 * Get labels for post types having archive.
 *
 * @return array
 */
function smartcrawl_get_archive_post_type_labels() {
	$archive_post_types = array();
	$post_type_args     = array(
		'public'      => true,
		'has_archive' => true,
	);
	foreach ( get_post_types( $post_type_args ) as $post_type ) {
		if ( in_array( $post_type, array( 'revision', 'nav_menu_item' ), true ) ) {
			continue;
		}

		$post_type_object                                 = get_post_type_object( $post_type );
		$archive_post_types[ 'pt-archive-' . $post_type ] = $post_type_object->labels->name;
	}

	return $archive_post_types;
}

/**
 * Fetch sitemap URL in an uniform fashion.
 *
 * TODO see what's going on in this function and get rid of everything we don't need
 *
 * @return string Sitemap URL
 */
function smartcrawl_get_sitemap_url() {
	return apply_filters( 'wds-sitemaps-sitemap_url', home_url( 'sitemap.xml' ) );
}

/**
 * Get plain sitemap url.
 *
 * @param string $type Type of sitemap.
 *
 * @return string
 */
function smartcrawl_get_plain_sitemap_url( $type = 'index' ) {
	return home_url( "?wds_sitemap=1&wds_sitemap_type=$type" );
}

/**
 * Get News sitemap url.
 *
 * @return string
 */
function smartcrawl_get_news_sitemap_url() {
	return home_url( 'news-sitemap.xml' );
}

/**
 * Get plain News sitemap url.
 *
 * @return string
 */
function smartcrawl_get_plain_news_sitemap_url() {
	return home_url( '?wds_news_sitemap=1&wds_news_sitemap_type=index' );
}

/**
 * Get url to robots.txt.
 *
 * @return string
 */
function smartcrawl_get_robots_url() {
	return home_url( '/robots.txt' );
}

/**
 * Get allowed html attributes for form elements.
 *
 * @return array[]
 */
function smartcrawl_get_allowed_html_for_forms() {
	return array(
		'form'  => array(
			'class'   => array(),
			'id'      => array(),
			'action'  => array(),
			'method'  => array(),
			'enctype' => array(),
		),
		'input' => array(
			'class' => array(),
			'id'    => array(),
			'type'  => array(),
			'name'  => array(),
			'value' => array(),
		),
	);
}

/**
 * Reads entire file into a string
 *
 * @param string $file Name of the file to read.
 *
 * @return false|string
 */
function smartcrawl_file_get_contents( $file ) {
	$pre = apply_filters( 'wds_pre_file_get_contents', null, $file );

	return ! is_null( $pre )
		? $pre
		: file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/**
 * Write a string to a file
 *
 * @param string $file Path to the file where to write the data.
 * @param mixed  $contents The data to write. Can be either a string, an array or a stream resource.
 * @param int    $flags [Optional] The value of flags can be any combination of the following flags (with some restrictions), joined with the binary OR (|) operator.
 *
 * @return bool
 */
function smartcrawl_file_put_contents( $file, $contents, $flags = 0 ) {
	$pre = apply_filters( 'wds_pre_file_put_contents', null, $file, $contents, $flags );

	return ! is_null( $pre )
		? $pre
		: (bool) file_put_contents( $file, $contents, $flags ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
}

/**
 * Get latest modified date of a post
 *
 * @param int $post_id Post ID.
 *
 * @return WP_Post
 */
function smartcrawl_get_latest_post_version( $post_id ) {
	$post           = get_post( $post_id );
	$post_revisions = wp_get_post_revisions(
		$post_id,
		array(
			'orderby'       => 'modified',
			'order'         => 'DESC',
			'check_enabled' => false,
		)
	);
	if ( count( $post_revisions ) ) {
		$revision = array_shift( $post_revisions );
		if ( strtotime( $revision->post_modified ) > strtotime( $post->post_modified ) ) {
			return $revision;
		}
	}

	return $post;
}

/**
 * Checks whether the supplied string is a valid meta tag
 *
 * @param string $str String to check.
 *
 * @return bool
 */
function smartcrawl_is_valid_meta_tag( $str ) {
	$str = trim( $str );
	if ( ! preg_match( '/^<meta/i', $str ) ) {
		return false;
	}
	if ( ! preg_match( '/>$/', $str ) ) {
		return false;
	}

	return true;
}

/**
 * Get dashboard profile data.
 *
 * @param mixed $default_value Default value.
 *
 * @return mixed|null
 */
function smartcrawl_get_dash_profile_data( $default_value = null ) {
	if (
		class_exists( '\WPMUDEV_Dashboard' )
		&& isset( WPMUDEV_Dashboard::$site )
		&& is_callable( array( WPMUDEV_Dashboard::$site, 'get_option' ) )
	) {
		$profile_data = WPMUDEV_Dashboard::$site->get_option( 'profile_data' );
		$name         = empty( $profile_data['profile']['name'] )
			? ''
			: $profile_data['profile']['name'];
		$email        = empty( $profile_data['profile']['user_name'] )
			? ''
			: sanitize_email( $profile_data['profile']['user_name'] );

		if ( $name && $email ) {
			return (object) array(
				'user_login' => $name,
				'user_email' => $email,
			);
		}
	}

	return $default_value;
}

/**
 * Retrieves plugin title.
 *
 * @return string
 */
function smartcrawl_get_plugin_title() {
	$white_labeled = smartcrawl_get_white_labeled_title();

	if ( $white_labeled ) {
		return $white_labeled;
	}

	return __( 'SmartCrawl', 'wds' );
}

/**
 * Determines if White Label is enabled.
 *
 * @return bool
 */
function smartcrawl_is_white_label_enabled() {
	return class_exists( '\WPMUDEV_Dashboard' ) &&
		isset( \WPMUDEV_Dashboard::$whitelabel ) &&
		( ! method_exists( \WPMUDEV_Dashboard::$whitelabel, 'can_whitelabel' ) || \WPMUDEV_Dashboard::$whitelabel->can_whitelabel() );
}

/**
 * Retrieves white labeled title.
 *
 * @return string|false
 */
function smartcrawl_get_white_labeled_title() {
	if ( ! \smartcrawl_is_white_label_enabled() ) {
		return false;
	}

	$settings = \WPMUDEV_Dashboard::$whitelabel->get_settings();

	if ( ! $settings['labels_enabled'] ) {
		return false;
	}

	if ( is_multisite() && ! is_network_admin() && empty( $settings['labels_networkwide'] ) ) {
		$sites = (array) $settings['labels_subsites'];

		if ( empty( $sites ) || ! in_array( get_current_blog_id(), $sites, true ) ) {
			return false;
		}
	}

	$config = (array) $settings['labels_config'];

	foreach ( $config as $pid => $data ) {
		$pid = (int) $pid;

		if ( SMARTCRAWL_PACKAGE_ID === $pid ) {
			return $data['name'];
		}
	}

	return false;
}

/**
 * Retrieves docs link enabled status from white label.
 *
 * @return bool
 */
function smartcrawl_is_doc_link_enabled() {
	if ( ! \smartcrawl_is_white_label_enabled() ) {
		return true;
	}

	if ( ! method_exists( \WPMUDEV_Dashboard::$whitelabel, 'get_branding_hide_doc_link' ) ) {
		return true;
	}

	return ! \WPMUDEV_Dashboard::$whitelabel->get_branding_hide_doc_link();
}

/**
 * Checks if current user is allowed to manage Usage Tracking setting.
 *
 * @return bool
 */
function smartcrawl_is_tracking_allowed() {
	if ( ! \smartcrawl_is_white_label_enabled() ) {
		return true;
	}

	if (
		isset( \WPMUDEV_Dashboard::$site ) &&
		method_exists( \WPMUDEV_Dashboard::$site, 'allowed_user' )
	) {
		return \WPMUDEV_Dashboard::$site->allowed_user();
	}

	return true;
}

/**
 * Get SUI class.
 *
 * @return string
 */
function smartcrawl_sui_class() {
	$classes[] = defined( 'SMARTCRAWL_SUI_VERSION' ) && SMARTCRAWL_SUI_VERSION
		? 'sui-' . str_replace( '.', '-', SMARTCRAWL_SUI_VERSION )
		: '';

	$hide_branding = White_Label::get()->is_hide_wpmudev_branding();
	if ( $hide_branding ) {
		$classes[] = 'wds-no-branding';
	}

	return implode( ' ', $classes );
}

/**
 * Get SUI Wrap classes.
 *
 * @param string $page_class Classes to be attached.
 *
 * @return void
 */
function smartcrawl_wrap_class( $page_class = '' ) {
	$classes = 'sui-wrap wrap wrap-wds wds-page';

	if ( $page_class ) {
		$classes .= " $page_class";
	}

	$options = SmartCrawl\Settings::get_options();
	if ( ! empty( $options['high-contrast'] ) ) {
		$classes .= ' sui-color-accessible';
	}

	echo esc_attr( $classes );
}

/**
 * Format formatted anchor html.
 *
 * @param string $text Text being formatted with Html anchor.
 * @param string $url Html anchor url.
 * @param string $anchor Html anchor text.
 * @param string $target Html anchor target attribute.
 *
 * @return string
 */
function smartcrawl_format_link( $text, $url, $anchor = '', $target = '' ) {
	if ( empty( $anchor ) ) {
		$anchor = esc_html__( 'click here', 'wds' );
	}

	return sprintf(
		$text,
		sprintf(
			'<a target="%s" href="%s">%s</a>',
			$target,
			esc_url_raw( $url ),
			$anchor
		)
	);
}

/**
 * Sanitize preserved macros.
 *
 * @param string $str String to be sanitized.
 *
 * @return string
 */
function smartcrawl_sanitize_preserve_macros( $str ) {
	if ( empty( $str ) ) {
		return $str;
	}

	$rpl = '__SMARTCRAWL_MACRO_QUOTES_REPLACEMENT__';
	$str = preg_replace( '/%%/', $rpl, $str );

	$str = sanitize_text_field( $str );

	return preg_replace( '/' . preg_quote( $rpl, '/' ) . '/', '%%', $str );
}

/**
 * Get current upload directory.
 *
 * @return string
 */
function smartcrawl_uploads_dir() {
	$dir  = wp_upload_dir();
	$path = trailingslashit( $dir['basedir'] );

	return "{$path}smartcrawl/";
}

/**
 * Get meta title min length.
 *
 * @return int
 */
function smartcrawl_title_min_length() {
	$options      = SmartCrawl\Settings::get_options();
	$custom_limit = (int) smartcrawl_get_array_value( $options, 'custom_title_min_length' );
	if ( empty( $options['custom_title_char_lengths'] ) || $custom_limit <= 0 ) {
		return SMARTCRAWL_TITLE_DEFAULT_MIN_LENGTH;
	}

	return $custom_limit;
}

/**
 * Get meta title max length.
 *
 * @return int
 */
function smartcrawl_title_max_length() {
	$options      = SmartCrawl\Settings::get_options();
	$custom_limit = (int) smartcrawl_get_array_value( $options, 'custom_title_max_length' );
	if ( empty( $options['custom_title_char_lengths'] ) || $custom_limit <= 0 ) {
		return SMARTCRAWL_TITLE_DEFAULT_MAX_LENGTH;
	}

	return $custom_limit;
}

/**
 * Get meta description min length.
 *
 * @return int
 */
function smartcrawl_metadesc_min_length() {
	$options      = SmartCrawl\Settings::get_options();
	$custom_limit = (int) smartcrawl_get_array_value( $options, 'custom_metadesc_min_length' );
	if ( empty( $options['custom_metadesc_char_lengths'] ) || $custom_limit <= 0 ) {
		return SMARTCRAWL_METADESC_DEFAULT_MIN_LENGTH;
	}

	return $custom_limit;
}

/**
 * Get meta description max length.
 *
 * @return int
 */
function smartcrawl_metadesc_max_length() {
	$options      = SmartCrawl\Settings::get_options();
	$custom_limit = (int) smartcrawl_get_array_value( $options, 'custom_metadesc_max_length' );
	if ( empty( $options['custom_metadesc_char_lengths'] ) || $custom_limit <= 0 ) {
		return SMARTCRAWL_METADESC_DEFAULT_MAX_LENGTH;
	}

	return $custom_limit;
}

/**
 * Check if SMARTCRAWL_BUILD_TYPE constant value is 'full'.
 *
 * @return bool
 */
function smartcrawl_is_build_type_full() {
	return defined( 'SMARTCRAWL_BUILD_TYPE' ) && SMARTCRAWL_BUILD_TYPE === 'full';
}

/**
 * Get frontend available post types.
 *
 * @return array
 */
function smartcrawl_frontend_post_types() {
	$types['post']       = 'post';
	$types['page']       = 'page';
	$types['attachment'] = 'attachment';
	foreach (
		get_post_types(
			array(
				'public'             => true,
				'publicly_queryable' => true,
				'_builtin'           => false,
			)
		) as $type
	) {
		$types[ $type ] = $type;
	}

	return $types;
}

/**
 * Get frontend available taxonomies.
 *
 * @param string $output Optional. The type of output to return in the array. Accepts either taxonomy 'names' or 'objects'.
 *
 * @return string[]|WP_Taxonomy[]
 */
function smartcrawl_frontend_taxonomies( $output = 'objects' ) {
	$taxonomies = get_taxonomies(
		array(
			'public'             => true,
			'publicly_queryable' => true,
		),
		$output
	);

	unset( $taxonomies['post_format'] );

	return $taxonomies;
}

/**
 * Sanitize email recipients.
 *
 * @param array $email_recipients Recipients to be sanitized.
 *
 * @return array
 */
function smartcrawl_sanitize_recipients( $email_recipients ) {
	if ( empty( $email_recipients ) ) {
		return array();
	}

	$sanitized_recipients = array();
	foreach ( $email_recipients as $recipient ) {
		$recipient_name  = smartcrawl_get_array_value( $recipient, 'name' );
		$recipient_email = smartcrawl_get_array_value( $recipient, 'email' );

		$sanitized_emails = array_column( $sanitized_recipients, 'email' );
		$recipient_exists = in_array( $recipient_email, $sanitized_emails, true );
		if (
			$recipient_name && $recipient_email
			&& sanitize_text_field( $recipient_name ) === $recipient_name
			&& sanitize_email( $recipient_email ) === $recipient_email
			&& ! $recipient_exists
		) {
			$sanitized_recipients[] = $recipient;
		}
	}

	return $sanitized_recipients;
}

/**
 * Get manager role for sub-site.
 *
 * @return mixed|string
 */
function smartcrawl_subsite_manager_role() {
	$manager_role = get_site_option( 'wds_subsite_manager_role' );

	return empty( $manager_role )
		? 'admin'
		: $manager_role;
}

/**
 * Activate all blog tabs.
 *
 * @return void
 */
function smartcrawl_activate_all_blog_tabs() {
	update_site_option(
		'wds_blog_tabs',
		array(
			SmartCrawl\Settings::TAB_ONPAGE      => true,
			SmartCrawl\Settings::TAB_SCHEMA      => true,
			SmartCrawl\Settings::TAB_SOCIAL      => true,
			SmartCrawl\Settings::TAB_SITEMAP     => true,
			SmartCrawl\Settings::ADVANCED_MODULE => true,
			SmartCrawl\Settings::TAB_SETTINGS    => true,
		)
	);
}

/**
 * Convert camel case to snake format.
 *
 * @param string $str String to be converted.
 *
 * @return string
 */
function smartcrawl_camel_to_snake( $str ) {
	return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $str ) );
}

/**
 * Convert snake to camel case format.
 *
 * @param string $str String to be converted.
 *
 * @return string
 */
function smartcrawl_snake_to_camel( $str ) {
	$str    = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $str ) ) );
	$str[0] = strtolower( $str[0] );

	return $str;
}

/**
 * Check if Woocommerce is acive.
 *
 * @return bool
 */
function smartcrawl_woocommerce_active() {
	$active_plugins = array();

	if ( is_multisite() ) {
		$active_plugins = array_keys( get_site_option( 'active_sitewide_plugins' ) );
	}

	$active_plugins = array_merge( $active_plugins, array_values( get_option( 'active_plugins' ) ) );

	return in_array( 'woocommerce/woocommerce.php', $active_plugins, true );
}

/**
 * Sanitize a string.
 *
 * @param string $value String to be sanitized.
 *
 * @return array|string
 */
function smartcrawl_clean( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'smartcrawl_clean', $value );
	} else {
		return is_scalar( $value ) ? sanitize_text_field( $value ) : $value;
	}
}

/**
 * Get a hash from array.
 *
 * @param array $haystack Array to be converted into hash.
 * @param array $keys Array of keys.
 *
 * @return int
 */
function smartcrawl_array_hash( $haystack, $keys = array() ) {
	$hash = 0;
	if ( is_array( $haystack ) ) {
		foreach ( $haystack as $key => $value ) {
			if ( is_array( $value ) ) {
				$value_hash = smartcrawl_array_hash(
					$value,
					array_merge( $keys, array( $key ) )
				);
			} else {
				$prefix     = join( '~', $keys );
				$value_hash = crc32( $prefix . $value );
			}

			$hash += $value_hash;
		}
	}

	return $hash;
}

/**
 * Check whether arrays are the same or not.
 *
 * @param array $array1 First array to be compared.
 * @param array $array2 Second array to be compared.
 *
 * @return bool
 */
function smartcrawl_arrays_same( $array1, $array2 ) {
	if (
		! is_array( $array1 )
		|| ! is_array( $array2 )
		|| count( $array1 ) !== count( $array2 )
	) {
		return false;
	}

	return smartcrawl_array_hash( $array1 ) === smartcrawl_array_hash( $array2 );
}

/**
 * Return the CSV mime types.
 *
 * @return string[]
 */
function smartcrawl_csv_mime_types() {
	return array(
		'text/plain',
		'text/x-csv',
		'text/plain',
		'application/vnd.ms-excel',
		'text/x-csv',
		'application/csv',
		'application/x-csv',
		'text/csv',
		'text/comma-separated-values',
		'text/x-comma-separated-values',
		'text/tab-separated-values',
	);
}

/**
 * Update and return url with archive page number attached.
 *
 * @param string $url Url to be updated.
 * @param int    $page_number Page number.
 *
 * @return string
 */
function smartcrawl_append_archive_page_number( $url, $page_number ) {
	/**
	 * WP Rewrite.
	 *
	 * @var $wp_rewrite WP_Rewrite
	 */
	global $wp_rewrite;
	if ( $page_number > 1 ) {
		if ( $wp_rewrite->using_permalinks() ) {
			$url = trailingslashit( $url ) . sprintf( $wp_rewrite->pagination_base . '/%d/', $page_number );
		} else {
			$url = esc_url_raw( add_query_arg( 'paged', $page_number, $url ) );
		}
	}

	return $url;
}

/**
 * Print admin notice.
 *
 * @param string $key Notice key.
 * @param string $title Notice title.
 * @param string $message Notice message.
 * @param string $action_url Action url.
 * @param string $button_text Action text.
 *
 * @return void
 */
function smartcrawl_print_admin_notice( $key, $title, $message, $action_url, $button_text ) {
	?>
	<div
		class="notice-info notice is-dismissible wds-native-dismissible-notice"
		data-message-key="<?php echo esc_attr( $key ); ?>"
	>
		<?php if ( $title ) : ?>
			<p><strong><?php echo esc_html( $title ); ?></strong></p>
		<?php endif; ?>

		<p style="margin-bottom:15px;">
			<?php echo wp_kses( $message, wp_kses_allowed_html() ); ?>
		</p>
		<a
			href="<?php echo esc_attr( $action_url ); ?>"
			class="button button-primary"
		>
			<?php echo esc_html( $button_text ); ?>
		</a>
		<a
			href="#"
			class="wds-native-dismiss"
		>
			<?php esc_html_e( 'Not now', 'wds' ); ?>
		</a>
		<p></p>
	</div>
	<?php
}

/**
 * Render our breadcrumbs output.
 *
 * @param string $before What to show before the breadcrumb.
 * @param string $after  What to show after the breadcrumb.
 * @param bool   $should_echo   Should echo the output.
 *
 * @return string
 *
 * @since 3.5.0
 * @depecated 3.5.2 Use smartcrawl_breadcrumbs() instead.
 */
function smartcrawl_breadcrumb( $before = '', $after = '', $should_echo = true ) {
	return smartcrawl_breadcrumbs( $before, $after, $should_echo );
}

/**
 * Render our breadcrumbs output.
 *
 * @param string $before What to show before the breadcrumb.
 * @param string $after  What to show after the breadcrumb.
 * @param bool   $should_echo   Should echo the output.
 *
 * @return string
 *
 * @since 3.5.0
 */
function smartcrawl_breadcrumbs( $before = '', $after = '', $should_echo = true ) {
	$html = \SmartCrawl\Modules\Advanced\Breadcrumbs\Controller::get()->render_breadcrumb( $before, $after );

	if ( $should_echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return '';
	}

	return $html;
}

/**
 * Gets a list of all registered post type objects.
 *
 * @return array
 */
function smartcrawl_get_post_types() {
	$post_types = array();

	$exclusions = array(
		'revision',
		'nav_menu_item',
		'attachment',
	);
	$raw        = get_post_types(
		array( 'public' => true ),
		'objects'
	);
	foreach ( $raw as $pt => $pto ) {
		if ( in_array( $pt, $exclusions, true ) ) {
			continue;
		}
		$post_types[ $pt ] = $pto;
	}

	return $post_types;
}

/**
 * Registers a new script.
 *
 * @param string       $handle Name of the script. Should be unique.
 * @param string|false $src    Relative URL of the script.
 * @param string[]     $deps   Optional. An array of registered script handles this script depends on. Default empty array.
 *
 * @return void
 */
function smartcrawl_register_script( $handle, $src, $deps = array() ) {
	wp_register_script( $handle, smartcrawl_asset_url( 'js/build/' . $src . '.min.js' ), $deps, smartcrawl_get_version(), true );

	wp_set_script_translations( $handle, 'wds' );
}

/**
 * Enqueues a new script.
 *
 * @param string       $handle Name of the script. Should be unique.
 * @param string|false $src    Relative URL of the script.
 * @param string[]     $deps   Optional. An array of registered script handles this script depends on. Default empty array.
 *
 * @return void
 */
function smartcrawl_enqueue_script( $handle, $src, $deps = array() ) {
	wp_enqueue_script( $handle, smartcrawl_asset_url( 'js/build/' . $src . '.min.js' ), $deps, smartcrawl_get_version(), true );

	wp_set_script_translations( $handle, 'wds' );
}

/**
 * Registers a CSS stylesheet.
 *
 * @param string       $handle Name of the stylesheet. Should be unique.
 * @param string|false $src    Relative URL of the stylesheet.
 * @param string[]     $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
 *
 * @return void
 */
function smartcrawl_register_style( $handle, $src, $deps = array() ) {
	wp_register_style( $handle, smartcrawl_asset_url( 'css/' . $src . '.min.css' ), $deps, smartcrawl_get_version() );
}

/**
 * Enqueues a CSS stylesheet.
 *
 * @param string       $handle Name of the stylesheet. Should be unique.
 * @param string|false $src    Relative URL of the stylesheet.
 * @param string[]     $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
 *
 * @return void
 */
function smartcrawl_enqueue_style( $handle, $src, $deps = array() ) {
	wp_enqueue_style( $handle, smartcrawl_asset_url( 'css/' . $src . '.min.css' ), $deps, smartcrawl_get_version() );
}

/**
 * Retrieves asset's full URL from plugin's base url.
 *
 * @param string $url Relative url of asset.
 *
 * @return string
 */
function smartcrawl_asset_url( $url ) {
	return trailingslashit( SMARTCRAWL_PLUGIN_URL ) . "assets/$url";
}

/**
 * Retrieves plugin's current version for assets.
 *
 * @return string
 */
function smartcrawl_get_version() {
	$version = \SmartCrawl\SmartCrawl::get_version();

	if ( defined( 'SMARTCRAWL_BUILD' ) ) {
		$version = sprintf( '%s-%s', $version, SMARTCRAWL_BUILD );
	}

	return $version;
}

/**
 * Checks if two arrays are different.
 *
 * @param array $array1 The array to compare from.
 * @param array $array2 The array to compare to.
 *
 * @return bool
 */
function smartcrawl_array_diff( $array1, $array2 ) {
	foreach ( $array1 as $key => $value ) {
		if ( is_array( $value ) ) {
			if ( ! isset( $array2[ $key ] ) || ! is_array( $array2[ $key ] ) ) {
				return true;
			}
			if ( smartcrawl_array_diff( $value, $array2[ $key ] ) ) {
				return true;
			}
		} elseif ( ! isset( $array2[ $key ] ) || $array2[ $key ] !== $value ) {
				return true;
		}
	}

	foreach ( $array2 as $key => $value ) {
		if ( ! isset( $array1[ $key ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Returns the admin email for the plugin.
 *
 * @return string The admin email HTML if available, otherwise "your DEV account email" HTML.
 *
 * @global \WPMUDEV_Dashboard $site
 */
function smartcrawl_get_admin_email() {
	$admin_email = false;
	$dash_email  = false;

	if ( class_exists( '\WPMUDEV_Dashboard' ) && ! empty( \WPMUDEV_Dashboard::$site ) ) {
		if ( is_callable( array( \WPMUDEV_Dashboard::$site, 'get_option' ) ) ) {
			$dash_email = \WPMUDEV_Dashboard::$site->get_option( 'auth_user' );
			if ( false !== strpos( $dash_email, '@' ) ) {
				$admin_email = $dash_email;
			}
		}
	}

	if ( ! empty( $dash_email ) && ! empty( $admin_email ) ) {
		$admin_email = sprintf( '<a href="mailto: %1$s"><strong>%1$s</strong></a>', $admin_email );
	} else {
		$admin_email = sprintf( '<strong>%1$s</strong>', esc_html__( 'your DEV account email', 'wds' ) );
	}

	return $admin_email;
}