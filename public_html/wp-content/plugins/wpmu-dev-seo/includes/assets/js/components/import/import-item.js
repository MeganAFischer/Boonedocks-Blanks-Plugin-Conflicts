import React from 'react';
import Toggle from '../toggle';

export default class ImportItem extends React.Component {
	static defaultProps = {
		name: '',
		label: '',
		description: '',
		checked: false,
		onChange: () => false,
	};

	render() {
		return (
			<div className="wds-separator-top wds-import-item">
				<Toggle
					id={'wds-third-party-' + this.props.name}
					label={this.props.label}
					description={this.props.description}
					checked={this.props.checked}
					onChange={() => this.handleChange()}
				/>
			</div>
		);
	}

	handleChange() {
		this.props.onChange(this.props.name);
	}
}
