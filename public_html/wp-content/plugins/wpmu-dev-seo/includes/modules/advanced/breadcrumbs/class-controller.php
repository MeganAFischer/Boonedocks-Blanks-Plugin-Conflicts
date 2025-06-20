<?php
/**
 * Initializes breadcrumbs functionality.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs;

use SmartCrawl\Controllers;
use SmartCrawl\Singleton;
use SmartCrawl\Settings;

/**
 * Breadcrumbs Controller class.
 */
class Controller extends Controllers\Submodule_Controller {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->module_title = __( 'Breadcrumbs', 'wds' );
		$this->event_name   = 'Breadcrumbs';
	}

	/**
	 * Includes methods that runs always.
	 *
	 * @return void
	 */
	protected function always() {
		parent::always();

		if ( function_exists( '\add_shortcode' ) ) {
			add_shortcode( 'smartcrawl_breadcrumbs', array( $this, 'render_shortcode' ) );
			// Keeping old shortcode for backward compatibility.
			add_shortcode( 'smartcrawl_breadcrumb', array( $this, 'render_shortcode' ) );
		}
	}

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	protected function init() {
		parent::init();

		if ( ! empty( $this->options['disable_woo'] ) ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		}

		add_filter( 'smartcrawl_known_macros', array( $this, 'replace_macros' ), 10, 2 );
	}

	/**
	 * Includes methods when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {
		parent::terminate();

		remove_filter( 'smartcrawl_known_macros', array( $this, 'replace_macros' ), 10, 2 );
	}

	/**
	 * Callback for shortcode function.
	 *
	 * @since 3.5.0
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function render_shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'before' => '',
				'after'  => '',
			),
			$atts,
			'smartcrawl_breadcrumbs'
		);

		return $this->render_breadcrumb( $atts['before'], $atts['after'] );
	}

	/**
	 * Render breadcrumb for current page.
	 *
	 * @since 3.5.0
	 *
	 * @param string $before What to show before the breadcrumb.
	 * @param string $after  What to show after the breadcrumb.
	 *
	 * @return string
	 */
	public function render_breadcrumb( $before = '', $after = '' ) {
		if ( ! $this->should_run() ) {
			return '';
		}

		// Front page doesn't need a breadcrumb.
		$builder = $this->get_current_builder();

		// If breadcrumb class is found.
		if ( is_object( $builder ) && method_exists( $builder, 'render' ) ) {
			return $builder->render( $before, $after );
		}
	}

	/**
	 * Get current page builder.
	 *
	 * Get the breadcrumb builder class instance for the
	 * current page.
	 *
	 * @return Builders\Builder|Builders\No
	 *
	 * @since 3.5.0
	 */
	public function get_current_builder() {
		static $builder = null;

		if ( null === $builder ) {
			// Default no breadcrumb builder.
			$builder = Builders\No::get();

			if ( function_exists( '\is_woocommerce' ) && \is_woocommerce() ) {
				// WooCommerce shop, product, category or tag.
				$builder = Builders\Woocommerce::get();
			} elseif ( is_page() || is_home() ) {
				// Normal page.
				$builder = Builders\Pages::get();
			} elseif ( is_single() || is_post_type_archive() ) {
				// Single post or post type archive.
				$builder = Builders\Posts::get();
			} elseif ( is_404() ) {
				// 404 page.
				$builder = Builders\Error_404::get();
			} elseif ( is_search() ) {
				// Search results page.
				$builder = Builders\Search::get();
			} elseif ( is_category() || is_tag() || is_tax() ) {
				// Taxonomy archive pages.
				$builder = Builders\Taxonomies::get();
			} elseif ( is_archive() ) {
				// Post archive pages.
				$builder = Builders\Archives::get();
			}
		}

		return $builder;
	}

	/**
	 * Modify pagination macro values for breadcrumbs.
	 *
	 * If there are no pages, display it as page 1.
	 * See https://incsub.atlassian.net/browse/SMA-1403
	 *
	 * @since 3.5.0
	 * @todo  Improve this method.
	 *
	 * @param array  $macros Macros.
	 * @param string $module Module name.
	 *
	 * @return array
	 */
	public function replace_macros( $macros, $module ) {
		global $wp_query;
		// Only for breadcrumbs.
		if ( 'breadcrumb' === $module ) {
			/* translators: 1: Current page number, 2: Total page number */
			$page_x_of_y = esc_html__( 'Page %1$s of %2$s', 'wds' );
			$max_pages   = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
			if ( empty( $macros['%%pagenumber%%'] ) || empty( $macros['%%pagetotal%%'] ) ) {
				$macros['%%pagenumber%%'] = 1;
				$macros['%%pagetotal%%']  = $max_pages;
			}

			if ( empty( $macros['%%spell_pagenumber%%'] ) || empty( $macros['%%spell_pagetotal%%'] ) ) {
				$macros['%%spell_pagenumber%%'] = \smartcrawl_spell_number( 1 );
				$macros['%%spell_pagetotal%%']  = \smartcrawl_spell_number( $max_pages );
			}
			if ( isset( $macros['%%page%%'] ) && empty( $macros['%%page%%'] ) ) {
				$macros['%%page%%'] = sprintf( $page_x_of_y, 1, $max_pages );
			}
			if ( isset( $macros['%%spell_page%%'] ) && empty( $macros['%%spell_page%%'] ) ) {
				// translators: %1$s Page number, %2$ total pages.
				$macros['%%spell_page%%'] = sprintf( $page_x_of_y, \smartcrawl_spell_number( 1 ), \smartcrawl_spell_number( $max_pages ) );
			}

			// Use custom separator.
			if ( isset( $macros['%%sep%%'] ) ) {
				// translators: %s separator.
				$macros['%%sep%%'] = sprintf(
					'<span class="smartcrawl-breadcrumb-separator">%s</span>',
					esc_attr( Helper::get_separator() )
				);
			}
		}

		return $macros;
	}

	/**
	 * Retrieves breadcrumb label formats.
	 *
	 * @return array
	 */
	private function get_label_formats() {
		$labels = isset( $this->options['labels'] ) ? $this->options['labels'] : array();

		return array(
			array(
				'type'        => 'post',
				'label'       => __( 'Post', 'wds' ),
				'snippets'    => array( 'Category', 'Subcategory' ),
				'placeholder' => __( '%%title%%', 'wds' ),
				'variables'   => array_merge(
					$this->get_macros( 'post' ),
					$this->get_general_macros()
				),
			),
			array(
				'type'        => 'page',
				'label'       => __( 'Page', 'wds' ),
				'snippets'    => array( 'Parent' ),
				'placeholder' => __( '%%title%%', 'wds' ),
				'variables'   => array_merge(
					$this->get_macros( 'page' ),
					$this->get_general_macros()
				),
			),
			array(
				'type'        => 'archive',
				'label'       => __( 'Archive', 'wds' ),
				'title'       => __( 'Archive Page', 'wds' ),
				'snippets'    => array(),
				'placeholder' => __( 'Archives for %%original-title%%', 'wds' ),
				'variables'   => array_merge(
					$this->get_macros( 'archive' ),
					$this->get_general_macros(),
					$this->get_pagination_macros()
				),
			),
			array(
				'type'        => 'search',
				'label'       => __( 'Search', 'wds' ),
				'title'       => __( 'Search Results Page', 'wds' ),
				'snippets'    => array(),
				'placeholder' => __( 'Search for "%%searchphrase%%"', 'wds' ),
				'variables'   => array_merge(
					$this->get_macros( 'search' ),
					$this->get_general_macros(),
					$this->get_pagination_macros()
				),
			),
			array(
				'type'        => '404',
				'label'       => __( '404', 'wds' ),
				'title'       => __( '404 Error Page', 'wds' ),
				'snippets'    => array(),
				'placeholder' => __( '404 Error: page not found', 'wds' ),
				'variables'   => array_merge(
					$this->get_macros( '404' ),
					$this->get_general_macros()
				),
			),
		);
	}


	/**
	 * Get breadcrumb macros.
	 *
	 * @param string $type Breadcrumb type.
	 *
	 * @return array
	 */
	private function get_macros( $type = '' ) {
		$post_type_macros = array(
			'%%id%%'       => __( 'ID', 'wds' ),
			'%%title%%'    => __( 'Title', 'wds' ),
			'%%modified%%' => __( 'Modified Time', 'wds' ),
			'%%date%%'     => __( 'Date', 'wds' ),
			'%%name%%'     => __( 'Author Nicename', 'wds' ),
			'%%userid%%'   => __( 'Author Userid', 'wds' ),
		);

		switch ( $type ) {
			case 'post':
				$post_type_macros['%%category%%'] = __( 'Categories (comma separated)', 'wds' );
				$post_type_macros['%%tag%%']      = __( 'Tags', 'wds' );

				foreach ( $post_type_macros as $macro => $label ) {
					$post_type_macros[ $macro ] = sprintf( 'Post %s', $label );
				}

				return array_merge( $post_type_macros, $this->get_general_macros() );
			case 'page':
				foreach ( $post_type_macros as $macro => $label ) {
					$post_type_macros[ $macro ] = sprintf( 'Page %s', $label );
				}

				return array_merge( $post_type_macros, $this->get_general_macros() );
			case 'archive':
				return array_merge(
					array(
						'%%original-title%%' => __( 'Archive Title ( no prefix )', 'wds' ),
						'%%archive-title%%'  => __( 'Archive Title', 'wds' ),
					),
					$this->get_general_macros()
				);
			case 'search':
				return array_merge(
					array(
						'%%searchphrase%%' => __( 'Search Keyword', 'wds' ),
					),
					$this->get_general_macros()
				);
			default:
				return $this->get_general_macros();
		}
	}

	/**
	 * Get general macros.
	 *
	 * @return array
	 */
	private function get_general_macros() {
		return array(
			'%%sep%%'          => __( 'Separator', 'wds' ),
			'%%sitename%%'     => __( "Site's name", 'wds' ),
			'%%sitedesc%%'     => __( "Site's tagline / description", 'wds' ),
			'%%currenttime%%'  => __( 'Current time', 'wds' ),
			'%%currentdate%%'  => __( 'Current date', 'wds' ),
			'%%currentmonth%%' => __( 'Current month', 'wds' ),
			'%%currentyear%%'  => __( 'Current year', 'wds' ),
		);
	}

	/**
	 * Get pagination macros.
	 *
	 * @since 3.5.0
	 *
	 * @return array
	 */
	private function get_pagination_macros() {
		return array(
			'%%page%%'             => __( 'Current page number (i.e. page 2 of 4)', 'wds' ),
			'%%pagetotal%%'        => __( 'Current page total', 'wds' ),
			'%%pagenumber%%'       => __( 'Current page number', 'wds' ),
			'%%spell_pagenumber%%' => __( 'Current page number, spelled out as numeral in English', 'wds' ),
			'%%spell_pagetotal%%'  => __( 'Current page total, spelled out as numeral in English', 'wds' ),
			'%%spell_page%%'       => __( 'Current page number, spelled out as numeral in English', 'wds' ),
		);
	}

	/**
	 * Sanitizes submitted options
	 *
	 * @param array $input Raw input.
	 *
	 * @return bool True if sanitized successfully, otherwise false.
	 */
	public function sanitize_options( $input ) {
		$old_options = $this->options;

		if ( isset( $input['active'] ) ) {
			$active = boolval( $input['active'] );

			if ( empty( $this->options['active'] ) || $active !== $this->options['active'] ) {
				$this->options['active'] = $active;

				return true;
			}

			unset( $input['active'] );
		}

		if ( empty( $input ) ) {
			return true;
		}

		// Text fields.
		$inputs = array( 'separator', 'custom_sep', 'prefix', 'home_label' );
		foreach ( $inputs as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$this->options[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		// Labels.
		if ( ! isset( $this->options['labels'] ) ) {
			$this->options['labels'] = array();
		}

		$labels = array( 'post', 'page', 'archive', 'search', '404' );
		foreach ( $labels as $key ) {
			if ( isset( $input['labels'][ $key ] ) ) {
				$this->options['labels'][ $key ] = \smartcrawl_sanitize_preserve_macros( $input['labels'][ $key ] );
			}
		}

		// Boolean fields.
		$booleans = array( 'home_trail', 'hide_post_title', 'add_prefix', 'disable_woo' );
		foreach ( $booleans as $key ) {
			$this->options[ $key ] = (bool) $input[ $key ];
		}

		return true;
	}

	/**
	 * Returns localize script data.
	 *
	 * @return array
	 */
	public function localize_script_args() {
		$args = array(
			'active'     => ! empty( $this->options['active'] ),
			'separators' => \smartcrawl_get_separators(),
			'formats'    => $this->get_label_formats(),
		);

		$options = array(
			'add_prefix'      => false,
			'home_trail'      => false,
			'hide_post_title' => false,
			'disable_woo'     => false,
			'separator'       => 'greater-than',
			'prefix'          => '',
			'custom_sep'      => '',
			'labels'          => array(),
			'home_label'      => '',
		);

		foreach ( $options as $key => $value ) {
			if ( isset( $this->options[ $key ] ) ) {
				$options[ $key ] = $this->options[ $key ];
			}
		}

		$args['options'] = $options;

		return $args;
	}

	/**
	 * Outputs submodule content to dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_content() {
		$active = (bool) $this->should_run();
		?>

		<div class="wds-separator-top wds-draw-left-padded">
			<small>
				<strong><?php esc_html_e( 'Breadcrumbs', 'wds' ); ?></strong>
			</small>

			<?php if ( $active ) : ?>

				<div class="wds-right">
					<span class="sui-tag wds-right sui-tag-sm sui-tag-blue"><?php esc_html_e( 'Active', 'wds' ); ?></span>
				</div>

			<?php else : ?>

				<p>
					<small><?php esc_html_e( 'Enhance your site\'s user experience and crawlability by adding breadcrumbs to your posts, pages, archives, and products.', 'wds' ); ?></small>
				</p>

				<button
					type="button"
					data-module="<?php echo esc_attr( $this->parent->module_id ); ?>"
					data-submodule="<?php echo esc_attr( $this->module_id ); ?>"
					class="wds-activate-submodule wds-disabled-during-request sui-button sui-button-blue">
					<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>

			<?php endif; ?>
		</div>

		<?php
	}
}