import React from 'react';
import classnames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import connectPropertyComponent from '../utils/connect-property-component';
import $ from 'jQuery';
import { calculateLabelSingle } from '../utils/property-utils';

class SchemaPropertyAccordionHeader extends React.Component {
	static defaultProps = {
		typeId: '',
		id: '',
		label: '',
		propertyName: '',
		description: '',
		type: '',
		required: false,
		requiredNotice: '',
		isAnAltVersion: false,
		labelSingle: '',
		isRepeatable: false,
		isCustom: false,
		disallowDeletion: false,
		invalid: false,
		properties: {},
		blueprint: {},
		onClick: () => false,
		onDelete: () => false,
		onRepeat: () => false,
		onChangeActiveVersion: () => false,
	};

	constructor(props) {
		super(props);

		this.labelSingleCalculated = calculateLabelSingle(
			this.props.propertyName
		);
	}

	render() {
		const {
			id,
			requiredNotice,
			description,
			label,
			type,
			required,
			isAnAltVersion,
			isRepeatable,
			labelSingle,
			disallowDeletion,
			invalid,
			isCustom,
			onRepeat,
			onDelete,
			onChangeActiveVersion,
		} = this.props;
		const requiredPropertyNotice = requiredNotice
			? requiredNotice
			: __('This property is required by Google.', 'wds');
		const labelSingleCalculated = this.labelSingleCalculated;

		return (
			<div
				className="sui-accordion-item-header"
				onClick={(event) => this.handleClick(event)}
			>
				<div className="sui-accordion-item-title">
					<span
						className={classnames({
							'sui-tooltip sui-tooltip-constrained':
								!!description,
						})}
						style={{ '--tooltip-width': '300px' }}
						data-tooltip={description}
					>
						{label}{' '}
						{isCustom && !!type && (
							<span className="sui-tag sui-tag-sm">{type}</span>
						)}
					</span>

					{required && (
						<span
							className="wds-required-asterisk sui-tooltip sui-tooltip-constrained"
							data-tooltip={requiredPropertyNotice}
						>
							*
						</span>
					)}

					{invalid && (
						<span
							className="sui-tooltip sui-tooltip-constrained"
							data-tooltip={__(
								'This section has missing properties that are required by Google.',
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

				<div className="sui-accordion-col-auto">
					{isAnAltVersion && (
						<div className="sui-accordion-item-action">
							<button
								onClick={onChangeActiveVersion}
								data-tooltip={__(
									'Change the type of this property',
									'wds'
								)}
								type="button"
								className="sui-button-icon sui-tooltip"
							>
								<span
									className="sui-icon-defer"
									aria-hidden="true"
								/>
							</button>
						</div>
					)}

					{isRepeatable && (
						<div className="sui-accordion-item-action">
							<button
								onClick={() => onRepeat(id)}
								type="button"
								data-tooltip={sprintf(
									// translators: %s: Property name.
									__('Add another %s', 'wds'),
									labelSingle ||
										labelSingleCalculated ||
										label
								)}
								className="sui-button-icon sui-tooltip"
							>
								<span
									className="sui-icon-plus"
									aria-hidden="true"
								/>
							</button>
						</div>
					)}

					{!disallowDeletion && (
						<React.Fragment>
							<div className="sui-accordion-item-action wds-delete-accordion-item-action">
								<span
									className="sui-icon-trash"
									onClick={() => onDelete(id)}
									aria-hidden="true"
								/>
							</div>
						</React.Fragment>
					)}

					<button
						className="sui-button-icon sui-accordion-open-indicator"
						type="button"
						aria-label={__('Open item', 'wds')}
					>
						<span
							className="sui-icon-chevron-down"
							aria-hidden="true"
						/>
					</button>
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

const SchemaPropertyAccordionHeaderContainer = connectPropertyComponent(
	SchemaPropertyAccordionHeader
);
export default SchemaPropertyAccordionHeaderContainer;
