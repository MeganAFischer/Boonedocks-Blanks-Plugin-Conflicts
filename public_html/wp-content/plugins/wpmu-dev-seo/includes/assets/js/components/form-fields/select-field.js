import React from 'react';
import FormField from '../form-field';
import Select from '../input-fields/select';

export default class SelectField extends React.Component {
	render() {
		return <FormField {...this.props} formControl={Select} />;
	}
}
