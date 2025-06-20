<?php
/**
 * On-page processing stuff
 *
 * OnPage::smartcrawl_title(), OnPage::smartcrawl_head(), OnPage::smartcrawl_metadesc()
 * inspired by WordPress SEO by Joost de Valk (http://yoast.com/wordpress/seo/).
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Singleton;
use SmartCrawl\Settings;
use SmartCrawl\Entities\Entity;
use SmartCrawl\Endpoint_Resolver;
use SmartCrawl\Admin\Settings\Admin_Settings;

/**
 * On-page (title, meta etc) stuff processing class
 */
class OnPage extends Controller {

	use Singleton;

	/**
	 * Queried entity.
	 *
	 * @var Entity|null
	 */
	private $queried_entity;

	/**
	 * Is module active?
	 *
	 * @return bool
	 */
	public function is_onpage_module_active() {
		return (
			Settings::get_setting( 'onpage' ) &&
			Admin_Settings::is_tab_allowed( Settings::TAB_ONPAGE ) &&
			$this->run_on_simplepress()
		);
	}

	/**
	 * Binds processing actions.
	 */
	protected function init() {
		$options = Settings::get_options();

		if ( ! empty( $options['general-suppress-generator'] ) ) {
			remove_action( 'wp_head', 'wp_generator' );
		}

		if ( ! empty( $options['general-suppress-redundant_canonical'] ) ) {
			if ( ! defined( 'SMARTCRAWL_SUPPRESS_REDUNDANT_CANONICAL' ) ) {
				define( 'SMARTCRAWL_SUPPRESS_REDUNDANT_CANONICAL', true );
			}
		}

		if ( ! $this->is_onpage_module_active() ) {
			add_action( 'wp_head', array( $this, 'smartcrawl_head_extras' ), 10, 1 );

			// The rest is only supposed to work when the onpage module is active.
			return;
		}

		remove_action( 'wp_head', 'rel_canonical' );

		add_action( 'wp_head', array( $this, 'smartcrawl_head' ), 10, 1 );

		// wp_title isn't enough. We'll do it anyway: suspenders and belt approach.
		add_filter( 'wp_title', array( $this, 'smartcrawl_title' ), 100 );

		// For newer themes using wp_get_document_title().
		add_filter( 'pre_get_document_title', array( $this, 'smartcrawl_title' ), 100 );

		// Buffer the header output and process it instead.
		if ( $this->force_rewrite_title() ) {
			add_action( 'template_redirect', array( $this, 'smartcrawl_start_title_buffer' ), 99 );
		}

		// This should now work with BuddyPress as well.
		add_filter( 'bp_page_title', array( $this, 'smartcrawl_title' ), 10 );

		if ( $this->wp_robots_api_available() ) {
			remove_filter( 'wp_robots', 'wp_robots_noindex_search' ); // SmartCrawl is going to handle the search archive.
			add_filter( 'wp_robots', array( $this, 'add_smartcrawl_robots_to_wp_robots' ) );
		}
	}

	/**
	 * Can't fully handle SimplePress installs properly.
	 *
	 * For non-forum pages, do our thing all the way.
	 * For forum pages, do nothing.
	 */
	private function run_on_simplepress() {
		global $wp_query;

		if (
			defined( '\SF_PREFIX' )
			&& function_exists( '\sf_get_option' )
		) {
			return (int) \sf_get_option( 'sfpage' ) !== $wp_query->post->ID;
		}

		return true;
	}

	/**
	 * Starts buffering the header.
	 *
	 * The buffer output will be used to replace the title.
	 */
	public function smartcrawl_start_title_buffer() {
		ob_start( array( $this, 'smartcrawl_process_title_buffer' ) );
	}

	/**
	 * Handles the title buffer.
	 * Replaces the title with what we get from the old smartcrawl_title method.
	 * If we get nothing from it, do nothing.
	 *
	 * @param string $head Header area to process.
	 *
	 * @return string
	 */
	public function smartcrawl_process_title_buffer( $head ) {
		if ( is_feed() ) {
			return $head;
		}

		$title_rx = '<title[^>]*?>.*?' . preg_quote( '</title>', '' );
		$head_rx  = '<head [^>]*? >';
		$head     = preg_replace( '/\n/', '__SMARTCRAWL_NL__', $head );
		// Dollar signs throw off replacement...
		$title = preg_replace( '/\$/', '__SMARTCRAWL_DOLLAR__', $this->smartcrawl_title( '' ) ); // ... so temporarily escape them, then
		// Make sure we're replacing TITLE that's actually in the HEAD.
		$head = ( $title && preg_match( "~{$head_rx}~ix", $head ) ) ?
			preg_replace( "~{$title_rx}~i", "<title>{$title}</title>", $head )
			: $head;

		return preg_replace( '/__SMARTCRAWL_NL__/', "\n", preg_replace( '/__SMARTCRAWL_DOLLAR__/', '\$', $head ) );
	}

