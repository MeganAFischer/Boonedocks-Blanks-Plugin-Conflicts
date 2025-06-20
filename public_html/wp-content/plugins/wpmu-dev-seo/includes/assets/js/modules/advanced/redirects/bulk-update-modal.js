import React from 'react';
import Modal from '../../../components/modal';
import { __, sprintf } from '@wordpress/i18n';
import Button from '../../../components/button';
import { connect } from 'react-redux';
import { createInterpolateElement } from '@wordpress/element';
import RedirectEditTabs from './redirect-edit-tabs';

class BulkUpdateModal extends React.Component {
	static defaultProps = {
		count: 0,
		onSave: () => false,
		onClose: () => false,
	};

	render() {
		const { count, loading, valid, onSave, onClose } = this.props;

		return (
			<Modal
				id="wds-bulk-update-redirects"
				title={__('Bulk Update', 'wds')}
				description={createInterpolateElement(
					sprintf(
						/* translators: %s: number of bulk items count */
						__(
							'Enable the bulk update actions you wish to perform. This will override the existing values for the <strong>%s</strong> selected item(s).',
							'wds'
						),
						count
					),
					{ strong: <strong /> }
				)}
				onEnter={() => onSave()}
				onClose={onClose}
				disableCloseButton={loading}
				enterDisabled={!valid}
				dialogClasses={{
					'sui-modal-md': true,
					'sui-modal-sm': false,
				}}
				small={true}
				footer={
					<>
						<Button
							text={__('Cancel', 'wds')}
							ghost={true}
							onClick={onClose}
							disabled={loading}
						/>
						<Button
							text={__('Apply', 'wds')}
							color="blue"
							onClick={() => onSave()}
							icon="sui-icon-check"
							disabled={!valid}
							loading={loading}
						/>
					</>
				}
			>
				<RedirectEditTabs bulkUpdate={true} />
			</Modal>
		);
	}
}

const mapStateToProps = (state) => ({ ...state });

export default connect(mapStateToProps)(BulkUpdateModal);
