<?php
/**
 * Entity resolving stuff.
 *
 * Interface for resolving/simulating varions WP resources,
 * virtual or otherwise.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Entities\Entity;

/**
 * Entity resolving class
 */
class Endpoint_Resolver {

	/**
	 * Traditional blog style homepage containing a list of posts
	 */
	const L_BLOG_HOME = 'front_home_posts';
	/**
	 * Page for posts
	 */
	const L_STATIC_HOME = 'static_home';

	const L_SEARCH = 'search_page';

	const L_404 = '404_page';

	const L_ARCHIVE = 'archive';

	const L_DATE_ARCHIVE = 'date';

	const L_PT_ARCHIVE = 'post_type_archive';

	const L_TAX_ARCHIVE = 'taxonomy_archive';

	const L_AUTHOR_ARCHIVE = 'author_archive';

	const L_SINGULAR = 'singular';

	const L_BP_GROUPS = 'bp_groups';

	const L_BP_PROFILE = 'bp_profile';

	const L_WOO_SHOP = 'woo_shop';
	/**
	 * Singleton instance
	 *
	 * @var Endpoint_Resolver
	 */
	private static $instance;
	/**
	 * Current resolved location
	 *
	 * One of the known constants, or false-ish.
	 *
	 * @var string
	 */
	private $location;

	/**
	 * Queried entity
	 *
	 * @var Entity
	 */
	private $queried;

