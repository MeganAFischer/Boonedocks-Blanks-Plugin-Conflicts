import React from 'react';
import classnames from 'classnames';

export default class Notice extends React.Component {
	static defaultProps = {
		type: 'warning',
		message: '',
		icon: '',
		loading: false,
		actions: false,
	};

	render() {
		const { type, loading, actions, className } = this.props;

		const icon = this.props.icon ? this.props.icon : this.getIcon(type);

		return (
			<div
				className={classnames(
					'sui-notice',
					type && 'sui-notice-' + type,
					className
				)}
			>
				<div className="sui-notice-content">
					<div className="sui-notice-message">
						{!!loading && (
							<span
								className="sui-notice-icon sui-icon-loader sui-loading"
								aria-hidden="true"
							/>
						)}

						{icon && (
							<span
								className={classnames(
									'sui-notice-icon sui-md',
									icon
								)}
								aria-hidden="true"
							/>
						)}

						<p>{this.props.message}</p>

						{!!actions && (
							<div className="sui-notice-actions">{actions}</div>
						)}
					</div>
				</div>
			</div>
		);
	}

	getIcon(type) {
		const icons = {
			warning: 'sui-icon-warning-alert',
			error: 'sui-icon-warning-alert',
			info: 'sui-icon-info',
			success: 'sui-icon-check-tick',
			purple: 'sui-icon-info',
			'': 'sui-icon-info',
		};

		return icons.hasOwnProperty(type) ? icons[type] : 'sui-icon-info';
	}
}
