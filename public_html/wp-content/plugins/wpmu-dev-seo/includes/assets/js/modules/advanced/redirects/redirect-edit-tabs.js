import React from 'react';
import Tabs from '../../../components/tabs';
import { __ } from '@wordpress/i18n';
import RedirectEditMain from './redirect-edit-main';
import RedirectEditAdvanced from './redirect-edit-advanced';

export default class RedirectEditTabs extends React.Component {
	static defaultProps = {
		bulkUpdate: false,
	};

	constructor(props) {
		super(props);

		this.state = {
			selectedTab: 'main',
		};
	}

	handleTabChange(tab) {
		event.preventDefault();
		event.stopPropagation();

		this.setState({
			selectedTab: tab,
		});
	}

	render() {
		const { bulkUpdate } = this.props;

		if (bulkUpdate) {
			return <RedirectEditMain bulkUpdate={true} />;
		}

		const tabs = {
			main: {
				label: __('Redirect', 'wds'),
				component: <RedirectEditMain />,
			},
			advanced: {
				label: __('Advanced', 'wds'),
				component: <RedirectEditAdvanced />,
			},
		};

		return (
			<Tabs
				tabs={tabs}
				value={this.state.selectedTab}
				flushed={true}
				onChange={(tab) => this.handleTabChange(tab)}
			></Tabs>
		);
	}
}
