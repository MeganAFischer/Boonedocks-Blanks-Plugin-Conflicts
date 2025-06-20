import React from 'react';
import Modal from '../../../components/modal';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Button from '../../../components/button';
import RedirectEditTabs from './redirect-edit-tabs';
import ConfigValues from '../../../es6/config-values';
import { connect } from 'react-redux';

const homeUrl = ConfigValues.get('home_url', 'admin').replace(/\/$/, '');

class RedirectModal extends React.Component {
	static defaultProps = {
		onSave: () => false,
		onClose: () => false,
	};

	render() {
		const { id, valid, loading, onSave, onClose } = this.props;

		return (
			<Modal
				id="wds-add-redirect-form"
				title={
					id
						? __('Edit Redirect', 'wds')
						: __('Add Redirect', 'wds')
				}
				description={createInterpolateElement(
					sprintf(
						// translators: %s: Home url.
						__(
							'Allowed formats include relative URLs like <strong>/cats</strong> or absolute URLs such as <strong>%s/cats</strong>.',
							'wds'
						),
						homeUrl
					),
					{
						strong: <strong />,
					}
				)}
				onEnter={() => onSave()}
				onClose={onClose}
				disableCloseButton={loading}
				enterDisabled={!valid}
				focusAfterOpen="wds-source-field"
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
				<RedirectEditTabs />
			</Modal>
		);
	}
}

const mapStateToProps = (state) => ({ ...state });

export default connect(mapStateToProps)(RedirectModal);
