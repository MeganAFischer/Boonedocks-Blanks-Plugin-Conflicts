import React from 'react';
import FormField from '../form-field';
import OptimumIndicator from '../input-fields/optimum-indicator';

export default class OptimumIndicatorField extends React.Component {
	render() {
		return <FormField {...this.props} formControl={OptimumIndicator} />;
	}
}
