import React from 'react';
import { __ } from '@wordpress/i18n';
import Button from './button';
import Modal from './modal';

export default class ConfirmationModal extends React.Component {
	static defaultProps = {
		id: '',
		title: '',
		description: '',
		inProgress: false,
		onClose: () => false,
		onDelete: () => false,
	};

	render() {
		const { id, title, description, inProgress, onClose, onDelete } =
			this.props;
		const cancelButtonId = id + '-cancel';

		return (
			<Modal
				id={id}
				title={title}
				description={description}
				small={true}
				disableCloseButton={inProgress}
				focusAfterOpen={cancelButtonId}
				onClose={onClose}
			>
				<Button
					id={cancelButtonId}
					ghost={true}
					disabled={inProgress}
					text={__('Cancel', 'wds')}
					onClick={onClose}
				/>

				<Button
					color="red"
					loading={inProgress}
					icon="sui-icon-trash"
					text={__('Delete', 'wds')}
					onClick={onDelete}
				/>
			</Modal>
		);
	}
}
