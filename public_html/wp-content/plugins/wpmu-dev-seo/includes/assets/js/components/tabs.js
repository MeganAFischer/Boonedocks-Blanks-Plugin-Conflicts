import React from 'react';
import classNames from 'classnames';

export default class Tabs extends React.Component {
	static defaultProps = {
		tabs: {},
		value: '',
		flushed: false,
		onChange: () => false,
	};

	render() {
		const { tabs, value, flushed, className = '' } = this.props;

		return (
			<div
				className={classNames(
					'sui-tabs',
					flushed ? 'sui-tabs-flushed' : '',
					className
				)}
			>
				<div role="tablist" className="sui-tabs-menu">
					{Object.keys(tabs).map((tabKey) => {
						return (
							<button
								type="button"
								role="tab"
								key={`tab--${tabKey}`}
								className={classNames('sui-tab-item', {
									active: value === tabKey,
								})}
								id={`tab--${tabKey}`}
								aria-controls={`tab-content--${tabKey}`}
								aria-selected={
									value === tabKey ? 'true' : 'false'
								}
								onClick={() => this.props.onChange(tabKey)}
							>
								{tabs[tabKey].label}
							</button>
						);
					})}
				</div>
				<div className="sui-tabs-content">
					{Object.keys(tabs).map((tabKey) => {
						return (
							<div
								role="tabpanel"
								tabIndex="0"
								key={tabKey}
								className={classNames('sui-tab-content', {
									active: value === tabKey,
								})}
								id={`tab-content--${tabKey}`}
								aria-labelledby={`tab--${tabKey}`}
							>
								{tabs[tabKey].component}
							</div>
						);
					})}
				</div>
			</div>
		);
	}
}
