import React from 'react';
import classnames from 'classnames';

export default class LighthouseToggle extends React.Component {
	static defaultProps = {
		text: '',
	};

	constructor(props) {
		super(props);

		this.state = {
			open: false,
		};
	}

	render() {
		return (
			<div
				className={classnames('wds-lh-toggle-container', {
					open: this.state.open,
				})}
			>
				<a
					className="wds-lh-toggle"
					href="#"
					onClick={(e) => this.handleClick(e)}
				>
					{this.props.text}
				</a>
				{this.state.open && (
					<div className="wds-lh-section">{this.props.children}</div>
				)}
			</div>
		);
	}

	handleClick(e) {
		e.preventDefault();

		this.setState({ open: !this.state.open });
	}
}
