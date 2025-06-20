import React from 'react';
import FormField from '../form-field';
import Text from '../input-fields/text';

export default class TextField extends React.Component {
	render() {
		return (
			<FormField
				{...this.props}
				className="sui-form-control"
				formControl={Text}
			/>
		);
	}
}
