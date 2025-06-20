import React from 'react';
import domReady from '@wordpress/dom-ready';
import ErrorBoundary from './components/error-boundry';
import ThirdPartyImport from './components/import/third-party-import';
import ConfigValues from './es6/config-values';
import ReactDom from 'react-dom/client';

domReady(() => {
	const importContainer = document.getElementById('wds-import-container');

	if (!importContainer) {
		return;
	}

	const root = ReactDom.createRoot(importContainer);

	const isMultisite = ConfigValues.get('is_multisite', 'import');
	const nonce = ConfigValues.get('nonce', 'import');
	const hasAioSeoData = ConfigValues.get('aioseop_data_exists', 'import');
	const indexSettingsUrl = ConfigValues.get('index_settings_url', 'import');

	root.render(
		<ErrorBoundary>
			<ThirdPartyImport
				isMultisite={isMultisite}
				indexSettingsUrl={indexSettingsUrl}
				nonce={nonce}
				hasAioSeoData={hasAioSeoData}
			/>
		</ErrorBoundary>
	);
});
