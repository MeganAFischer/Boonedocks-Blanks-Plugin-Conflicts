import ErrorBoundary from './components/error-boundry';
import ReactDom from 'react-dom/client';
import React from 'react';
import CrawlReport from './components/crawler/crawl-report';
import UrlUtil from './utils/url-util';
import NewsSitemapTab from './components/sitemaps/news-sitemap-tab';
import ConfigValues from './es6/config-values';
import SitemapTroubleshoot from './components/sitemaps/sitemap-troubleshoot';

(($, undefined) => {
	const newsSitemapTab = document.getElementById('wds-news-sitemap-tab');
	if (newsSitemapTab) {
		const root = ReactDom.createRoot(newsSitemapTab);

		root.render(
			<ErrorBoundary>
				<NewsSitemapTab
					newsSitemapUrl={ConfigValues.get(
						'news_sitemap_url',
						'news'
					)}
					schemaEnabled={ConfigValues.get('schema_enabled', 'news')}
					enabled={ConfigValues.get('enabled', 'news')}
					publication={ConfigValues.get('publication', 'news')}
					postTypes={ConfigValues.get('post_types', 'news')}
				/>
			</ErrorBoundary>
		);
	}

	const sitemapTroubleshooting = document.getElementById(
		'wds-troubleshooting-sitemap-placeholder'
	);
	if (sitemapTroubleshooting) {
		const root = ReactDom.createRoot(sitemapTroubleshooting);

		root.render(
			<React.Fragment>
				<ErrorBoundary>
					<SitemapTroubleshoot
						nonce={ConfigValues.get('nonce', 'sitemaps')}
						sitemapUrl={ConfigValues.get('sitemap_url', 'sitemaps')}
					/>
				</ErrorBoundary>
				<div />
			</React.Fragment>
		);
	}

	const updatePageAfterReportReload = (activeIssues, activeSitemapIssues) => {
		if (activeIssues === undefined) {
			// In progress or no data
			return;
		}

		const $titleIssuesIndicator = $(
				'#tab_url_crawler .sui-box-header .sui-tag'
			),
			$crawlerTab = $('li.tab_url_crawler'),
			$labelIssuesIndicator = $crawlerTab.find('.sui-tag'),
			$labelTick = $crawlerTab.find('.sui-icon-check-tick'),
			$labelSpinner = $crawlerTab.find('.sui-icon-loader'),
			$newCrawlButton = $('.wds-new-crawl-button'),
			$summaryNumber = $('.sui-summary-large'),
			$summaryIcon = $('.sui-summary-large + [class*="sui-icon-"]'),
			$sitemapIssueCount = $('.wds-invisible-urls-count'),
			$titleIgnoreAllButton = $(
				'.sui-box-header .wds-ignore-all'
			).closest('div');

		if (activeIssues > 0) {
			$titleIssuesIndicator.show().html(activeIssues);
			$labelIssuesIndicator.show().html(activeIssues);
			$titleIgnoreAllButton.show();
			$labelTick.hide();
			$summaryIcon
				.removeClass('sui-icon-check-tick sui-success')
				.addClass('sui-icon-info sui-warning');
		} else {
			$titleIssuesIndicator.hide();
			$labelIssuesIndicator.hide();
			$titleIgnoreAllButton.hide();
			$labelTick.show();
			$summaryIcon
				.removeClass('sui-icon-info sui-warning')
				.addClass('sui-icon-check-tick sui-success');
		}

		// Show active issue count in top section
		$summaryNumber.html(activeIssues);
		$sitemapIssueCount.html(activeSitemapIssues);

		// Hide the spinner and show the new crawl button regardless of the result
		$labelSpinner.hide();
		$newCrawlButton.show();
	};

	const getCrawlProgress = () =>
		$.post(
			ajaxurl,
			{
				action: 'smartcrawl_crawl_get_progress',
				_wds_nonce: Wds.get('crawler', 'nonce'),
			},
			() => false,
			'json'
		);

	const updateProgress = () => {
		const $container = $('.tab_url_crawler');
		if (!$container.find('.wds-url-crawler-progress').length) {
			return;
		}

		/**
		 * Get crawl progress.
		 *
		 * @param {{data:{in_progress:boolean, progress: number}}} response - Response
		 */
		getCrawlProgress().done((response) => {
			const inProgress = response?.data?.in_progress;
			const progress = response?.data?.progress;
			const $progressBar = $('#tab_url_crawler .wds-progress');

			if (inProgress) {
				Wds.update_progress_bar($progressBar, progress);
				setTimeout(updateProgress, 5000);
			} else {
				Wds.update_progress_bar($progressBar, 100);
				window.location.reload();
			}
		});
	};

	const updateSitemapSubsectionVisibility = () => {
		$('.wds-sitemap-toggleable').each(() => {
			const $toggleable = $(this),
				$nestedTable = $toggleable.next('tr').find('.sui-table');

			if ($toggleable.find('input[type="checkbox"]').is(':checked')) {
				$nestedTable.show();
			} else {
				$nestedTable.hide();
			}
		});
	};

	const addQueryParams = (params) => {
		const currentUrl = window.location.href,
			currentParams = new URLSearchParams(window.location.search);

		return (
			currentUrl.split('?')[0] +
			'?' +
			$.param($.extend({}, { page: currentParams.get('page') }, params))
		);
	};

	const overrideNative = (override, callback) =>
		$.post(
			ajaxurl,
			{
				action: 'wds-override-native',
				override: override ? '1' : '0',
				_wds_nonce: Wds.get('sitemaps', 'nonce'),
			},
			callback,
			'json'
		);

	const switchToNativeSitemap = () => {
		const $button = $('#wds-switch-to-native-button');

		Wds.open_dialog(
			'wds-switch-to-native-modal',
			'wds-switch-to-native-sitemap',
			$button.attr('id')
		);
		$button.off().on('click', () => {
			$button.addClass('sui-button-onload');
			overrideNative(false, () => {
				window.location.href = addQueryParams({
					'switched-to-native': 1,
				});
			});
		});
	};

	const switchToSmartcrawlSitemap = () => {
		const $button = $('#wds-switch-to-smartcrawl-button');

		Wds.open_dialog(
			'wds-switch-to-smartcrawl-modal',
			'wds-switch-to-smartcrawl-sitemap',
			$button.attr('id')
		);
		$button.off().on('click', () => {
			$button.addClass('sui-button-onload');
			overrideNative(true, () => {
				window.location.href = addQueryParams({
					'switched-to-sc': 1,
				});
			});
		});
	};

	const manuallyNotifySearchEngines = () => {
		const $button = $(this);
		$button.addClass('sui-button-onload');
		return $.post(
			ajaxurl,
			{
				action: 'wds-manually-update-engines',
				_wds_nonce: Wds.get('sitemaps', 'nonce'),
			},
			() => {
				Wds.show_floating_message(
					'wds-sitemap-manually-notify-search-engines',
					Wds.l10n('sitemaps', 'manually_notified_engines'),
					'success'
				);
				$button.removeClass('sui-button-onload');
			},
			'json'
		);
	};

	const manuallyUpdateSitemap = () => {
		const $button = $(this);
		$button.addClass('sui-button-onload');
		return $.post(
			ajaxurl,
			{
				action: 'wds-manually-update-sitemap',
				_wds_nonce: Wds.get('sitemaps', 'nonce'),
			},
			() => {
				Wds.show_floating_message(
					'wds-sitemap-manually-updated',
					Wds.l10n('sitemaps', 'manually_updated'),
					'success'
				);
				$button.removeClass('sui-button-onload');
			},
			'json'
		);
	};

	const deactivateSitemapModule = () => {
		$(this).addClass('sui-button-onload');
		return $.post(
			ajaxurl,
			{
				action: 'wds-deactivate-sitemap-module',
				_wds_nonce: Wds.get('sitemaps', 'nonce'),
			},
			() => {
				window.location.reload();
			},
			'json'
		);
	};

	const init = () => {
		window.Wds.hook_conditionals();
		window.Wds.hook_toggleables();
		window.Wds.conditional_fields();
		window.Wds.dismissible_message();
		window.Wds.vertical_tabs();
		window.Wds.reporting_schedule();

		updateProgress();
		UrlUtil.removeQueryParam('crawl-in-progress');

		$(document)
			.on(
				'change',
				'.wds-sitemap-toggleable input[type="checkbox"]',
				updateSitemapSubsectionVisibility
			)
			.on('click', '#wds-switch-to-native-sitemap', switchToNativeSitemap)
			.on(
				'click',
				'#wds-switch-to-smartcrawl-sitemap',
				switchToSmartcrawlSitemap
			)
			.on(
				'click',
				'#wds-deactivate-sitemap-module',
				deactivateSitemapModule
			)
			.on('click', '#wds-manually-update-sitemap', manuallyUpdateSitemap)
			.on(
				'click',
				'#wds-manually-notify-search-engines',
				manuallyNotifySearchEngines
			);

		$(updateSitemapSubsectionVisibility);
	};

	$(init);

	const reportContainer = document.getElementById('wds-url-crawler-report');
	if (reportContainer) {
		const root = ReactDom.createRoot(reportContainer);

		root.render(
			<ErrorBoundary>
				<CrawlReport
					onActiveIssueCountChange={updatePageAfterReportReload}
				/>
			</ErrorBoundary>
		);
	}
})(jQuery);
