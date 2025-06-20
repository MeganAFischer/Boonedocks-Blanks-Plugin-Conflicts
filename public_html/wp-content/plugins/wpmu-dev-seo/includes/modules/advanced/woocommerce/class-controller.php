<?php
/**
 * Manages WooCommerce related things.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\WooCommerce;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Controllers;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * WooCommerce controller.
 */
class Controller extends Controllers\Submodule_Controller {

	use Singleton;

	/**
	 * Sanitize callbacks for options.
	 *
	 * @var string[]
	 */
	private $sanitizers = array(
		'rm_gen_tag'          => 'boolval',
		'enable_og'           => 'boolval',
		'add_robots'          => 'boolval',
		'shop_schema'         => 'boolval',
		'noindex_hidden_prod' => 'boolval',
		'brand'               => 'sanitize_text_field',
		'global_id'           => 'sanitize_text_field',
	);

	/**
	 * Constructor.
	 *
	 * @since 3.3.0
	 */
	protected function __construct() {
		$this->module_title = __( 'WooCommerce SEO', 'wds' );
		$this->event_name   = 'WooCommerce SEO';
	}

	/**
	 * Checks if current module is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return \smartcrawl_woocommerce_active();
	}

	/**
	 * Includes methods that runs always.
	 *
	 * @return void
	 */
	protected function always() {
		parent::always();

		add_filter( 'woocommerce_structured_data_product', array( $this, 'remove_woocommerce_product_schema' ), 10, 2 );
		add_action( 'smartcrawl_admin_notices', array( $this, 'display_notice' ) );
		add_action( "update_option_{$this->parent->module_name}", array( $this, 'invalidate_sitemap_cache' ), 10, 2 );
		add_filter( 'wds_seo_metabox_permission_level', array( $this, 'include_woocommerce_roles' ) );
		add_filter( 'wds_seo_metabox_301_permission_level', array( $this, 'include_woocommerce_roles' ) );
		add_filter( 'wds_urlmetrics_metabox_permission_level', array( $this, 'include_woocommerce_roles' ) );
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		parent::init();

		add_filter( 'get_the_generator_html', array( $this, 'remove_generator_tag' ), - 10, 2 );
		add_filter( 'get_the_generator_xhtml', array( $this, 'remove_generator_tag' ), - 10, 2 );
		add_filter( 'woocommerce_structured_data_product', array( $this, 'add_brand_to_woocommerce_schema' ), 15, 2 );
		add_filter( 'smartcrawl_robots_txt_content', array( $this, 'add_rules_to_robots_txt' ) );
		add_filter( 'smartcrawl_update_submodule_' . $this->module_id, array( $this, 'update_response' ) );

		Global_Id::get()->set_options( $this->options );
		Global_Id::get()->run();

		$this->remove_hidden_products_from_sitemap();
	}

