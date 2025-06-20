import React from 'react';
import FormField from './form-field';
import SideTabs from './side-tabs';

export default class SideTabsField extends React.Component {
	render() {
		return <FormField {...this.props} formControl={SideTabs} />;
	}
}
