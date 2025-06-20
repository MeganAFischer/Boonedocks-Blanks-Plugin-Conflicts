import React from 'react';
import SUI from 'SUI';
import $ from 'jQuery';
import TextInput from './text-input';
import { cloneDeep } from 'lodash-es';

export default class InsertVariables extends React.Component {
	static defaultProps = {
		value: '',
		variables: {},
		inputControl: TextInput,
	};

	constructor(props) {
		super(props);

		const { value, variables } = this.props;

		if (typeof variables === 'object' && Object.keys(variables).length) {
			this.refVars = React.createRef();
		}

		this.state = { value };
	}

	componentDidMount() {
		if (this.refVars && this.refVars.current) {
			const { dropdownWrapper } = this.props;
			const $select = $(this.refVars.current),
				$parentModal = $select.closest('.sui-modal-content'),
				$parent =
					dropdownWrapper ||
					($parentModal.length
						? $('#' + $parentModal.attr('id'))
						: $(`.sui-${window.Wds.version}`)),
				hasSearch = 'true' === $select.attr('data-search') ? 0 : -1;

			$select
				.SUIselect2({
					theme: 'vars',
					dropdownParent: $parent,
					templateResult: SUI.select.formatVars,
					templateSelection: SUI.select.formatVarsSelection,
					escapeMarkup: function escapeMarkup(markup) {
						return markup;
					},
					minimumResultsForSearch: hasSearch,
				})
				.on('select2:select', () => {
					this.handleSelect(0, $select.val());
					$select.val(null);
				});

			$select.val(null);
		}
	}

	render() {
		const { variables } = this.props;

		const InputControl = this.props.inputControl;
		const inputProps = cloneDeep(this.props);

		delete inputProps.label;
		delete inputProps.onChange;
		delete inputProps.variables;
		inputProps.value = this.state.value;

		return (
			<div className="sui-insert-variables">
				<InputControl
					{...inputProps}
					onChange={(value) => this.handleSelect(1, value)}
				></InputControl>
				<select className="sui-variables" ref={this.refVars}>
					{Object.keys(variables).map((key, index) => (
						<option key={index} value={key}>
							{variables[key]}
						</option>
					))}
				</select>
			</div>
		);
	}

	handleSelect(type, value) {
		const updatedValue = type === 1 ? value : this.state.value + value;

		if (this.props.onChange) {
			this.props.onChange(updatedValue);
		}

		this.setState({
			value: updatedValue,
		});
	}
}
