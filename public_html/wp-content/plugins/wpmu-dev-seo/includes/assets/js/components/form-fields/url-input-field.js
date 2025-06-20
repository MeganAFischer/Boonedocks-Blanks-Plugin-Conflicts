import React from 'react';
import FormField from '../form-field';
import UrlInput from '../input-fields/url-input';

export default class UrlInputField extends React.Component {
	render() {
		return <FormField {...this.props} formControl={UrlInput} />;
	}
}
