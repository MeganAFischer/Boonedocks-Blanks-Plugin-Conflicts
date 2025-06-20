<?php
/**
 * Detects conflicted plugins.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Controllers\Controller;
use SmartCrawl\Settings as SC_Settings;
use SmartCrawl\Controllers\Assets;
use SmartCrawl\Singleton;

/**
 * Conflict Detector controller
 */
class Conflict_Detector extends Controller {

	use Singleton;

	const ID = 'wds-conflict-detector';

	/**
	 * List of plugins which might be conflicted with SmartCrawl.
	 *
	 * @var array
	 */
	private $conflicts;

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'admin_init', array( $this, 'get_conflicted_plugins' ) );

		add_action( 'network_admin_notices', array( $this, 'admin_notices' ) );

		if ( ! is_multisite() || is_main_site() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_smartcrawl_deactivate_plugin', array( $this, 'deactivate_plugin' ) );
	}

	/**
	 * Manages admin notices.
	 *
	 * @return void
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! self::is_smartcrawl_page() || self::is_settings_page() ) {
			return;
		}

		$has_conflicts = (bool) $this->get_active_conflicts();

		if ( ! $has_conflicts ) {
			return;
		}

		$dismissed_messages   = get_user_meta( get_current_user_id(), 'wds_dismissed_messages', true );
		$is_message_dismissed = \smartcrawl_get_array_value( $dismissed_messages, self::ID ) === true;

		if ( $is_message_dismissed ) {
			return;
		}

		?>
		<div
			class="notice-warning notice is-dismissible wds-native-dismissible-notice"
			data-message-key="<?php echo esc_attr( self::ID ); ?>"
		>
			<p>
			<?php
			$referer_page       = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$settings_admin_url = add_query_arg( 'referer', $referer_page, Admin_Settings::admin_url( SC_Settings::TAB_SETTINGS ) );

			echo wp_kses_post(
				sprintf(
					/* translators: 1, 2: strong tag, 3, 4: anchor tag, 5: plugin title */
					esc_html__( '%1$sWe\'ve detected one or more SEO plugins on your site.%2$s To avoid SEO issues, please disable the conflicting plugin(s) or select specific %1$s%5$s%2$s modules to use alongside other plugins on the %3$sSettings page%4$s.', 'wds' ),
					'<strong>',
					'</strong>',
					sprintf( '<a href="%s">', $settings_admin_url ),
					'</a>',
					\smartcrawl_get_plugin_title()
				)
			);
			?>
				</p>
		</div>

		<?php
	}

	/**
	 * Registers script and style files.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		if ( ! self::is_settings_page() ) {
			return;
		}

		wp_localize_script(
			Assets::SETTINGS_PAGE_JS,
			'_wds_conflicts',
			array(
				'plugins' => self::get_active_conflicts(),
			)
		);
	}

	/**
	 * Checks if current page is SmartCrawl page.
	 *
	 * @return bool
	 */
	private function is_smartcrawl_page() {
		global $pagenow;

		if ( 'admin.php' !== $pagenow ) {
			return false;
		}

		$page = (string) \smartcrawl_get_array_value( $_GET, 'page' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return strpos( $page, 'wds_' ) === 0 || strpos( $page, 'wds-' ) === 0;
	}

	/**
	 * Checks if current page is SmartCrawl Settings page.
	 *
	 * @return bool
	 */
	public function is_settings_page() {
		if ( ! self::is_smartcrawl_page() ) {
			return false;
		}

		$page = (string) \smartcrawl_get_array_value( $_GET, 'page' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return SC_Settings::TAB_SETTINGS === $page;
	}

	/**
	 * Populates conflicted plugins.
	 */
	public function get_conflicted_plugins() {
		$available_conflicts = array(
			'wordpress-seo/wp-seo.php',
			'wordpress-seo-premium/wp-seo-premium.php',
			'seo-by-rank-math/rank-math.php',
			'seo-by-rank-math-pro/rank-math-pro.php',
			'all-in-one-seo-pack/all_in_one_seo_pack.php',
			'all-in-one-seo-pack-pro/all_in_one_seo_pack_pro.php',
			'wp-seopress/seopress.php',
			'wp-seopress-pro/seopress-pro.php',
			'autodescription.php',
			'slim-seo/slim-seo.php',
			'wp-meta-seo/wp-meta-seo.php',
			'schema-and-structured-data-for-wp/schema.php',
			'schema/schema.php',
			'wp-seo-structured-data-schema/wp-seo-structured-data-schema.php',
			'wonderm00ns-simple-facebook-open-graph-tags/wonderm00ns-open-graph.php',
			'opengraph/opengraph.php',
			'google-sitemap-generator/sitemap.php',
			'google-sitemap-generator-pro/sitemap-pro.php',
			'xml-sitemap-feed/xml-sitemap.php',
			'simple-sitemap/simple-sitemap.php',
			'redirection/redirection.php',
			'redirection-pro/redirection-pro.php',
			'eps-301-redirects/eps-301-redirects.php',
			'safe-redirect-manager/safe-redirect-manager.php',
			'quick-pagepost-redirect-plugin/quick-redirect.php',
			'wp-robots-txt/wp-robots-txt.php',
			'pc-robotstxt/pc-robotstxt.php',
			'better-robots-txt/better-robots-txt.php',
			'breadcrumb-navxt/breadcrumb_navxt.php',
			'woocommerce-breadcrumbs/woocommerce-breadcrumbs.php',
			'breadcrumb/breadcrumb.php',
			'flexy-breadcrumb/flexy-breadcrumb.php',
			'jetpack/jetpack.php',
			'squirrly-seo/squirrly.php',
		);

		$dependencies = array(
			'wordpress-seo/wp-seo.php'             => array( 'wordpress-seo-premium/wp-seo-premium.php' ),
			'wp-seopress/seopress.php'             => array( 'wp-seopress-pro/seopress-pro.php' ),
			'google-sitemap-generator/sitemap.php' => array( 'google-sitemap-generator-pro/sitemap-pro.php' ),
		);

		$installed_plugins = get_plugins();

		foreach ( $available_conflicts as $conflict ) {
			if ( ! empty( $installed_plugins[ $conflict ] ) && ! empty( $installed_plugins[ $conflict ]['Name'] ) ) {
				$this->conflicts[ $conflict ] = array(
					'name' => $installed_plugins[ $conflict ]['Name'],
				);
			}

			if ( ! empty( $dependencies[ $conflict ] ) ) {
				$this->conflicts[ $conflict ]['deps'] = $dependencies[ $conflict ];
			}
		}
	}

	/**
	 * Retrieves conflicted plugins.
	 *
	 * @return array
	 */
	private function get_active_conflicts() {
		$conflicts = array();

		foreach ( $this->conflicts as $plugin => $info ) {
			if ( is_plugin_active_for_network( $plugin ) ) {
				$conflicts[ $plugin ] = array(
					'name'    => $info['name'],
					'network' => true,
				);
			} elseif ( is_plugin_active( $plugin ) ) {
				$conflicts[ $plugin ] = array(
					'name' => $info['name'],
				);
			}
		}

		return $conflicts;
	}

	/**
	 * Ajax handler to deactivate a plugin.
	 *
	 * @return void
	 */
	public function deactivate_plugin() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have the required permissions to deactivate this plugin. Please contact your site administrator for assistance.', 'wds' ) ) );
		}

		$data = self::get_request_data();

		if ( empty( $data['plugin'] ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: 1,2: Anchor tag to plugins page. */
						__( 'There is an issue in deactivating this plugin. Please try deactivating it from the %1$sPlugins page%2$s or contact the plugin author for assistance.', 'wds' ),
						'<a>',
						'</a>'
					),
				)
			);
		}

		$plugin = $data['plugin'];

		$info = $this->conflicts[ $plugin ];

		if ( isset( $info['deps'] ) && is_array( $info['deps'] ) ) {
			$deps = $info['deps'];

			$i = 0;

            // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
			while ( $i < count( $deps ) ) {
				if ( is_plugin_active( $deps[ $i ] ) ) {
					break;
				}

				++$i;
			}

			if ( $i < count( $deps ) ) {
				wp_send_json_error(
					array(
						'message' => sprintf(
							/* translators: %s: Plugin name, 2,3: Anchor tag to plugins page. */
							__( '%1$s is required by other plugin(s). Please try deactivating it from the %2$sPlugins page%3$s or contact the plugin author for assistance.', 'wds' ),
							$info['name'],
							'<a>',
							'</a>'
						),
					)
				);
			}
		}

		$is_network = 1 === intval( $data['network'] );

		deactivate_plugins( $plugin, true, $is_network );

		wp_send_json_success();
	}

	/**
	 * Verifies nonce and returns POST request data.
	 *
	 * @return array
	 */
	private function get_request_data() {
		if ( ! isset( $_POST['_wds_nonce'] ) ) {
			return array();
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-settings-nonce' ) ) {
			return array();
		}

		return stripslashes_deep( $_POST );
	}
}