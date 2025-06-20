import React from 'react';
import { __ } from '@wordpress/i18n';
import Notice from '../notices/notice';
import Button from '../button';

export default class ImportError extends React.Component {
	static defaultProps = {
		error: '',
		onRetry: () => false,
		onClose: () => false,
	};

	render() {
		const { error, onRetry, onClose } = this.props;

		return (
			<React.Fragment>
				<p>
					{__(
						'We have encountered an error while importing your data. You may retry the import or contact our support if the problem persists.',
						'wds-texdomain'
					)}
				</p>
				<Notice type="error" message={error} />

				<div className="wds-import-footer">
					<div className="cf">
						<Button
							className="wds-import-skip"
							color="ghost"
							text={__('Cancel', 'wds-texdomain')}
							onClick={onClose}
						/>

						<Button
							className="wds-import-main-action wds-reattempt-import"
							text={__('Try Again', 'wds-texdomain')}
							onClick={onRetry}
						/>
					</div>
				</div>
			</React.Fragment>
		);
	}
}
