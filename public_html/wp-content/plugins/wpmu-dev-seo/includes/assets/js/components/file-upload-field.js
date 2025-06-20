import React from 'react';
import FormField from './form-field';
import FileUpload from './file-upload';

export default class FileUploadField extends React.Component {
	render() {
		return <FormField {...this.props} formControl={FileUpload} />;
	}
}
