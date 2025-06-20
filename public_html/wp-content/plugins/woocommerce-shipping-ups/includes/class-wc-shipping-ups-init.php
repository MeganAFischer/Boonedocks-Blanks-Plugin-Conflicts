<?php
/**
 * The main plugin class.
 *
 * @package WC_Shipping_UPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WooCommerce\Shipping\UPS\Blocks_Integration;
use WooCommerce\UPS\Store_API_Extension;

/**
 * WC_Shipping_UPS_Init Class
 */
class WC_Shipping_UPS_Init {

	/**
	 * Instance of this class.
	 *
	 * @var WC_Shipping_UPS_Init|null
	 */
	private static ?WC_Shipping_UPS_Init $instance = null;

	/**
	 * Get the class instance.
	 */
	public static function get_instance(): WC_Shipping_UPS_Init {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin's public actions.
	 */
	public function __construct() {
		if ( class_exists( 'WC_Shipping_Method' ) ) {
			add_action( 'admin_init', array( $this, 'maybe_install' ), 5 );
			add_action( 'after_setup_theme', array( $this, 'load_textdomain' ) );
			add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			add_action( 'woocommerce_shipping_init', array( $this, 'includes' ) );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'add_method' ) );
			add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
			add_action( 'wp_ajax_ups_dismiss_upgrade_notice', array( $this, 'dismiss_upgrade_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );
			add_action( 'woocommerce_blocks_loaded', array( $this, 'register_blocks_integration' ) );
			add_action( 'woocommerce_blocks_loaded', array( $this, 'extend_store_api' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'wc_deactivated' ) );
		}
	}

	/**
	 * Register blocks integration.
	 */
	public function register_blocks_integration() {
		require_once WC_SHIPPING_UPS_PLUGIN_DIR . '/includes/class-blocks-integration.php';
		add_action(
			'woocommerce_blocks_cart_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new Blocks_Integration() );
			}
		);
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new Blocks_Integration() );
			}
		);
	}

	/**
	 * Extend the store API.
	 */
	public function extend_store_api() {
		require_once WC_SHIPPING_UPS_PLUGIN_DIR . '/includes/class-store-api-extension.php';
		Store_API_Extension::init();
	}

	/**
	 * Include needed files.
	 */
	public function includes() {
		include_once WC_SHIPPING_UPS_PLUGIN_DIR . '/includes/class-wc-shipping-ups-privacy.php';
		include_once WC_SHIPPING_UPS_PLUGIN_DIR . '/includes/class-wc-shipping-ups.php';
	}

	/**
	 * Assets to enqueue in admin.
	 */
	public function assets() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( sanitize_title( __( 'woocommerce', 'woocommerce-shipping-ups' ) ) . '_page_wc-settings' === $screen_id ) {
			/*
			 * PHPStan seems to have issues finding constants when they're defined with functions
			 *
			 * @link https://github.com/phpstan/phpstan/issues/6662
			 * @phpstan-ignore constant.notFound
			 */
			wp_enqueue_style( 'ups-admin-css', WC_SHIPPING_UPS_PLUGIN_URL . '/assets/css/ups-admin.css', '', WC_SHIPPING_UPS_VERSION );
			/*
			 * PHPStan seems to have issues finding constants when they're defined with functions
			 *
			 * @link https://github.com/phpstan/phpstan/issues/6662
			 * @phpstan-ignore constant.notFound
			 */
			wp_register_script( 'ups-admin-js', WC_SHIPPING_UPS_PLUGIN_URL . '/assets/js/ups-admin.js', array( 'jquery', 'jquery-ui-sortable' ), WC_SHIPPING_UPS_VERSION, true );
		}
	}

	/**
	 * Assets to enqueue on the frontend.
	 */
	public function frontend_assets() {
		/*
		 * PHPStan seems to have issues finding constants when they're defined with functions
		 *
		 * @link https://github.com/phpstan/phpstan/issues/6662
		 * @phpstan-ignore constant.notFound
		 */
		wp_register_script( 'ups-checkout-js', WC_SHIPPING_UPS_PLUGIN_URL . '/assets/js/checkout.js', array( 'jquery' ), WC_SHIPPING_UPS_VERSION, true );

		wp_localize_script(
			'ups-checkout-js',
			'wc_ups_checkout_params',
			array(
				'store_api_namespace'  => Store_API_Extension::IDENTIFIER,
				'is_wc_block_checkout' => has_block( 'woocommerce/checkout' ),
				'strings'              => array(
					'button_apply_address' => __( 'Updating address...', 'woocommerce-shipping-ups' ),
				),
			)
		);

		if ( is_checkout() ) {
			wp_enqueue_script( 'ups-checkout-js' );
		}
	}

	/**
	 * Add UPS shipping method.
	 *
	 * @param array $methods Shipping methods.
	 *
	 * @return array Shipping methods.
	 * @since 1.0.0
	 */
	public function add_method( array $methods ): array {
		$methods['ups'] = 'WC_Shipping_UPS';

		return $methods;
	}

	/**
	 * Localisation.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'woocommerce-shipping-ups', false, basename( WC_SHIPPING_UPS_PLUGIN_DIR ) . '/languages/' );
	}

	/**
	 * Declare High-Performance Order Storage (HPOS) compatibility
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#declaring-extension-incompatibility
	 *
	 * @return void
	 */
	public function declare_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', 'woocommerce-shipping-ups/woocommerce-shipping-ups.php' );
		}
	}

	/**
	 * Plugin page links.
	 *
	 * @param array $links Plugin links.
	 *
	 * @return array Plugin links.
	 * @since 1.0.0
	 */
	public function plugin_links( array $links ): array {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=ups' ) . '">' . __( 'Settings', 'woocommerce-shipping-ups' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Plugin page links to support and documentation
	 *
	 * @param array  $links List of plugin links.
	 * @param string $file  Current file.
	 *
	 * @return array
	 * @since 3.x
	 */
	public function plugin_row_meta( array $links, string $file ): array {
		if ( plugin_basename( __FILE__ ) === $file ) {

			/**
			 * Filter the UPS documentation URL.
			 *
			 * @since 3.2.26
			 */
			$docs_url = apply_filters( 'woocommerce_shipping_ups_docs_url', 'https://docs.woocommerce.com/document/ups-shipping' );

			/**
			 * Filter the UPS support URL.
			 *
			 * @since 3.2.26
			 */
			$support_url = apply_filters( 'woocommerce_shipping_ups_support_url', 'https://woocommerce.com/my-account/create-a-ticket?select=18665' );

			$row_meta = array(
				'docs'    => '<a href="' . esc_url( $docs_url ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-shipping-ups' ) ) . '">' . __( 'Docs', 'woocommerce-shipping-ups' ) . '</a>',
				'support' => '<a href="' . esc_url( $support_url ) . '" title="' . esc_attr( __( 'Open a support request at WooCommerce.com', 'woocommerce-shipping-ups' ) ) . '">' . __( 'Support', 'woocommerce-shipping-ups' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	/**
	 * WooCommerce not installed notice.
	 */
	public function wc_deactivated() {
		/* translators: %s: WooCommerce link. */
		echo '<div class="error"><p>' . sprintf( esc_html__( 'WooCommerce UPS Shipping requires %s to be installed and active.', 'woocommerce-shipping-ups' ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' ) . '</p></div>';
	}

	/**
	 * Checks the plugin version.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 *
	 * @return void
	 */
	public function maybe_install() {
		// Only need to do this for versions less than 3.2.0 to migrate settings
		// to shipping zone instance.
		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
		if ( ! $doing_ajax && ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'wc_ups_version' ), '3.2.0', '<' ) ) {

			$this->install();
		}
	}

	/**
	 * Update/migration script.
	 *
	 * @since   3.2.0
	 * @version 3.2.0
	 */
	public function install() {
		// Get all saved settings and cache it.
		$ups_settings = get_option( 'woocommerce_ups_settings', false );

		// Settings exists.
		if ( $ups_settings ) {
			global $wpdb;

			// Unset un-needed settings.
			unset( $ups_settings['enabled'] );
			unset( $ups_settings['availability'] );
			unset( $ups_settings['countries'] );

			// First add it to the "rest of the world" zone when no ups instance.
			if ( ! $this->is_zone_has_ups( 0 ) ) {

				// Add a new instance to the "rest of the world" zone.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery --- Reason: Direct query is required here.
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO {$wpdb->prefix}woocommerce_shipping_zone_methods ( zone_id, method_id, method_order, is_enabled ) VALUES ( %d, %s, %d, %d )",
						0,
						'ups',
						1,
						1
					)
				);

				// Add settings to the newly created instance to options table.
				$instance = $wpdb->insert_id;
				add_option( 'woocommerce_ups_' . $instance . '_settings', $ups_settings );
			}

			update_option( 'woocommerce_ups_show_upgrade_notice', 'yes' );
		}

		update_option( 'wc_ups_version', WC_SHIPPING_UPS_VERSION );
	}

	/**
	 * Show the user a notice for plugin updates.
	 *
	 * @since 3.2.0
	 */
	public function upgrade_notice() {
		$show_notice = get_option( 'woocommerce_ups_show_upgrade_notice' );

		if ( 'yes' !== $show_notice ) {
			return;
		}

		// phpcs:ignore WordPress.WP.Capabilities.Unknown --- Reason: This is a Woo Core capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$query_args      = array(
			'page' => 'wc-settings',
			'tab'  => 'shipping',
		);
		$zones_admin_url = add_query_arg( $query_args, get_admin_url() . 'admin.php' );
		?>
		<div class="notice notice-success is-dismissible wc-ups-notice">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
					/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( 'UPS now supports shipping zones. The zone settings were added to a new UPS method on the "Rest of the World" Zone. See the zones %1$shere%2$s ', 'woocommerce-shipping-ups' ),
						'<a href="' . esc_url( $zones_admin_url ) . '">',
						'</a>'
					)
				);
				?>
			</p>
		</div>

		<script type="application/javascript">
			jQuery( '.notice.wc-ups-notice' ).on( 'click', '.notice-dismiss', function () {
				wp.ajax.post( 'ups_dismiss_upgrade_notice' );
			} );
		</script>
		<?php
	}

	/**
	 * Turn of the dismisable upgrade notice.
	 *
	 * @since 3.2.0
	 */
	public function dismiss_upgrade_notice() {
		update_option( 'woocommerce_ups_show_upgrade_notice', 'no' );
	}

	/**
	 * Helper method to check whether given zone_id has ups method instance.
	 *
	 * @param int $zone_id Zone ID.
	 *
	 * @return bool True if given zone_id has ups method instance
	 * @since 4.4.0
	 */
	public function is_zone_has_ups( int $zone_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery --- Reason: Direct query is required here.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(instance_id) FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id = 'ups' AND zone_id = %d",
				$zone_id
			)
		) > 0;
	}
}
