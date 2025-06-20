import React from 'react';
import FormField from '../form-field';
import InsertVariables from '../input-fields/insert-variables';

export default class InsertVariablesField extends React.Component {
	render() {
		return <FormField {...this.props} formControl={InsertVariables} />;
	}
}
