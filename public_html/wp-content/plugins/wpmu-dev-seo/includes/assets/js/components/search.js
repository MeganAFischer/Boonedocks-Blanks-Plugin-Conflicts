import React from 'react';
import { debounce } from 'lodash-es';

export default class Search extends React.Component {
	static defaultProps = {
		placeholder: '',
		onChange: () => false,
	};

	render() {
		const { placeholder } = this.props;

		return (
			<div className="sui-search sui-control-with-icon">
				<span
					className="sui-icon-magnifying-glass-search"
					aria-hidden="true"
				/>
				<input
					type="text"
					className="sui-form-control wds-search-box"
					onChange={(e) => this.handleChange(e.target.value)}
					placeholder={placeholder}
				/>
			</div>
		);
	}

	handleChange(keyword) {
		if (!this.debounced) {
			this.debounced = debounce((e) => this.props.onChange(e), 500);
		}

		this.debounced(keyword);
	}
}