	/**
	 * Retrieves queried entity.
	 *
	 * @return Entity
	 */
	private function get_queried_entity() {
		if ( ! $this->queried_entity ) {
			$this->queried_entity = Endpoint_Resolver::resolve()->get_queried_entity();
		}

		return $this->queried_entity;
	}

	/**
	 * Gets the processed HTML title
	 *
	 * @param string $title Original title.
	 *
	 * @return string
	 */
	public function smartcrawl_title( $title ) {
		$entity = $this->get_queried_entity();

		if ( $entity && method_exists( $entity, 'get_meta_title' ) ) {
			return esc_html( wp_strip_all_tags( stripslashes( $entity->get_meta_title() ) ) );
		}

		return $title;
	}

	/**
	 * Processes the stuff that goes into the HTML head
	 */
	public function smartcrawl_head() {
		if ( $this->force_rewrite_title() ) {
			$this->smartcrawl_stop_title_buffer(); // STOP processing the buffer.
		}

		$this->head_start();

		$this->smartcrawl_canonical();
		$this->smartcrawl_rel_links();
		if ( ! $this->wp_robots_api_available() ) {
			$this->smartcrawl_robots();
		}
		$this->smartcrawl_metadesc();

		$this->print_meta_tags();
		$this->head_end();
	}

	/**
	 * Extra items for head.
	 *
	 * @return void
	 */
	public function smartcrawl_head_extras() {
		$this->head_start();
		$this->print_meta_tags();
		$this->head_end();
	}

	/**
	 * Print html tag.
	 *
	 * @param string $html HTML content.
	 *
	 * @return bool
	 */
	private function print_html_tag( $html ) {
		if ( ! preg_match( '/\<(link|meta)/', $html ) ) {
			// Do not allow plaintext output.
			return false;
		}
		echo wp_kses(
			$html,
			array(
				'meta' => array(
					'name'       => array(),
					'content'    => array(),
					'http-equiv' => array(),
					'charset'    => array(),
					'property'   => array(),
					'scheme'     => array(),
				),
				'link' => array(
					'charset'         => array(),
					'crossorigin'     => array(),
					'use-credentials' => array(),
					'href'            => array(),
					'hreflang'        => array(),
					'media'           => array(),
					'rel'             => array(),
					'stylesheet'      => array(),
					'rev'             => array(),
					'sizes'           => array(),
					'any'             => array(),
					'target'          => array(),
					'frame_name'      => array(),
					'type'            => array(),
				),
			)
		);

		return true;
	}

	/**
	 * Stops buffering the output - the title should now be in the buffer.
	 */
	private function smartcrawl_stop_title_buffer() {
		if ( function_exists( '\ob_list_handlers' ) ) {
			$active_handlers = \ob_list_handlers();
		} else {
			$active_handlers = array();
		}
		if ( count( $active_handlers ) > 0 ) {
			$offset  = count( $active_handlers ) - 1;
			$handler = ! empty( $active_handlers[ $offset ] ) ? trim( $active_handlers[ $offset ] ) : '';

			if ( preg_match( '/::smartcrawl_process_title_buffer$/', $handler ) ) {
				ob_end_flush();
			}
		}
	}

