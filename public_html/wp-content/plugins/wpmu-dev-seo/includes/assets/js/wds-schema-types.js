import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/error-boundry';
import { Provider } from 'react-redux';
import ConfigValues from './es6/config-values';
import {
	initializeTypes,
	validateTypes,
} from './components/schema/utils/type-utils';
import { createStore } from 'redux';
import reducer from './components/schema/reducers/';
import domReady from '@wordpress/dom-ready';
import SchemaTypesBuilderContainer from './components/schema/components/schema-types-builder';

domReady(() => {
	const schemaBuilderPlaceholder = document.getElementById(
		'wds-schema-type-components'
	);
	if (schemaBuilderPlaceholder) {
		const savedTypeData = ConfigValues.get('types', 'schema_types');
		const store = createStore(reducer, {
			types: validateTypes(initializeTypes(savedTypeData)),
		});

		const root = createRoot(schemaBuilderPlaceholder);
		root.render(
			<Provider store={store}>
				<ErrorBoundary>
					<SchemaTypesBuilderContainer />
				</ErrorBoundary>
			</Provider>
		);
	}
});
