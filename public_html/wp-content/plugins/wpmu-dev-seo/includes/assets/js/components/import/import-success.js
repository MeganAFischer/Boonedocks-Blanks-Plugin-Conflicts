import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Notice from '../notices/notice';
import Button from '../button';

export default class ImportSuccess extends React.Component {
	static defaultProps = {
		source: '',
		sourceName: '',
		deactivationUrl: '',
		indexSettingsUrl: '',
		onClose: () => false,
	};

	render() {
		const { source, sourceName, deactivationUrl, onClose } = this.props;

		return (
			<React.Fragment>
				<p>
					{__(
						'All imported successfully, nice work!',
						'wds-texdomain'
					)}
				</p>

				<Notice
					type="success"
					message={sprintf(
						/* translators: %s: source plugin name. */
						__(
							'Your %s settings have been imported successfully and are now active.',
							'wds-texdomain'
						),
						sourceName
					)}
				/>

				{(source === 'yoast' || deactivationUrl) && (
					<React.Fragment>
						<Notice
							type="warning"
							message={this.getDeactivationMessage()}
						/>
					</React.Fragment>
				)}

				<div className="wds-import-footer">
					<div className="cf">
						<Button
							className="wds-import-skip"
							color="ghost"
							text={__('Close', 'wds-texdomain')}
							onClick={onClose}
						/>

						{deactivationUrl && (
							<Button
								className="wds-import-main-action wds-reattempt-import"
								icon="power-on-off"
								href={deactivationUrl}
								text={sprintf(
									/* translators: %s: source plugin name. */
									__('Deactivate %s', 'wds-texdomain'),
									sourceName
								)}
							/>
						)}
					</div>
				</div>
			</React.Fragment>
		);
	}

	getDeactivationMessage() {
		const { deactivationUrl, indexSettingsUrl, source, sourceName } =
			this.props;

		const texts = [];

		if (deactivationUrl) {
			texts.push(
				sprintf(
					/* translators: %s: source plugin name. */
					__(
						'We highly recommend you deactivate %s to avoid potential conflicts.',
						'wds-texdomain'
					),
					sourceName
				)
			);
		}

		if (source === 'yoast') {
			texts.push(
				createInterpolateElement(
					__(
						'Please recheck your <a>index settings</a> to make sure your website is correctly indexed.',
						'wds-texdomain'
					),
					{
						a: (
							// eslint-disable-next-line jsx-a11y/anchor-has-content
							<a
								href={indexSettingsUrl}
								target="_blank"
								rel="noreferrer"
							/>
						),
					}
				)
			);
		}

		return texts;
	}
}
