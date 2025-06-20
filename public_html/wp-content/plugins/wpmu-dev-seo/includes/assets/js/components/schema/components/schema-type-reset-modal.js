import React from 'react';
import Modal from '../../modal';
import { __ } from '@wordpress/i18n';
import Button from '../../button';

export default class SchemaTypeResetModal extends React.Component {
	static defaultProps = {
		onCancel: () => false,
		onReset: () => false,
	};

	render() {
		const { onCancel, onReset } = this.props;

		return (
			<Modal
				small={true}
				id="wds-confirm-property-reset"
				title={__('Are you sure?', 'wds')}
				onClose={onCancel}
				focusAfterOpen="wds-schema-property-reset-button"
				description={__(
					'Are you sure you want to dismiss your changes and turn back your properties list to default?',
					'wds'
				)}
			>
				<Button
					text={__('Cancel', 'wds')}
					onClick={onCancel}
					ghost={true}
				/>

				<Button
					text={__('Reset Properties', 'wds')}
					onClick={onReset}
					icon="sui-icon-refresh"
					id="wds-schema-property-reset-button"
				/>
			</Modal>
		);
	}
}
