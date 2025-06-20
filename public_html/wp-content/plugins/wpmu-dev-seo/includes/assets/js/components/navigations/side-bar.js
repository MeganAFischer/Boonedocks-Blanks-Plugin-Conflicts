import React from 'react';
import classnames from 'classnames';
import Select from '../input-fields/select';
import { __ } from '@wordpress/i18n';

export default class SideBar extends React.Component {
	static defaultProps = {
		selected: 0,
		disabled: false,
		items: [],
		onChange: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			selected: this.props.selected,
		};
	}

	handleChange(selected, e = false) {
		if (e) {
			e.preventDefault();
		}

		if (this.props.disabled) {
			return;
		}

		this.setState({ selected });
		this.props.onChange(selected);
	}

	render() {
		const { items, disabled } = this.props;
		const { selected } = this.state;

		const itemOpts = {};

		items.forEach((item, index) => {
			itemOpts[item.id || index] = item.title;
		});

		return (
			<div role="navigation" className="sui-sidenav">
				<ul className="sui-vertical-tabs sui-sidenav-hide-md">
					{items.map((item, index) => {
						const id = item.id || index;

						return (
							<li
								key={id}
								className={classnames('sui-vertical-tab', {
									'sui-disabled': disabled,
									current: id === selected,
								})}
							>
								<a
									href="#"
									role="button"
									onClick={(e) => this.handleChange(id, e)}
								>
									{item.title}
									{!!item.new_feature && (
										<span className="wds-new-feature-status"></span>
									)}
								</a>
							</li>
						);
					})}
				</ul>

				<div className="sui-sidenav-settings">
					<div className="sui-form-field sui-sidenav-hide-lg">
						<label className="sui-label">
							{__('Navigate', 'wds')}
						</label>

						<Select
							options={itemOpts}
							selectedValue={selected}
							disabled={disabled}
							onSelect={(val) => this.handleChange(val)}
						></Select>
					</div>
				</div>
			</div>
		);
	}
}
