import React from 'react';
import classNames from 'classnames';

export default class SideTabs extends React.Component {
	static defaultProps = {
		tabs: {},
		value: '',
		onChange: () => false,
	};

	render() {
		const { tabs, children, value, onChange } = this.props;
		const childrenExist = !!React.Children.toArray(children).length;

		return (
			<div className="sui-side-tabs">
				<div className="sui-tabs-menu">
					{Object.keys(tabs).map((tabKey) => {
						return (
							<div
								className={classNames('sui-tab-item', {
									active: value === tabKey,
								})}
								key={tabKey}
								onClick={() => onChange(tabKey)}
							>
								{tabs[tabKey]}
							</div>
						);
					})}
				</div>

				{childrenExist && (
					<div className="sui-tabs-content">
						<div className="sui-tab-content active">
							<div className="sui-border-frame">{children}</div>
						</div>
					</div>
				)}
			</div>
		);
	}
}
