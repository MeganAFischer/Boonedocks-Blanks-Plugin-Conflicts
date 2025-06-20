import React from 'react';
import classnames from 'classnames';

export default class TextInput extends React.Component {
	static defaultProps = {
		id: '',
		name: '',
		value: '',
		placeholder: '',
		type: 'text',
		readOnly: false,
		disabled: false,
		prefix: false,
		suffix: false,
		className: '',
		loading: false,
	};

	constructor(props) {
		super(props);

		this.state = { value: this.props.value };
	}

	// todo: determine why we need this.
	// eslint-disable-next-line no-unused-vars
	componentDidUpdate(prevProps, prevState, snapshot) {
		if (prevProps.value !== this.props.value) {
			this.setState({ value: this.props.value });
		}
	}

	render() {
		const { id, name, placeholder, type, disabled, readOnly, className } =
			this.props;
		const { value } = this.state;

		const props = {};

		if (id) {
			props.id = id;
		}

		if (name) {
			props.name = name;
		}

		if (placeholder) {
			props.placeholder = placeholder;
		}

		if (disabled) {
			props.disabled = disabled;
		}

		if (readOnly) {
			props.readOnly = readOnly;
		}

		props.value = value;

		return (
			<input
				{...props}
				type={type}
				className={classnames('sui-form-control', className)}
				onChange={(e) => this.handleChange(e)}
			/>
		);
	}

	handleChange(e) {
		if (this.props.beforeChange) {
			e.target.value = this.props.beforeChange(e.target.value);
		}

		if (this.props.onChange) {
			this.props.onChange(e.target.value);
		}

		this.setState({ value: e.target.value });
	}
}
