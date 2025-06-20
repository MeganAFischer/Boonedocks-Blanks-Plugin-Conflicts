import React from 'react';
import ReactDom from 'react-dom/client';
import ErrorBoundary from './components/error-boundry';
import DataResetButton from './components/settings/data-reset-button';
import MultisiteResetButton from './components/settings/multisite-reset-button';
import PluginModules from './modules/settings/general/plugin-modules';
import ConflictingPlugins from './components/settings/plugin-modules/conflicting-plugins';

(function ($) {
	const resetButton = document.getElementById(
		'wds-data-reset-button-placeholder'
	);
	if (resetButton) {
		const root = ReactDom.createRoot(resetButton);
		root.render(
			<ErrorBoundary>
				<DataResetButton />
			</ErrorBoundary>
		);
	}

	const multisiteResetButton = document.getElementById(
		'wds-multisite-reset-button-placeholder'
	);
	if (multisiteResetButton) {
		const root = ReactDom.createRoot(multisiteResetButton);
		root.render(
			<ErrorBoundary>
				<MultisiteResetButton />
			</ErrorBoundary>
		);
	}

	const conflictingPlugins = document.getElementById(
		'wds-conflicting-plugins'
	);
	if (conflictingPlugins) {
		const root = ReactDom.createRoot(conflictingPlugins);
		root.render(
			<ErrorBoundary>
				<ConflictingPlugins />
			</ErrorBoundary>
		);
	}

	const pluginModules = document.getElementById('wds-plugin-modules');
	if (pluginModules) {
		const root = ReactDom.createRoot(pluginModules);
		root.render(
			<ErrorBoundary>
				<PluginModules />
			</ErrorBoundary>
		);
	}

	window.Wds = window.Wds || {};

	function addCustomMetaTagField() {
		const $this = $(this),
			$container = $this.closest('.wds-custom-meta-tags'),
			$newInput = $container
				.find('.wds-custom-meta-tag:first-of-type')
				.clone();

		$newInput.insertBefore($this);
		$newInput.find('input').val('').trigger('focus');
	}

	function init() {
		window.Wds.styleable_file_input();
		$(document).on(
			'click',
			'.wds-custom-meta-tags button',
			addCustomMetaTagField
		);
		$(
			'input[type="checkbox"][name="wds_settings_options[analysis-seo]"], input[type="checkbox"][name="wds_settings_options[analysis-readability]"]'
		).on('change', () => {
			const seoChecked = $(
					'input[type="checkbox"][name="wds_settings_options[analysis-seo]"]'
				).is(':checked'),
				readabilityChecked = $(
					'input[type="checkbox"][name="wds_settings_options[analysis-readability]"]'
				).is(':checked');

			$('.wds-in-post-analysis').css(
				'display',
				seoChecked || readabilityChecked ? 'flex' : 'none'
			);
		});

		Wds.vertical_tabs();
	}

	$(init);
})(jQuery);
