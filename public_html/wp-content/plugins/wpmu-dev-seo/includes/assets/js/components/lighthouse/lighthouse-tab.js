import React from 'react';
import LighthouseNoData from './lighthouse-no-data';
import LighthouseError from './lighthouse-error';
import LighthouseReport from './lighthouse-report';
import ConfigValues from '../../es6/config-values';

export default class LighthouseTab extends React.Component {
	render() {
		const report = ConfigValues.get('report', 'lighthouse');

		if (report.no_data) {
			return (
				<LighthouseNoData
					startTime={ConfigValues.get('start_time', 'lighthouse')}
					image={report.image}
				/>
			);
		}

		if (report.error) {
			return <LighthouseError message={report.message} />;
		}

		return (
			<LighthouseReport
				startTime={ConfigValues.get('start_time', 'lighthouse')}
			/>
		);
	}
}
