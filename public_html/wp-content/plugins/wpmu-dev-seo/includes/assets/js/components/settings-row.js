import React from 'react';
import classnames from 'classnames';

export default class SettingsRow extends React.Component {
	static defaultProps = {
		label: '',
		description: '',
		direction: 'row',
		flushed: false,
		slim: false,
	};

	render() {
		const { label, description, direction, flushed, slim, children } =
			this.props;

		return (
			<div
				className={classnames(
					slim ? 'sui-box-settings-slim-row' : 'sui-box-settings-row',
					flushed ? 'sui-flushed' : ''
				)}
			>
				{direction === 'row' && (
					<React.Fragment>
						<div className="sui-box-settings-col-1">
							<span className="sui-settings-label">{label}</span>
							<span className="sui-description">
								{description}
							</span>
						</div>

						<div className="sui-box-settings-col-2">{children}</div>
					</React.Fragment>
				)}

				{direction === 'column' && (
					<div className="sui-box-settings-col-2">
						{(label || description) && (
							<div className="sui-box-settings-header">
								<span className="sui-settings-label">
									{label}
								</span>
								<span className="sui-description">
									{description}
								</span>
							</div>
						)}
						<div className="sui-box-settings-content">
							{children}
						</div>
					</div>
				)}
			</div>
		);
	}
}
