<?php
/**
 * Abstract class for Hub connector.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Admin\Module_Settings;
use SmartCrawl\Modules\Advanced;
use SmartCrawl\Cache\Post_Cache;
use SmartCrawl\Configs;
use SmartCrawl\Entities;
use SmartCrawl\Lighthouse;
use SmartCrawl\Models\Analysis;
use SmartCrawl\Schema\Type_Constants;
use SmartCrawl\Services\Service;
use SmartCrawl\Settings;
use SmartCrawl\Sitemaps\Utils;
use SmartCrawl\Third_Party_Import\AIOSEOP;
use SmartCrawl\Third_Party_Import\Yoast;

/**
 * Abstract class for Hub connect.
 */
abstract class Hub_Abstract {

	/**
	 * Ajax handler to receive audit data.
	 *
	 * @param object $params Additional parameters.
	 * @param string $action Action being performed.
	 *
	 * @return void
	 */
	public function ajax_receive_audit_data( $params, $action = '' ) {
	}

	/**
	 * Syncs ignores list.
	 *
	 * @param array  $params The parameters for syncing.
	 * @param string $action The action for syncing.
	 *
	 * @return bool
	 */
	public function sync_ignores( $params, $action = '' ) {
		return false;
	}

	/**
	 * Ajax handler to sync the ignores list.
	 *
	 * @param object $params Hub-provided parameters for syncing.
	 * @param string $action The action to perform during syncing.
	 */
	public function ajax_sync_ignores( $params, $action = '' ) {
	}

	/**
	 * Purges the ignores list.
	 *
	 * @return bool
	 */
	public function purge_ignores() {
		return false;
	}

	/**
	 * Ajax handler to purges ignores list.
	 *
	 * @return void
	 */
	public function ajax_purge_ignores() {
	}

	/**
	 * Syncs the extras list.
	 *
	 * @param array  $params The parameters for the request.
	 * @param string $action The action to perform on the extras list. Default is an empty string.
	 *
	 * @return bool
	 */
	public function sync_extras( $params, $action = '' ) {
		return false;
	}

	/**
	 * Ajax handler to sync extras list.
	 *
	 * @param object $params Hub-provided parameters.
	 * @param string $action Action called.
	 *
	 * @return void
	 */
	public function ajax_sync_extras( $params, $action = '' ) {
	}

	/**
	 * Purges the extras list.
	 *
	 * @return bool
	 */
	public function purge_extras() {
		return false;
	}

	/**
	 * Purges the extras list in JSON format.
	 *
	 * @return void
	 */
	public function ajax_purge_extras() {
	}