	/**
	 * Includes methods when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {
		parent::terminate();

		remove_filter( 'get_the_generator_html', array( $this, 'remove_generator_tag' ), - 10, 2 );
		remove_filter( 'get_the_generator_xhtml', array( $this, 'remove_generator_tag' ), - 10, 2 );
		remove_filter( 'woocommerce_structured_data_product', array( $this, 'add_brand_to_woocommerce_schema' ), 15, 2 );
		remove_action( 'smartcrawl_robots_txt_content', array( $this, 'add_rules_to_robots_txt' ) );
	}

	/**
	 * Displays admin notice.
	 *
	 * @return void
	 */
	public function display_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $this->should_run() ) {
			return;
		}

		$hide_disables = Settings::get_setting( 'hide_disables', true );

		if ( ! $this->parent->should_run() && $hide_disables ) {
			return;
		}

		$key            = 'try-woocommerce';
		$dismissed_msgs = get_user_meta( get_current_user_id(), 'wds_dismissed_messages', true );
		$is_dismissed   = \smartcrawl_get_array_value( $dismissed_msgs, $key ) === true;

		if ( $is_dismissed ) {
			return;
		}
		?>

		<div
			class="notice-info notice is-dismissible wds-native-dismissible-notice"
			data-message-key="<?php echo esc_attr( $key ); ?>"
		>
			<p><strong><?php esc_html_e( 'Improve your WooCommerce SEO', 'wds' ); ?></strong></p>

			<p style="margin-bottom:15px;">
				<?php
				printf(
					/* translators: %s: User's first name */
					esc_html__( 'Hey, %s! It looks like you’re using WooCommerce. Did you know that you can improve your site’s SEO ranking with our WooCommerce SEO settings?', 'wds' ),
					esc_html( \SmartCrawl\Models\User::current()->get_first_name() )
				);
				?>
			</p>
			<a
				href="<?php echo esc_attr( Admin_Settings::admin_url( Settings::ADVANCED_MODULE ) . '&tab=' . $this->module_name ); ?>"
				class="button button-primary"
			>
				<?php esc_html_e( 'Activate WooCommerce SEO', 'wds' ); ?>
			</a>
			<a
				href="#"
				class="wds-native-dismiss"
				style="font-weight: 400;color: #2271b1;"
			>
				<?php esc_html_e( 'Not now', 'wds' ); ?>
			</a>
			<p></p>
		</div>
		<?php
	}

	/**
	 * Removes hidden products from sitemap.
	 *
	 * @return void
	 */
	public function remove_hidden_products_from_sitemap() {
		$noindex_hidden_prod = \smartcrawl_get_array_value( $this->get_options(), 'noindex_hidden_prod' );
		if ( $noindex_hidden_prod ) {
			add_filter( 'wds_sitemap_ignored_product_ids', array( $this, 'ignore_hidden_products' ) );
			add_filter( 'wds_news_sitemap_ignored_product_ids', array( $this, 'ignore_hidden_products' ) );
		}
	}

	/**
	 * Ignores hidden products.
	 *
	 * @param int[] $ignored_ids Ignored product IDs.
	 *
	 * @return array
	 */
	public function ignore_hidden_products( $ignored_ids ) {
		$product_visibility_terms = wc_get_product_visibility_term_ids();
		$product_ids              = get_posts(
			array(
				'post_type'   => 'product',
				'fields'      => 'ids',
				'numberposts' => - 1,
				'tax_query'   => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'term_taxonomy_id',
						'terms'    => array( $product_visibility_terms['exclude-from-catalog'] ),
					),
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'term_taxonomy_id',
						'terms'    => array( $product_visibility_terms['exclude-from-search'] ),
					),
				),
			)
		);
		$product_ids              = ! empty( $product_ids ) && is_array( $product_ids )
			? $product_ids
			: array();
		$ignored_ids              = ! empty( $ignored_ids ) && is_array( $ignored_ids )
			? $ignored_ids
			: array();

		return array_merge( $ignored_ids, $product_ids );
	}

	/**
	 * Removes WooCommerce generator filters.
	 *
	 * @param string $gen The HTML markup output to wp_head().
	 * @return mixed
	 */
	public function remove_generator_tag( $gen ) {
		$should_remove = (bool) \smartcrawl_get_array_value( $this->get_options(), 'rm_gen_tag' );

		if ( $should_remove ) {
			remove_filter( 'get_the_generator_html', 'wc_generator_tag' );
			remove_filter( 'get_the_generator_xhtml', 'wc_generator_tag' );
		}

		return $gen;
	}

	/**
	 * Removes WooCommerce product schema.
	 *
	 * @param array       $markup  Schema.
	 * @param \WC_Product $product Product.
	 *
	 * @return array
	 */
	public function remove_woocommerce_product_schema( $markup, $product ) {
		if ( $this->is_schema_disabled() ) {
			return $markup;
		}

		$schema_utils = \SmartCrawl\Schema\Utils::get();
		$product_post = get_post( $product->get_id() );
		$schema_types = $schema_utils->get_custom_schema_types( $product_post );

		foreach ( $schema_types as $type => $schema ) {
			if ( 'Product' === $type ) {
				return array();
			}
		}

		return $markup;
	}

	/**
	 * Adds brand to WooCommerce schema.
	 *
	 * @param array       $schema  Schema.
	 * @param \WC_Product $product Product.
	 *
	 * @return array
	 */
	public function add_brand_to_woocommerce_schema( $schema, $product ) {
		$brand = $this->get_brand( $product );
		if ( empty( $schema ) || empty( $brand ) ) {
			// We may have already removed the schema or there's no brand available.
			return $schema;
		}

		$schema['brand'] = array(
			'@type' => 'Brand',
			'name'  => $brand->name,
			'url'   => get_term_link( $brand ),
		);

		return $schema;
	}

	/**
	 * Checks if schema is disabled.
	 *
	 * @return bool
	 */
	private function is_schema_disabled() {
		$social = Settings::get_component_options( Settings::COMP_SOCIAL );

		return ! empty( $social['disable-schema'] ) || ! Admin_Settings::is_tab_allowed( Settings::TAB_SCHEMA );
	}

	/**
	 * The following function belongs inside a SmartCrawl\Entities\Product class.
	 *
	 * @param \WC_Product $product Product.
	 *
	 * @return \WP_Term|bool
	 */
	public function get_brand( $product ) {
		$brand = \smartcrawl_get_array_value( $this->get_options(), 'brand' );
		if ( empty( $brand ) ) {
			return false;
		}

		$brands = get_the_terms( $product->get_id(), $brand );

		return is_wp_error( $brands ) || empty( $brands[0] )
			? false
			: $brands[0];
	}

	/**
	 * Adds rules to Robots.txt.
	 *
	 * @param string $contents Robots.txt file content.
	 *
	 * @return string
	 */
	public function add_rules_to_robots_txt( $contents ) {
		$enabled = \smartcrawl_get_array_value( $this->get_options(), 'add_robots' );

		if ( ! $enabled ) {
			return $contents;
		}

		$parts = array(
			'Disallow: /*add-to-cart=*',
		);

		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
			$page_id = wc_get_page_id( $page );
			if ( $page_id > 0 ) {
				$page_permalink      = wc_get_page_permalink( $page );
				$page_permalink_part = str_replace( home_url( '/' ), '/', $page_permalink );
				$parts[]             = "Disallow: $page_permalink_part";
			}
		}

		if ( $parts ) {
			$contents .= "\n\n" . join( "\n", $parts );
		}

		return $contents;
	}

	/**
	 * Adds rules to Robots.txt.
	 *
	 * @param array $response Response data.
	 *
	 * @return array
	 */
	public function update_response( $response ) {
		$robots_controller = \SmartCrawl\Modules\Advanced\Robots\Controller::get();

		$response[] = array(
			'name'  => $robots_controller->module_name,
			'value' => array( 'robots_output' => $robots_controller->get_content() ),
		);

		return $response;
	}

	/**
	 * Invalidates sitemap cache when options are updated.
	 *
	 * @param array $old_option Old options.
	 * @param array $new_option New options.
	 *
	 * @return void
	 */
	public function invalidate_sitemap_cache( $old_option, $new_option ) {
		$old_woo_status     = \smartcrawl_get_array_value( $old_option, 'active' );
		$old_noindex_value  = \smartcrawl_get_array_value( $old_option, 'noindex_hidden_prod' );
		$old_noindex_status = $old_woo_status && $old_noindex_value;

		$new_woo_status     = \smartcrawl_get_array_value( $new_option, 'active' );
		$new_noindex_value  = \smartcrawl_get_array_value( $new_option, 'noindex_hidden_prod' );
		$new_noindex_status = $new_woo_status && $new_noindex_value;

		if ( $old_noindex_status !== $new_noindex_status ) {
			\SmartCrawl\Sitemaps\Cache::get()->invalidate();
		}
	}

	/**
	 * Add Woo role to roles list.
	 *
	 * @since 3.6.3
	 *
	 * @param array $default_roles Role list.
	 *
	 * @return array
	 */
	public function include_woocommerce_roles( $default_roles ) {
		if ( \smartcrawl_woocommerce_active() ) {
			$default_roles['manage_woocommerce'] = __( 'Shop Manager (and up)', 'wds' );
		}

		return $default_roles;
	}

	/**
	 * Retrieves brand options.
	 *
	 * @return array
	 */
	private function get_brand_options() {
		$options            = array(
			'' => esc_html__( 'None', 'wds' ),
		);
		$product_taxonomies = get_object_taxonomies( 'product', 'objects' );
		$excluded           = array(
			'product_shipping_class',
			'product_type',
			'product_visibility',
		);
		foreach ( $product_taxonomies as $product_taxonomy ) {
			if ( in_array( $product_taxonomy->name, $excluded, true ) ) {
				continue;
			}

			$options[ $product_taxonomy->name ] = $product_taxonomy->label;
		}

		return $options;
	}

	/**
	 * Checks if Opengraph setting is enabled.
	 *
	 * @return bool
	 */
	private function is_opengraph_enabled() {
		$options                = Settings::get_options();
		$social_enabled         = (bool) \smartcrawl_get_array_value( $options, 'social' );
		$og_active              = (bool) \smartcrawl_get_array_value( $options, 'og-enable' );
		$og_active_for_products = (bool) \smartcrawl_get_array_value( $options, 'og-active-product' );

		return $social_enabled && $og_active && $og_active_for_products;
	}

	/**
	 * Retrieves admin url to social page.
	 *
	 * @return string
	 */
	private function get_social_url() {
		$options        = Settings::get_options();
		$social_enabled = (bool) \smartcrawl_get_array_value( $options, 'social' );
		$og_active      = (bool) \smartcrawl_get_array_value( $options, 'og-enable' );

		if ( ! $social_enabled || ! $og_active ) {
			return Admin_Settings::admin_url( Settings::TAB_SOCIAL );
		} else {
			return Admin_Settings::admin_url( Settings::TAB_ONPAGE ) . '&tab=tab_post_types';
		}
	}

	/**
	 * Checks if schema is enabled.
	 *
	 * @return bool
	 */
	private function is_schema_enabled() {
		$social = Settings::get_component_options( Settings::COMP_SOCIAL );

		return empty( $social['disable-schema'] );
	}

	/**
	 * Checks if social is allowed.
	 *
	 * @return bool
	 */
	private function is_social_allowed() {
		$options        = Settings::get_options();
		$social_enabled = (bool) \smartcrawl_get_array_value( $options, 'social' );
		$og_active      = (bool) \smartcrawl_get_array_value( $options, 'og-enable' );

		if ( ! $social_enabled || ! $og_active ) {
			return Admin_Settings::is_tab_allowed( Settings::TAB_SOCIAL );
		} else {
			return Admin_Settings::is_tab_allowed( Settings::TAB_ONPAGE );
		}
	}

	/**
	 * Checks if schema is allowed.
	 *
	 * @return bool
	 */
	private function is_schema_allowed() {
		return Admin_Settings::is_tab_allowed( Settings::TAB_SCHEMA );
	}

	/**
	 * Returns localize script data.
	 *
	 * @return array
	 */
	public function localize_script_args() {
		$args = array(
			'active'         => ! empty( $this->options['active'] ),
			'brand_opts'     => $this->get_brand_options(),
			'social_url'     => $this->get_social_url(),
			'og_enabled'     => $this->is_opengraph_enabled(),
			'social_allowed' => $this->is_social_allowed(),
			'schema_enabled' => $this->is_schema_enabled(),
			'schema_allowed' => $this->is_schema_allowed(),
			'schema_url'     => Admin_Settings::admin_url( Settings::TAB_SCHEMA ),
			'options'        => array(),
		);

		foreach ( $this->sanitizers as $key => $sanitizer ) {
			$args['options'][ $key ] = call_user_func( $sanitizer, isset( $this->options[ $key ] ) ? $this->options[ $key ] : '' );
		}

		return $args;
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

		foreach ( $this->sanitizers as $option => $sanitizer ) {
			$this->options[ $option ] = call_user_func( $sanitizer, isset( $input[ $option ] ) ? $input[ $option ] : '' );
		}

		return true;
	}

	/**
	 * Sets submodule options.
	 *
	 * @param array $options Submodule options.
	 *
	 * @return void
	 */
	public function set_options( $options = array() ) {
		$this->options = $options;
	}
}