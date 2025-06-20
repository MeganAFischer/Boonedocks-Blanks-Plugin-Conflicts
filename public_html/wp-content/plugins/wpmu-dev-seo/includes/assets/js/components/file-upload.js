import React from 'react';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

export default class FileUpload extends React.Component {
	static defaultProps = {
		id: '',
		acceptType: '',
		onChange: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			file: null,
		};
	}

	handleFileChange(e) {
		const file = e.target.files[0];

		this.changeFile(file);
	}

	handleRemoval() {
		this.changeFile(null);
	}

	changeFile(file) {
		this.setState({ file });
		this.props.onChange(file);
	}

	render() {
		const { id, acceptType } = this.props;
		const { file } = this.state;
		const hasFile = !!file;
		const fileName = hasFile && file?.name;

		return (
			<div
				className={classnames('sui-upload', {
					'sui-has_file': hasFile,
					'sui-file-upload sui-file-browser': !hasFile,
				})}
			>
				<input
					id={id}
					type="file"
					readOnly="readonly"
					accept={acceptType}
					value=""
					onChange={(e) => this.handleFileChange(e)}
				/>

				<label className="sui-upload-button" htmlFor={id}>
					<span
						className="sui-icon-upload-cloud"
						aria-hidden="true"
					/>
					{__('Upload file', 'wds-texdomain')}
				</label>

				<div className="sui-upload-file">
					<span>{fileName}</span>

					<button
						type="button"
						aria-label={__('Remove file', 'wds-texdomain')}
						onClick={() => this.handleRemoval()}
					>
						<span className="sui-icon-close" aria-hidden="true" />
					</button>
				</div>
			</div>
		);
	}
}
