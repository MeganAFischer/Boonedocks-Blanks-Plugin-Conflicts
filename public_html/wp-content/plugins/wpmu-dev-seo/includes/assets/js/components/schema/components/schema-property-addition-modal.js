import React from 'react';
import { __ } from '@wordpress/i18n';
import BoxSelectorModal from '../../box-selector-modal';
import { createInterpolateElement } from '@wordpress/element';
import Notice from '../../notices/notice';

export default class SchemaPropertyAdditionModal extends React.Component {
	static defaultProps = {
		description: '',
		options: {},
		onClose: () => false,
		onAction: () => false,
	};

	render() {
		const { description, options, onClose, onAction } = this.props;
		return (
			<BoxSelectorModal
				id="wds-add-property"
				title={__('Add Properties', 'wds')}
				description={description}
				actionButtonText={__('Add', 'wds')}
				actionButtonIcon="sui-icon-plus"
				onClose={onClose}
				onAction={onAction}
				options={options}
				noOptionsMessage={
					<div className="wds-box-selector-message">
						<h3>{__('No properties to add', 'wds')}</h3>
						<p className="sui-description">
							{__(
								'It seems that you have already added all the available properties.',
								'wds'
							)}
						</p>
					</div>
				}
				requiredNotice={
					<Notice
						className="wds-missing-properties-notice"
						message={this.getRequiredMessage()}
					/>
				}
			/>
		);
	}

	getRequiredMessage() {
		return createInterpolateElement(
			__(
				'You are missing properties that are required by Google ( <span>*</span> ). Make sure you include all of them so that your content will be eligible for display as a rich result. To learn more about schema type properties, see our <a>Schema Documentation</a>.',
				'wds'
			),
			{
				span: <span />,
				a: (
					<a
						target="_blank"
						href="https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/#schema"
						rel="noreferrer"
					/>
				),
			}
		);
	}
}
