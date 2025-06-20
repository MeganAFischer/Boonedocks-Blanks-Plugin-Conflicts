import React from 'react';
import FormField from '../form-field';
import ImageUploads from '../input-fields/image-uploads';

export default class ImageUploadsField extends React.Component {
	render() {
		return <FormField {...this.props} formControl={ImageUploads} />;
	}
}
