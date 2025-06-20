import React from 'react';
import UrlUtil from '../../utils/url-util';
import { Provider } from 'react-redux';
import { createStore } from 'redux';
import reducer from '../../reducers/module-reducer';
import Module from './module';

export default class ModuleProvider extends React.Component {
	static defaultProps = {
		reducers: {},
		preloadedState: {},
	};

	render() {
		const { submodules } = this.props;
		const preloadedState = {};

		Object.keys(submodules).forEach((submodule) => {
			preloadedState[submodule] = window[`_wds_${submodule}`] || {};
		});

		preloadedState.selected =
			UrlUtil.getQueryParam('tab') ||
			(Object.keys(submodules) || [])[0] ||
			0;

		const store = createStore(reducer, preloadedState);

		return (
			<Provider store={store}>
				<Module {...this.props} />
			</Provider>
		);
	}
}