	/**
	 * Ajax handler to return the SEO summary result as response.
	 *
	 * @return void
	 */
	public function ajax_seo_summary() {
		$options = Settings::get_options();

		// Twitter cards.
		$twitter_enabled = (bool) \smartcrawl_get_array_value( $options, 'twitter-card-enable' );
		$card_type       = \smartcrawl_get_array_value( $options, 'twitter-card-type' );
		$twitter         = $twitter_enabled
			? array( 'card_type' => $card_type )
			: array();

		// Pinterest.
		$pinterest_status = \smartcrawl_get_array_value( $options, 'pinterest-verification-status' );
		$pinterest_value  = \smartcrawl_get_array_value( $options, 'pinterest-verify' );
		$pinterest        = empty( $pinterest_value )
			? array()
			: array(
				'status' => 'fail' === $pinterest_status ? 'unverified' : 'verified',
			);

		$adv_controller       = Advanced\Controller::get();
		$autolinks_controller = Advanced\Autolinks\Controller::get();
		$moz_controller       = Advanced\Seomoz\Controller::get();
		$robots_controller    = Advanced\Robots\Controller::get();

		$adv_opts = Advanced\Controller::get()->get_options();

		// URL redirects.
		$redirects_table = Advanced\Redirects\Database_Table::get();
		$redirects_count = $redirects_table->get_count();

		// Moz.
		$moz = array(
			'active' => $moz_controller->should_run(),
			'data'   => $moz_controller->should_run()
				? get_option( Advanced\Seomoz\Cron::OPTION_ID, array() )
				: (object) array(),
		);

		// Robots file.
		$autolinks = $autolinks_controller->should_run()
			? array(
				'insert'  => $autolinks_controller->get_insert_options(),
				'link_to' => $autolinks_controller->get_linkto_options(),
			)
			: array();

		$advanced = Module_Settings::is_tab_allowed( Settings::ADVANCED_MODULE )
			? array(
				'autolinks'          => (object) $autolinks,
				'autolinking_active' => $autolinks_controller->should_run(),
				'url_redirects'      => $redirects_count,
				'moz'                => $moz,
				'robots_txt_active'  => $robots_controller->should_run(),
			)
			: array();

		// Lighthouse reporting schedule.
		$lighthouse_reporting_enabled = Lighthouse\Options::is_cron_enabled();
		$lighthouse_reporting         = $lighthouse_reporting_enabled
			? array(
				'frequency'  => Lighthouse\Options::reporting_frequency(),
				'day'        => Lighthouse\Options::reporting_dow(),
				'time'       => Lighthouse\Options::reporting_tod(),
				'recipients' => count( Lighthouse\Options::email_recipients() ),
			)
			: array();
		$lighthouse                   = array(
			'reporting' => (object) $lighthouse_reporting,
		);

		// Crawler reporting schedule.
		$seo_service               = Service::get( Service::SERVICE_SEO );
		$seo_report                = $seo_service->get_report();
		$crawler_reporting_enabled = \smartcrawl_get_array_value( $options, 'crawler-cron-enable' );
		$crawler_reporting         = $crawler_reporting_enabled
			? array(
				'frequency'  => \smartcrawl_get_array_value( $options, 'crawler-frequency' ),
				'day'        => \smartcrawl_get_array_value( $options, 'crawler-dow' ),
				'time'       => \smartcrawl_get_array_value( $options, 'crawler-tod' ),
				'recipients' => count( \SmartCrawl\Admin\Settings\Sitemap::get_email_recipients() ),
			)
			: array();
		$sitemap_stats             = Utils::get_meta_data();
		$sitemap_se_stats          = get_option( Utils::ENGINE_NOTIFICATION_OPTION_ID );
		$sitemap                   = $this->is_active( 'sitemap' )
			? array(
				'url'                      => \smartcrawl_get_sitemap_url(),
				'last_update'              => \smartcrawl_get_array_value( $sitemap_stats, 'time' ),
				'last_google_notification' => \smartcrawl_get_array_value(
					$sitemap_se_stats,
					array(
						'google',
						'time',
					)
				),
				'last_bing_notification'   => \smartcrawl_get_array_value(
					$sitemap_se_stats,
					array(
						'bing',
						'time',
					)
				),
				'crawler'                  => array(
					'in_progress'        => $seo_report->is_in_progress(),
					'last_run_timestamp' => $seo_service->get_last_run_timestamp(),
					'reporting'          => (object) $crawler_reporting,
				),
			)
			: array();

		// Third-party import.
		$import_plugins = array();

		$yoast_importer = new Yoast();

		if ( $yoast_importer->data_exists() ) {
			$import_plugins[] = 'yoast';
		}

		$aioseo = new AIOSEOP();

		// phpcs:disable
		// if ( $aioseo->data_exists() ) {
		// 	$import_plugins[] = 'aioseo';
		// }
		// phpcs:enable

		$onpage_active = $this->is_active( 'onpage' );
		$onpage        = $onpage_active
			? array(
				'meta'              => $this->get_titles_and_meta(),
				'static_homepage'   => get_option( 'show_on_front' ) === 'page',
				'public_post_types' => count( get_post_types( array( 'public' => true ) ) ),
			)
			: array();

		$social_active = $this->is_active( 'social' );
		$social        = $social_active
			? array(
				'opengraph_active' => (bool) \smartcrawl_get_array_value( $options, 'og-enable' ),
				'twitter'          => (object) $twitter,
				'pinterest'        => (object) $pinterest,
			)
			: array();

		$analysis             = array();
		$analysis_model       = new Analysis();
		$seo_analysis_enabled = Settings::get_setting( 'analysis-seo' );
		if ( $seo_analysis_enabled ) {
			$analysis['seo'] = $analysis_model->get_overall_seo_analysis();
		}
		$readability_analysis_enabled = Settings::get_setting( 'analysis-readability' );
		if ( $readability_analysis_enabled ) {
			$analysis['readability'] = $analysis_model->get_overall_readability_analysis();
		}

		// Schema.
		$schema_utils          = \SmartCrawl\Schema\Utils::get();
		$is_schema_type_person = $schema_utils->is_schema_type_person();
		$schema_active         = ! \smartcrawl_get_array_value( $options, 'disable-schema' ) && Module_Settings::is_tab_allowed( Settings::TAB_SCHEMA );
		$schema                = $schema_active ? array(
			'org_type' => $is_schema_type_person
				? Type_Constants::TYPE_PERSON
				: Type_Constants::TYPE_ORGANIZATION,
			'org_name' => $is_schema_type_person
				? $schema_utils->get_personal_brand_name()
				: $schema_utils->get_organization_name(),
			'types'    => $this->get_schema_types(),
		) : array();

		wp_send_json_success(
			array(
				'onpage'     => (object) $onpage,
				'schema'     => (object) $schema,
				'social'     => (object) $social,
				'advanced'   => (object) $advanced,
				'lighthouse' => (object) $lighthouse,
				'sitemap'    => (object) $sitemap,
				'analysis'   => (object) $analysis,
				'import'     => array( 'plugins' => $import_plugins ),
			)
		);
	}