	/**
	 * Gets object instance ready for item resolution
	 *
	 * @return Endpoint_Resolver instance
	 */
	public static function resolve() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->resolve_location();
		}

		return self::$instance;
	}

	/**
	 * Gets the queried entity.
	 *
	 * @return Entity
	 */
	public function get_queried_entity() {
		return $this->queried;
	}

	/**
	 * Sets the queried entity.
	 *
	 * @param Entity $queried Entity.
	 */
	public function set_queried_entity( $queried ) {
		$this->queried = $queried;
	}

	/**
	 * Resolves current location to one of known constants
	 */
	public function resolve_location() {
		$query = $this->get_query_context();
		if ( ! $query instanceof \WP_Query ) {
			return;
		}

		$queried_object_id   = $query->get_queried_object_id();
		$queried_object      = $query->get_queried_object();
		$queried_posts       = $query->posts;
		$archive_page_number = $query->get( 'paged', 0 );

		$buddypress_api = new \SmartCrawl\BuddyPress\Api();
		$woo_api        = new Integration\Woocommerce\Api();

		if ( $this->is_static_posts_page() ) {
			$this->set_location( self::L_STATIC_HOME );
			$this->set_queried_entity(
				new Entities\Static_Home( $queried_posts, $archive_page_number )
			);
		} elseif ( $this->is_home_posts_page() ) {
			$this->set_location( self::L_BLOG_HOME );
			$this->set_queried_entity(
				new Entities\Blog_Home( $archive_page_number )
			);
		} elseif ( \smartcrawl_woocommerce_active() && $woo_api->is_shop() ) {
			$this->set_location( self::L_WOO_SHOP );
			$this->set_queried_entity(
				new Entities\Woo_Shop_Page( $queried_posts )
			);
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$this->set_location( self::L_TAX_ARCHIVE );
			$this->set_queried_entity(
				new Entities\Taxonomy_Term( $queried_object_id, $queried_posts, $archive_page_number )
			);
		} elseif ( is_search() ) {
			$this->set_location( self::L_SEARCH );
			$this->set_queried_entity(
				new Entities\Search_Page( $query->get( 's' ), $queried_posts, $archive_page_number )
			);
		} elseif ( is_author() ) {
			$this->set_location( self::L_AUTHOR_ARCHIVE );
			$this->set_queried_entity(
				new Entities\Post_Author(
					get_user_by( 'id', $queried_object_id ),
					$queried_posts,
					$archive_page_number
				)
			);
		} elseif ( is_post_type_archive() ) {
			$this->set_location( self::L_PT_ARCHIVE );
			$this->set_queried_entity(
				new Entities\Post_Type( $queried_object, $queried_posts, $archive_page_number )
			);
		} elseif ( is_date() ) {
			$this->set_location( self::L_DATE_ARCHIVE );
			$this->set_queried_entity(
				new Entities\Date_Archive(
					$query->get( 'year' ),
					$query->get( 'monthnum' ),
					$query->get( 'day' ),
					$queried_posts,
					$archive_page_number
				)
			);
		} elseif ( is_archive() ) {
			$this->set_location( self::L_ARCHIVE );
			$this->set_queried_entity( null );
		} elseif ( is_404() ) {
			$this->set_location( self::L_404 );
			$this->set_queried_entity(
				new Entities\Page_404()
			);
		} elseif (
			'groups' === $buddypress_api->bp_current_component() &&
			$buddypress_api->groups_get_current_group()
		) {
			$this->set_location( self::L_BP_GROUPS );
			$this->set_queried_entity(
				new Entities\BuddyPress_Group( $buddypress_api->groups_get_current_group() )
			);
		} elseif ( 'profile' === $buddypress_api->bp_current_component() ) {
			$this->set_location( self::L_BP_PROFILE );
			$displayed_user = $buddypress_api->bp_get_displayed_user();

			$this->set_queried_entity(
				new Entities\BuddyPress_Profile( get_user_by( 'id', $displayed_user->id ) )
			);
		} elseif ( // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElseif
			$buddypress_api->bp_current_component() &&
			'profile' !== $buddypress_api->bp_current_component()
		) {
			// Do nothing.
		} elseif ( is_singular() ) {
			$this->set_location( self::L_SINGULAR );
			$post_page_number = $query->get( 'page', 0 );
			$comments_page    = $query->get( 'cpage' );

			if ( \smartcrawl_woocommerce_active() && is_singular( array( 'product' ) ) ) {
				$this->set_queried_entity(
					new Entities\Product(
						$queried_object_id,
						$post_page_number,
						$comments_page
					)
				);
			} else {
				$this->set_queried_entity(
					new Entities\Post(
						$queried_object_id,
						$post_page_number,
						$comments_page
					)
				);
			}
		}
	}

	/**
	 * Checks if home page is set to static page with posts.
	 *
	 * @return boolean
	 */
	private function is_static_posts_page() {
		$page_for_posts = (int) get_option( 'page_on_front' );
		$query          = $this->get_query_context();
		if ( ! $query instanceof \WP_Query ) {
			return false;
		}

		return 'page' === get_option( 'show_on_front' ) && 0 < $page_for_posts && $query->get_queried_object_id() === $page_for_posts;
	}

	/**
	 * Checks if home page is set to latess posts.
	 *
	 * @return boolean
	 */
	private function is_home_posts_page() {
		return is_home() && ( 'posts' === get_option( 'show_on_front' ) || 0 === (int) get_option( 'page_on_front' ) );
	}

	/**
	 * Gets query context
	 *
	 * @return \WP_Query instance
	 */
	public function get_query_context() {
		global $wp_query;

		return $wp_query;
	}

	/**
	 * Gets resolved or simulated location
	 *
	 * @return string Location
	 */
	public function get_location() {
		return $this->location;
	}

	/**
	 * Sets resolved location
	 *
	 * @param string $loc One of the defined location constants.
	 *
	 * @return bool
	 */
	public function set_location( $loc ) {
		$this->location = $loc;

		return ! ! $this->location;
	}

	/**
	 * Sets the query context.
	 *
	 * @deprecated
	 *
	 * @param mixed $qobj Query object.
	 */
	public function set_query_context( $qobj ) {
		$this->mark_deprecated( __METHOD__ );

		return false;
	}

	/**
	 * Simulates a post.
	 *
	 * @deprecated
	 *
	 * @param int $pid Post ID.
	 */
	public function simulate_post( $pid ) {
		$this->mark_deprecated( __METHOD__ );

		return false;
	}

	/**
	 * Simulates a taxonomy term.
	 *
	 * @deprecated
	 *
	 * @param int $term_id Term ID.
	 */
	public function simulate_taxonomy_term( $term_id ) {
		$this->mark_deprecated( __METHOD__ );

		return false;
	}

	/**
	 * Simulates a post type.
	 *
	 * @deprecated
	 *
	 * @param string $post_type Post type.
	 */
	public function simulate_post_type( $post_type ) {
		$this->mark_deprecated( __METHOD__ );
	}

	/**
	 * Simulates a location.
	 *
	 * @deprecated
	 *
	 * @param string $location Location.
	 * @param mixed  $context Context.
	 * @param mixed  $query_context Query context.
	 */
	public function simulate( $location, $context, $query_context = null ) {
		$this->mark_deprecated( __METHOD__ );

		return false;
	}

	/**
	 * Gets the context.
	 *
	 * @deprecated
	 */
	public function get_context() {
		$this->mark_deprecated( __METHOD__ );

		return false;
	}

	/**
	 * Sets the context.
	 *
	 * @deprecated
	 *
	 * @param mixed $pobj Context object.
	 */
	public function set_context( $pobj ) {
		$this->mark_deprecated( __METHOD__ );

		return false;
	}

	/**
	 * Stops the simulation.
	 *
	 * @deprecated
	 */
	public function stop_simulation() {
		$this->mark_deprecated( __METHOD__ );

		return false;
	}

	/**
	 * Resets the environment.
	 *
	 * @deprecated
	 */
	public function reset_env() {
		$this->mark_deprecated( __METHOD__ );
	}

	/**
	 * Checks if the location is singular.
	 *
	 * @deprecated
	 *
	 * @param string $location Location.
	 */
	public function is_singular( $location = false ) {
		$this->mark_deprecated( __METHOD__ );

		return false;
	}

	/**
	 * Marks a method as deprecated.
	 *
	 * @param string $method Method name.
	 */
	private function mark_deprecated( $method ) {
		$class = __CLASS__;
		_deprecated_function( "$class::$method", '2.18.0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}