	/**
	 * Handle canonical link rendering
	 *
	 * @return bool Status
	 */
	private function smartcrawl_canonical() {
		if (
			function_exists( '\bp_is_blog_page' ) // If we have BuddyPress.
			&& // ... and
			! ( \bp_is_blog_page() || is_404() ) // ... we're on a BP page.
		) {
			// Because apparently BP prints it's own canonical URLs.
			return false;
		}

		if ( ! apply_filters( 'wds_process_canonical', true ) ) {
			return false;
		} // Allow optional filtering out.
		// Set decent canonicals for homepage, singulars and taxonomy pages.
		$canonical = $this->get_canonical_url();

		// Let's check if we're dealing with the redundant canonical.
		if ( \smartcrawl_is_switch_active( 'SMARTCRAWL_SUPPRESS_REDUNDANT_CANONICAL' ) ) {
			global $wp;

			$current_url = add_query_arg( $_GET, trailingslashit( home_url( $wp->request ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( $current_url === $canonical ) {
				$canonical = false;
			}
		}

		if ( ! empty( $canonical ) && ! is_wp_error( $canonical ) ) {
			$this->print_html_tag( '<link rel="canonical" href="' . esc_attr( $canonical ) . '" />' . "\n" );
		}

		return ! empty( $canonical );
	}

	/**
	 * Retrieves canonical URL.
	 *
	 * @return bool|mixed|string|\WP_Error
	 */
	public function get_canonical_url() {
		$entity = $this->get_queried_entity();

		if ( $entity && method_exists( $entity, 'get_canonical_url' ) ) {
			return $entity->get_canonical_url();
		}

		return '';
	}

	/**
	 * Output link rel tags.
	 */
	private function smartcrawl_rel_links() {
		global $wp_query, $paged;

		if ( ! $wp_query->max_num_pages ) {
			return false;
		} // Short out on missing max page number.
		if ( ! apply_filters( 'wds_process_rel_links', true ) ) {
			return false;
		} // Allow optional filtering out.

		$is_taxonomy     = ( is_tax() || is_tag() || is_category() || is_date() );
		$requested_year  = get_query_var( 'year' );
		$requested_month = get_query_var( 'monthnum' );
		$is_date         = is_date() && ! empty( $requested_year );
		$date_callback   = ! empty( $requested_year ) && empty( $requested_month )
			? 'get_year_link'
			: 'get_month_link';
		$pageable        = ( $is_taxonomy || ( is_home() && 'posts' === get_option( 'show_on_front' ) ) );
		if ( ! $pageable ) {
			return false;
		}

		$term      = $wp_query->get_queried_object();
		$canonical = ! empty( $term->taxonomy ) && $is_taxonomy ? \smartcrawl_get_term_meta( $term, $term->taxonomy, 'wds_canonical' ) : false;
		if ( ! $canonical ) {
			if ( (int) $paged > 1 ) {
				$prev = is_home() ? home_url() : (
				$is_date
					? $date_callback( $requested_year, $requested_month )
					: get_term_link( $term, $term->taxonomy )
				);
				$prev = ( '' === get_option( 'permalink_structure' ) )
					? ( ( $paged > 2 ) ? add_query_arg( 'page', $paged - 1, $prev ) : $prev )
					: ( ( $paged > 2 ) ? trailingslashit( $prev ) . 'page/' . ( $paged - 1 ) : $prev );
				$prev = esc_attr( trailingslashit( $prev ) );
				$this->print_html_tag( "<link rel='prev' href='{$prev}' />\n" );
			}

			$is_paged = (int) $paged ? (int) $paged : 1;

			if ( $is_paged < $wp_query->max_num_pages ) {
				$next      = is_home() ? home_url() : (
				$is_date
					? $date_callback( $requested_year, $requested_month )
					: get_term_link( $term, $term->taxonomy )
				);
				$next_page = $is_paged + 1;
				$next      = ( '' === get_option( 'permalink_structure' ) )
					? add_query_arg( 'page', $next_page, $next )
					: trailingslashit( $next ) . 'page/' . $next_page;
				$next      = esc_attr( trailingslashit( $next ) );
				$this->print_html_tag( "<link rel='next' href='{$next}' />\n" );
			}
		}

		return true;
	}

	/**
	 * Retrieves robots string.
	 *
	 * @return string
	 */
	private function get_robots_string() {
		$entity = $this->get_queried_entity();

		$robots = is_object( $entity ) && method_exists( $entity, 'get_robots' )
			? $entity->get_robots()
			: '';

		// Cleans up, index, follow is the default and doesn't need to be in output. All other combinations should be.
		if ( 'index,follow' === $robots ) {
			$robots = '';
		}
		if ( strpos( $robots, 'index,follow,' ) === 0 ) {
			$robots = str_replace( 'index,follow,', '', $robots );
		}

		return rtrim( $robots, ',' );
	}

	/**
	 * Outputs meta robots tag
	 *
	 * @return bool
	 */
	private function smartcrawl_robots() {
		if ( $this->robots_processing_disabled() ) {
			return false;
		}

		$robots = $this->get_robots_string();
		if ( $this->is_blog_public() && '' !== $robots ) {
			$this->print_html_tag( '<meta name="robots" content="' . esc_attr( $robots ) . '"/>' . "\n" );
		}

		return true;
	}

	/**
	 * Adds items to robots.
	 *
	 * @param array $wp_robots Robots.
	 *
	 * @return mixed
	 */
	public function add_smartcrawl_robots_to_wp_robots( $wp_robots ) {
		if (
			! $this->is_blog_public() // If user has an override at the blog level.
			|| $this->robots_processing_disabled() // or robots processing is disabled.
		) {
			// leave everything to WP.
			return $wp_robots;
		}

		$sc_robots_string = $this->get_robots_string();
		if ( empty( $sc_robots_string ) ) {
			return $wp_robots;
		}

		$sc_robots = explode( ',', $sc_robots_string );
		foreach ( $sc_robots as $directive ) {
			$wp_robots[ $directive ] = true;
		}

		return $wp_robots;
	}

	/**
	 * Outputs meta description.
	 *
	 * @return bool
	 */
	private function smartcrawl_metadesc() {
		if ( is_admin() ) {
			return false;
		}

		$entity   = $this->get_queried_entity();
		$metadesc = $entity && method_exists( $entity, 'get_meta_description' )
			? $entity->get_meta_description()
			: '';
		$metadesc = wp_kses( wp_strip_all_tags( stripslashes( $metadesc ) ), array(), array() );

		if ( ! empty( $metadesc ) ) {
			echo '<meta name="description" content="' . esc_attr( $metadesc ) . '" />' . "\n";
		}

		return true;
	}

	/**
	 * Gets (custom) meta tags for output.
	 *
	 * @return array
	 */
	public function get_meta_tags() {
		// Sitemap options are shown on the settings page so the decision to fallback should be made after checking.
		// if Settings::TAB_SETTINGS is allowed.
		// This logic follows the pattern used in Smartcrawl_Settings._populate_options.
		$smartcrawl_options = get_option( Settings::TAB_SITEMAP . '_options', array() );

		$metas = array();

		$include_verifications = (bool) (
			empty( $smartcrawl_options['verification-pages'] )
			|| (
				! empty( $smartcrawl_options['verification-pages'] )
				&&
				'home' === $smartcrawl_options['verification-pages']
				&&
				is_front_page()
			)
		);

		// Full meta overrides.
		if ( ! empty( $smartcrawl_options['verification-google-meta'] ) && $include_verifications ) {
			$metas['google'] = $smartcrawl_options['verification-google-meta'];
		}
		if ( ! empty( $smartcrawl_options['verification-bing-meta'] ) && $include_verifications ) {
			$metas['bing'] = $smartcrawl_options['verification-bing-meta'];
		}

		$additional = ! empty( $smartcrawl_options['additional-metas'] ) ? $smartcrawl_options['additional-metas'] : array();
		if ( ! is_array( $additional ) ) {
			$additional = array();
		}

		foreach ( $additional as $meta ) {
			$metas[] = $meta;
		}

		return $metas;
	}

	/**
	 * Force rewrite title.
	 *
	 * @return bool
	 */
	private function force_rewrite_title() {
		return \smartcrawl_is_switch_active( '\SMARTCRAWL_FORCE_REWRITE_TITLE' );
	}

	/**
	 * Output head start items.
	 *
	 * @return void
	 */
	private function head_start() {
		$is_white_label = \smartcrawl_is_switch_active( 'SMARTCRAWL_WHITELABEL_ON' ) || White_Label::get()->is_hide_wpmudev_branding();

		if ( ! $is_white_label ) {
			$project = defined( 'SMARTCRAWL_PROJECT_TITLE' ) ? SMARTCRAWL_PROJECT_TITLE : 'SmartCrawl';
			echo '<!-- SEO meta tags powered by ' . esc_html( $project ) . " -->\n";
		}
	}

	/**
	 * Output to head end.
	 *
	 * @return void
	 */
	private function head_end() {
		do_action( 'smartcrawl_head_after_output' );

		if ( ! \smartcrawl_is_switch_active( 'SMARTCRAWL_WHITELABEL_ON' ) ) {
			echo "<!-- /SEO -->\n";
		}
	}

	/**
	 * Print meta tags.
	 *
	 * @return void
	 */
	private function print_meta_tags() {
		$metas = $this->get_meta_tags();
		foreach ( $metas as $meta ) {
			$this->print_html_tag( "{$meta}\n" );
		}
	}

	/**
	 * Check if robots processing is disabled.
	 *
	 * @return bool
	 */
	private function robots_processing_disabled() {
		return ! apply_filters( 'wds_process_robots', true );
	}

	/**
	 * Check if robots API is available.
	 *
	 * @return bool
	 */
	private function wp_robots_api_available() {
		global $wp_version;

		return version_compare( $wp_version, '5.7', '>=' );
	}

	/**
	 * Check if blog is public.
	 *
	 * @return bool
	 */
	private function is_blog_public() {
		return 1 === (int) get_option( 'blog_public' );
	}
}