	/**
	 * Runs the crawl and returns the result as Ajax response.
	 *
	 * @return void
	 */
	public function ajax_run_crawl() {
		$service = Service::get( Service::SERVICE_SEO );
		$started = $service->start();

		if ( is_wp_error( $started ) ) {
			wp_send_json_error(
				array(
					'message' => $started->get_error_message(),
				)
			);
		} elseif ( ! $started ) {
			wp_send_json_error();
		} else {
			wp_send_json_success();
		}
	}

	/**
	 * Retrieves the titles and meta information.
	 *
	 * @return array An associative array containing titles and meta information.
	 */
	private function get_titles_and_meta() {
		$meta_title       = '';
		$meta_description = '';
		$home_robots      = '';
		$meta             = array();
		$posts_on_front   = 'posts' === get_option( 'show_on_front' ) || 0 === (int) get_option( 'page_on_front' );

		if ( $posts_on_front ) {
			$blog_home        = new Entities\Blog_Home();
			$meta_title       = $blog_home->get_meta_title();
			$meta_description = $blog_home->get_meta_description();
			$home_robots      = $blog_home->get_robots();
		} else {
			$page_on_front = Post_Cache::get()->get_post( (int) get_option( 'page_on_front' ) );
			if ( $page_on_front ) {
				$meta_title       = $page_on_front->get_meta_title();
				$meta_description = $page_on_front->get_meta_description();
				$home_robots      = $page_on_front->get_robots();
			}
		}
		$meta['home'] = array(
			'label'       => esc_html__( 'Homepage', 'wds' ),
			'title'       => $meta_title,
			'description' => $meta_description,
			'noindex'     => strpos( $home_robots, 'noindex' ) !== false,
			'nofollow'    => strpos( $home_robots, 'nofollow' ) !== false,
			'url'         => home_url(),
		);

		foreach (
			get_post_types(
				array(
					'public'  => true,
					'show_ui' => true,
				),
				'objects'
			) as $post_type
		) {
			$random_post = $this->get_random_post( $post_type->name );
			if ( ! $random_post ) {
				continue;
			}
			$smartcrawl_random_post = Post_Cache::get()->get_post( $random_post->ID );
			if ( ! $smartcrawl_random_post ) {
				continue;
			}
			$meta[ $post_type->name ] = array(
				'label'       => $post_type->label,
				'title'       => $smartcrawl_random_post->get_meta_title(),
				'description' => $smartcrawl_random_post->get_meta_description(),
				'noindex'     => $smartcrawl_random_post->is_noindex(),
				'nofollow'    => $smartcrawl_random_post->is_nofollow(),
				'url'         => $smartcrawl_random_post->get_permalink(),
			);
		}

		return $meta;
	}

	/**
	 * Retrieves a random post of a specific post type.
	 *
	 * @param string $post_type The post type to retrieve.
	 *
	 * @return \WP_Post|null The random post or null if no posts are found.
	 */
	private function get_random_post( $post_type ) {
		$posts = get_posts(
			array(
				'post_status'    => array( 'publish', 'inherit' ),
				'orderby'        => 'rand',
				'posts_per_page' => 1,
				'post_type'      => $post_type,
			)
		);

		return \smartcrawl_get_array_value( $posts, 0 );
	}

	/**
	 * Retrieves the schema types.
	 *
	 * @return array
	 */
	private function get_schema_types() {
		$schema_types = \SmartCrawl\Schema\Types::get()->get_schema_types();

		return array_unique( array_column( $schema_types, 'type' ) );
	}

	/**
	 * Checks if a module is active.
	 *
	 * @param string $module The module name.
	 *
	 * @return bool Returns true if the module is active, false otherwise.
	 */
	private function is_active( $module ) {
		return Settings::get_setting( $module ) && Module_Settings::is_tab_allowed( 'wds_' . $module );
	}

	/**
	 * Ajax handler to apply configuration.
	 *
	 * @param object $params Config params.
	 *
	 * @return void
	 */
	public function ajax_apply_config( $params ) {
		if ( empty( $params->configs ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid config', 'wds' ) )
			);
		}

		$configs = json_decode( wp_json_encode( $params->configs ), true );
		Configs\Controller::get()->apply_handler( $configs );

		wp_send_json_success();
	}

	/**
	 * Ajax handler to export the configuration.
	 *
	 * @return void
	 */
	public function ajax_export_config() {
		$config = Configs\Model::create_from_plugin_snapshot();
		wp_send_json_success(
			array(
				'configs' => $config->get_configs(),
				'strings' => $config->get_strings(),
			)
		);
	}

	/**
	 * Refreshes the Lighthouse report and returns it as a JSON string.
	 *
	 * @return void
	 */
	public function ajax_refresh_lighthouse_report() {
		$lighthouse = Service::get( Service::SERVICE_LIGHTHOUSE );
		$lighthouse->refresh_report();
	}
}