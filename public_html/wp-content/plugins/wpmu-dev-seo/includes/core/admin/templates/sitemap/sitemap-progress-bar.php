<?php
/**
 * Template: Sitemap Progress Bar.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$crawl_report = empty( $crawl_report ) ? null : $crawl_report;
?>

<div class="wds-crawl-results-report wds-report">
	<p><?php esc_html_e( "We're looking for issues with your sitemap, please wait…", 'wds' ); ?></p>
	<div class="wds-url-crawler-progress">
		<?php
		$this->render_view(
			'progress-bar',
			array(
				'progress'       => $crawl_report->get_progress(),
				'progress_state' => __( 'Crawling website...', 'wds' ),
			)
		);
		?>
		<?php $this->render_view( 'progress-notice' ); ?>
	</div>
</div>