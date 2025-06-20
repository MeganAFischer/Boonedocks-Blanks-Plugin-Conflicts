import React from 'react';
import Modal from '../../modal';
import { __ } from '@wordpress/i18n';
import Button from '../../button';

export default class SchemaTypeDeletionModal extends React.Component {
	static defaultProps = {
		onCancel: () => false,
		onDelete: () => false,
	};

	render() {
		const { onCancel, onDelete } = this.props;

		return (
			<Modal
				small={true}
				id="wds-confirm-type-deletion"
				title={__('Are you sure?', 'wds')}
				onClose={onCancel}
				focusAfterOpen="wds-schema-type-delete-button"
				description={__(
					'Are you sure you wish to delete this schema type? You can add it again anytime.',
					'wds'
				)}
			>
				<Button
					text={__('Cancel', 'wds')}
					onClick={onCancel}
					ghost={true}
				/>

				<Button
					text={__('Delete', 'wds')}
					onClick={onDelete}
					icon="sui-icon-trash"
					color="red"
					id="wds-schema-type-delete-button"
				/>
			</Modal>
		);
	}
}
