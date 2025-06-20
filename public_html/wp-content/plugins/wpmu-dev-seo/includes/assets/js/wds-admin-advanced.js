import ErrorBoundary from './components/error-boundry';
import domReady from '@wordpress/dom-ready';
import React from 'react';
import ReactDom from 'react-dom/client';
import Advanced from './modules/advanced/advanced';

domReady(() => {
	const container = document.getElementById('container');

	if (container) {
		const root = ReactDom.createRoot(container);

		root.render(
			<ErrorBoundary>
				<Advanced />
			</ErrorBoundary>
		);
	}
});
