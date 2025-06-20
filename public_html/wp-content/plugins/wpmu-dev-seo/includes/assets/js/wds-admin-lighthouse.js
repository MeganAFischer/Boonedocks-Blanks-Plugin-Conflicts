import ErrorBoundary from './components/error-boundry';
import React from 'react';
import domReady from '@wordpress/dom-ready';
import LighthouseTab from './components/lighthouse/lighthouse-tab';
import ReactDom from 'react-dom/client';

domReady(() => {
	const placeholder = document.getElementById('wds-lighthouse-tab');
	if (placeholder) {
		const root = ReactDom.createRoot(placeholder);

		root.render(
			<ErrorBoundary>
				<LighthouseTab />
			</ErrorBoundary>
		);
	}
});

(function ($) {
	$(init);

	function init() {
		window.Wds.reporting_schedule();
		window.Wds.vertical_tabs();
		window.Wds.hook_toggleables();

		$(document).on(
			'click',
			'#wds-new-lighthouse-test-button',
			startNewTest
		);

		$('.wds-vertical-tabs').on(
			'wds_vertical_tabs:tab_change',
			function (event, activeTab) {
				$(activeTab)
					.find('.wds-vertical-tab-section')
					.removeClass('hidden');
			}
		);
	}

	function startNewTest() {
		const $button = $(this);
		$button.addClass('sui-button-onload');

		post('wds-lighthouse-start-test').then(function () {
			window.location.reload();
		});
	}

	function post(action) {
		return new Promise(function (resolve, reject) {
			const request = {
				action,
				_wds_nonce: Wds.get('lighthouse', 'nonce'),
			};

			$.post(ajaxurl, request)
				.done(function (response) {
					if (response.success) {
						resolve((response || {}).data);
					} else {
						reject();
					}
				})
				.fail(reject);
		});
	}
})(jQuery);
