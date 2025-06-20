import React from 'react';
import classnames from 'classnames';

export default class Box extends React.Component {
	static defaultProps = {
		title: '',
		headerLeft: '',
		headerRight: '',
		footerLeft: '',
		footerRight: '',
	};

	render() {
		const {
			title,
			headerLeft,
			headerRight,
			footerLeft,
			footerRight,
			className,
			children,
		} = this.props;

		return (
			<div className={classnames('sui-box', className)}>
				<div className="sui-box-header">
					<h2 className="sui-box-title">{title}</h2>
					{!!headerLeft && (
						<div className="sui-actions-left">{headerLeft}</div>
					)}
					{!!headerRight && (
						<div className="sui-actions-right">{headerRight}</div>
					)}
				</div>

				<div className="sui-box-body">{children}</div>

				{(!!footerLeft || !!footerRight) && (
					<div className="sui-box-footer">
						{footerLeft}
						{!!footerRight && (
							<div className="sui-actions-right">
								{footerRight}
							</div>
						)}
					</div>
				)}
			</div>
		);
	}
}
