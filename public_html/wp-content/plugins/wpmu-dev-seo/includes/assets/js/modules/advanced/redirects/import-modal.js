import React from 'react';
import Modal from '../../../components/modal';
import FileUploadField from '../../../components/file-upload-field';
import { __ } from '@wordpress/i18n';
import Button from '../../../components/button';
import Notice from '../../../components/notices/notice';

export class ImportModal extends React.Component {
	static defaultProps = {
		loading: false,
		onClose: () => false,
		onImport: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			file: null,
			sizeError: false,
			typeError: false,
		};
	}

	handleFileChange(file) {
		this.setState({
			file,
			sizeError: file?.size && file?.size > 1000000,
			typeError: file && !this.isFileTypeValid(file),
		});
	}

	isFileTypeValid(file) {
		const fileName = file?.name + '';

		return fileName.endsWith('.json') || fileName.endsWith('.csv');
	}

	render() {
		const { loading, onClose, onImport } = this.props;
		const { file, sizeError, typeError } = this.state;
		const submissionDisabled = !file || sizeError || typeError;

		return (
			<Modal
				id="wds-import-redirects-modal"
				title={__('Import Redirects', 'wds')}
				description={__(
					'Import redirects from a JSON file below.',
					'wds'
				)}
				small={true}
				onClose={onClose}
				disableCloseButton={loading}
				footer={
					<React.Fragment>
						<div className="sui-flex-child-right">
							<Button
								text={__('Cancel', 'wds')}
								ghost={true}
								onClick={onClose}
								disabled={loading}
							/>
						</div>

						<div className="sui-actions-right">
							<Button
								text={__('Import', 'wds')}
								color="blue"
								onClick={() => onImport(file)}
								icon="sui-icon-upload-cloud"
								disabled={submissionDisabled}
								loading={loading}
							/>
						</div>
					</React.Fragment>
				}
			>
				<FileUploadField
					id="wds-import-redirects-file"
					acceptType=".json,.csv"
					label={__('Upload JSON file', 'wds')}
					onChange={(fl) => this.handleFileChange(fl)}
				/>

				{sizeError && (
					<Notice
						type="error"
						message={__(
							'Oops! The uploaded file is too large, please select a file not larger than 1MB.',
							'wds'
						)}
					/>
				)}

				{typeError && (
					<Notice
						type="error"
						message={__(
							'Whoops! Only .json or .csv file types are allowed.',
							'wds'
						)}
					/>
				)}

				<p className="sui-description" style={{ textAlign: 'left' }}>
					<small>
						{__(
							'Choose a JSON file (.json) with a max-size of 1MB containing your redirects. Redirects in CSV format without location-based rules are still supported. However, all future imports and exports for redirects with location rules must be in JSON format.',
							'wds'
						)}
					</small>
				</p>
			</Modal>
		);
	}
}
