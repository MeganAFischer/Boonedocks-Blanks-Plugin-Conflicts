import domReady from '@wordpress/dom-ready';
import $ from 'jQuery';
import React from 'react';
import ReactDom from 'react-dom/client';
import ErrorBoundary from './components/error-boundry';
import DeactivationSurvey from './admin/deactivation-survey';

domReady(() => {
	$(document).on('wpmud.ready', () => {
		const wpmudevDashboardAdminPluginsPage = $('body').data(
			'wpmudevDashboardAdminPluginsPage'
		);

		if (!wpmudevDashboardAdminPluginsPage) {
			return;
		}

		wpmudevDashboardAdminPluginsPage.$el.off(
			'click',
			'a[data-action=project-deactivate]'
		);
		wpmudevDashboardAdminPluginsPage.$el.on(
			'click',
			'a[data-action=project-deactivate]',
			(e) => {
				e.preventDefault();

				if (!wpmudevDashboardAdminPluginsPage.actionEnabled) {
					return false;
				}

				const data = e.target.dataset;

				if (
					data?.action === 'project-deactivate' &&
					$(e.target)
						.closest('tr')
						.find('.dashui-plugin-name > a:first-child')
						.text()
						.includes('SmartCrawl')
				) {
					const wrap = document.getElementById('wds-survey-wrap');

					if (wrap) {
						const root = ReactDom.createRoot(wrap);

						root.render(
							<ErrorBoundary>
								<DeactivationSurvey
									from="dashboard"
									data={data}
								/>
							</ErrorBoundary>
						);
					}
				} else {
					wpmudevDashboardAdminPluginsPage.deactivate(data);
				}

				return false;
			}
		);
	});

	$(
		'.plugins tr.active[data-slug="smartcrawl-pro"] .deactivate a[id^="deactivate-smartcrawl-pro"],' +
			'.plugins tr.active[data-slug="smartcrawl-seo"] .deactivate a[id^="deactivate-smartcrawl-seo"]'
	).on('click', (e) => {
		e.preventDefault();

		const wrap = document.getElementById('wds-survey-wrap');

		if (wrap) {
			const root = ReactDom.createRoot(wrap);

			root.render(
				<ErrorBoundary>
					<DeactivationSurvey from="plugins" data={e.target.href} />
				</ErrorBoundary>
			);
		}

		return false;
	});
});
