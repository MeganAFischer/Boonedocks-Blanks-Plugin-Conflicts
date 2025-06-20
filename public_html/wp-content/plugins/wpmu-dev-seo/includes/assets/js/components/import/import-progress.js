import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import ProgressBar from '../progress-bar';

export default class ImportProgress extends React.Component {
	static defaultProps = {
		progress: 0,
		siteProgress: 0,
		isMultisite: false,
		sourceName: '',
	};

	render() {
		const { progress, siteProgress, sourceName, isMultisite } = this.props;

		return (
			<React.Fragment>
				<p>
					{sprintf(
						/* translators: %s: source plugin name */
						__(
							'Importing your %s settings, please keep this window open …',
							'wds-texdomain'
						),
						sourceName
					)}
				</p>
				{isMultisite && (
					<div className="wds-site-progress">
						<label className="sui-label">
							{__('Overall Progress', 'wds-texdomain')}
						</label>
						<ProgressBar progress={siteProgress} />
					</div>
				)}
				<div className="wds-post-progress">
					{isMultisite && (
						<label className="sui-label">
							{__('Current Subsite', 'wds-texdomain')}
						</label>
					)}
					<ProgressBar progress={progress} />
				</div>
			</React.Fragment>
		);
	}
}
