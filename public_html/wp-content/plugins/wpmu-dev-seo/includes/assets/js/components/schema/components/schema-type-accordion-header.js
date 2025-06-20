import React from 'react';
import $ from 'jQuery';
import { __ } from '@wordpress/i18n';
import SchemaTypeLocations from './schema-type-locations';
import Toggle from '../../toggle';
import SchemaTypeDropdown from './schema-type-dropdown';
import AccordionItemOpenIndicator from '../../accordion-item-open-indicator';

export default class SchemaTypeAccordionHeader extends React.Component {
	static defaultProps = {
		label: '',
		icon: '',
		isInvalid: false,
		disabled: false,
		conditions: [],
		onClick: () => false,
		onStatusChange: () => false,
		onRename: () => false,
		onDuplicate: () => false,
		onDelete: () => false,
	};

	render() {
		const {
			label,
			icon,
			conditions,
			isInvalid,
			disabled,
			onRename,
			onDuplicate,
			onDelete,
			onStatusChange,
		} = this.props;

		return (
			<div
				className="sui-accordion-item-header"
				onClick={(event) => this.handleClick(event)}
			>
				<div className="sui-accordion-col-5">
					<span className={icon} />
					<span>{label}</span>
					{isInvalid && (
						<span
							className="sui-tooltip sui-tooltip-constrained"
							data-tooltip={__(
								'This type has missing properties that are required by Google.',
								'wds'
							)}
						>
							<span
								className="wds-invalid-type-icon sui-icon-warning-alert sui-md"
								aria-hidden="true"
							/>
						</span>
					)}
				</div>

				<div className="sui-accordion-col-3">
					<SchemaTypeLocations conditions={conditions} />
				</div>

				<div className="sui-accordion-col-auto">
					<Toggle
						checked={!disabled}
						onChange={(checked) => onStatusChange(checked)}
					/>

					<SchemaTypeDropdown
						onRename={() => onRename()}
						onDuplicate={() => onDuplicate()}
						onDelete={() => onDelete()}
					/>

					<AccordionItemOpenIndicator />
				</div>
			</div>
		);
	}

	handleClick(event) {
		const { onClick } = this.props;
		const clickedTarget = $(event.target);
		if (clickedTarget.closest('.sui-accordion-item-action').length) {
			return true;
		}

		onClick();
	}
}
