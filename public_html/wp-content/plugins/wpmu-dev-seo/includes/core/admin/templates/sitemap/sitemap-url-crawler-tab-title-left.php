<?php
/**
 * Template: Sitemap Url Crawler Tab Title Left.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$crawl_report  = empty( $_view['crawl_report'] ) ? null : $_view['crawl_report'];
$active_issues = empty( $crawl_report ) ? 0 : (int) $crawl_report->get_issues_count();
$is_member     = ! empty( $_view['is_member'] );
$upgrade_url   = 'https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=smartcrawl_sitemap_crawler_pro_tag';

if ( $is_member ) : ?>
	<span
		class="sui-tag sui-tag-warning"
		style="<?php echo $active_issues ? '' : 'display:none;'; ?>"
	>
		<?php echo esc_html( (string) $active_issues ); ?>
	</span>
<?php else : ?>
	<a target="_blank" href="<?php echo esc_attr( $upgrade_url ); ?>">
		<span
			class="sui-tag sui-tag-pro sui-tooltip"
			data-tooltip="<?php esc_attr_e( 'Upgrade to SmartCrawl Pro', 'wds' ); ?>"
		>
			<?php esc_html_e( 'Pro', 'wds' ); ?>
		</span>
	</a>
<?php endif; ?>