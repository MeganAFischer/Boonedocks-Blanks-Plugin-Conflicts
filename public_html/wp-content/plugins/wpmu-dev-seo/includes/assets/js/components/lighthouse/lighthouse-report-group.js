import React from 'react';
import Notice from '../notices/notice';

export default class LighthouseReportGroup extends React.Component {
	static defaultProps = {
		id: '',
		label: '',
		description: '',
		notice: 0,
		failingCount: 0,
	};

	render() {
		const { id, label, description, notice, failingCount, children } =
			this.props;

		return (
			<div className="wds-vertical-tab-section sui-box" id={id}>
				<div className="sui-box-header">
					<h2 className="sui-box-title">{label}</h2>
				</div>
				<div className="sui-box-body">
					<p>{description}</p>

					{failingCount === 0 && !!notice && (
						<Notice
							type="success"
							icon="sui-icon-info"
							message={notice}
						/>
					)}

					{!!children && (
						<div className="sui-accordion sui-accordion-flushed">
							{children}
						</div>
					)}
				</div>
			</div>
		);
	}
}
