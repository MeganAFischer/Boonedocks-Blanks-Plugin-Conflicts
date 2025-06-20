import React from 'react';
import RedirectTable from './redirect-table';
import RedirectSettings from './redirect-settings';
import { createStore } from 'redux';
import redirectReducer from '../../../reducers/redirect-reducer';
import { connect, Provider } from 'react-redux';

class Redirects extends React.Component {
	render() {
		const store = createStore(
			redirectReducer,
			this.props.hasOwnProperty('redirect')
				? {
						...this.props.redirect,
						maxmind_license: this.props.maxmind_license,
				  }
				: Object.assign({}, this.props, {
						id: '',
						source: '',
						destination: '',
						dstDisabled: false,
						rules: [],
						ruleKeys: [],
						title: '',
						options: [],
						type: this.props.options.default_type,
						defaultType: this.props.options.default_type,
						valid: false,
						loading: false,
						deletingRule: false,
						maxmindUpdate: this.props.updateProp,
				  })
		);

		return (
			<>
				<Provider store={store}>
					<RedirectTable />
				</Provider>
				<RedirectSettings />
			</>
		);
	}
}

const mapStateToProps = (state) => ({ ...state.redirects });

const mapDispatchToProps = {
	updateProp: (key, value) => ({
		type: 'UPDATE_PROP',
		key,
		value,
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(Redirects);
