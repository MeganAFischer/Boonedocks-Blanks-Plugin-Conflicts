import React from 'react';
import classnames from 'classnames';
import Button from './button';

export default class VerticalTab extends React.Component {
	static defaultProps = {
		id: '',
		title: '',
		actionsLeft: '',
		actionsRight: '',
		buttonText: '',
		isActive: false,
	};

	render() {
		const {
			id,
			title,
			actionsLeft,
			actionsRight,
			isActive,
			buttonText,
			className,
			children,
		} = this.props;

		return (
			<div
				id={!!id ? id : undefined}
				className={classnames(
					'wds-vertical-tab-section',
					'sui-box',
					className,
					isActive || 'hidden'
				)}
			>
				<div className="sui-box-header">
					<h2 className="sui-box-title">{title}</h2>
					{!!actionsLeft && (
						<div className="sui-actions-left">{actionsLeft}</div>
					)}
					{!!actionsRight && (
						<div className="sui-actions-right">{actionsRight}</div>
					)}
				</div>

				<div className="sui-box-body">{children}</div>

				{!!buttonText && (
					<div className="sui-box-footer">
						<Button
							type="submit"
							color="blue"
							icon="sui-icon-save"
							text={buttonText}
						></Button>
					</div>
				)}
			</div>
		);
	}
}
