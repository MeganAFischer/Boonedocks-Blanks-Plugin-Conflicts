import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import ConfirmationModal from '../confirmation-modal';

export default class ConfigDeleteModal extends React.Component {
	static defaultProps = {
		configName: '',
		inProgress: false,
		onClose: () => false,
		onDelete: () => false,
	};

	render() {
		const { inProgress, onClose, onDelete } = this.props;

		return (
			<ConfirmationModal
				id="wds-delete-config-modal"
				title={__('Delete Configuration File', 'wds')}
				description={this.getDescription()}
				inProgress={inProgress}
				onClose={onClose}
				onDelete={onDelete}
			/>
		);
	}

	getDescription() {
		return createInterpolateElement(
			sprintf(
				// translators: %s: Number of configuration.
				__(
					'Are you sure you want to delete the <strong>%s</strong> config file? You will no longer be able to apply it to this or other connected sites.',
					'wds'
				),
				this.props.configName
			),
			{ strong: <strong /> }
		);
	}
}
