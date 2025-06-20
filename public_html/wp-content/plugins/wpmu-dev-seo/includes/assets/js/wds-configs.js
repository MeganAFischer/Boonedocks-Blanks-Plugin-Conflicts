import React from 'react';
import ErrorBoundary from './components/error-boundry';
import domReady from '@wordpress/dom-ready';
import ConfigsTabWrapper from './components/configs/configs-tab-wrapper';
import ConfigsWidgetWrapper from './components/configs/configs-widget-wrapper';
import ReactDom from 'react-dom/client';

domReady(() => {
	const settingsPageConfigs = document.getElementById(
		'wds-config-components'
	);
	if (settingsPageConfigs) {
		const root = ReactDom.createRoot(settingsPageConfigs);
		root.render(
			<ErrorBoundary>
				<ConfigsTabWrapper />
			</ErrorBoundary>
		);
	}

	const dashboardWidget = document.getElementById('wds-config-widget');
	if (dashboardWidget) {
		const root = ReactDom.createRoot(dashboardWidget);
		root.render(
			<ErrorBoundary>
				<ConfigsWidgetWrapper />
			</ErrorBoundary>
		);
	}
});
