import React from 'react';

export default class DashboardWidget extends React.Component {
	static defaultProps = {
		title: '',
		description: '',
		icon: false,
		actions: false,
	};

	render() {
		const { title, description, icon, actions, children } = this.props;

		return (
			<div className="sui-box wds-dashboard-widget">
				<div className="sui-box-header">
					<h2 className="sui-box-title">
						{!!icon && <span className={icon} aria-hidden="true" />}
						{title}
					</h2>
				</div>
				<div className="sui-box-body">
					{!!description && <p>{description}</p>}
					{children}
				</div>

				{actions && <div className="sui-box-footer">{actions}</div>}
			</div>
		);
	}
}
