import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import Button from '../../components/button';
import ConfigValues from '../../es6/config-values';
import Box from '../../components/boxes/box';
import RequestUtil from '../../utils/request-util';
import FloatingNoticePlaceholder from '../floating-notice-placeholder';
import NoticeUtil from '../../utils/notice-util';
import SettingsRow from '../settings-row';
import DisabledComponent from '../disabled-component';
import { connect } from 'react-redux';
import PropUtil from '../../utils/prop-util';

class SubmoduleBox extends React.Component {
	static defaultProps = {
		name: '',
		title: '',
		activateProps: {},
		deactivateProps: {},
		disableFooter: false,
	};

	constructor(props) {
		super(props);

		this.state = {
			activating: false,
		};
	}

	handleUpdate(data = false) {
		const { name, updateSubmodule, toggleLoading } = this.props;

		const { active, options } = this.props[name];

		if (!data) {
			data = { options };
			Object.keys(data.options).forEach((key) => {
				data.options[key] =
					typeof data.options[key] === 'boolean'
						? data.options[key]
							? 1
							: 0
						: data.options[key];
			});
		}

		this.setState({
			activating: data.hasOwnProperty('active'),
		});

		toggleLoading();

		RequestUtil.post(
			`smartcrawl_update_options_${name}`,
			ConfigValues.get('nonce', 'admin'),
			data
		)
			.then((resp) => {
				if (resp) {
					updateSubmodule(resp);
					NoticeUtil.showSuccessNotice(
						'smartcrawl-submodule-notice',
						__('Settings saved successfully.', 'wds')
					);
				}
			})
			.catch(() => {
				NoticeUtil.showErrorNotice(
					'smartcrawl-submodule-notice',
					data.hasOwnProperty('active')
						? sprintf(
								// translators: %s: activate/deactivate
								__(
									'Failed to %s the settings.',
									'wds'
								),
								active
									? __('deactivate', 'wds')
									: __('activate', 'wds')
						  )
						: __('Failed to save settings.', 'wds')
				);
			})
			.finally(() => {
				toggleLoading();
			});
	}

	render() {
		const {
			name,
			activateProps,
			deactivateProps,
			disableFooter,
			children,
		} = this.props;

		const { active, loading } = this.props[name];

		const { activating } = this.state;

		const boxProps = PropUtil.getValidProps(this.props, [
			'title',
			'className',
			'headerLeft',
			'headerRight',
			'footerLeft',
		]);

		return (
			<Box
				{...boxProps}
				footerLeft={
					!disableFooter &&
					active &&
					!Object.keys(deactivateProps).length && (
						<Button
							ghost
							icon="sui-icon-power-on-off"
							text={__('Deactivate', 'wds')}
							loading={activating && loading}
							disabled={!activating && loading}
							onClick={() => this.handleUpdate({ active: false })}
						></Button>
					)
				}
				footerRight={
					!disableFooter &&
					active && (
						<Button
							color="blue"
							icon="sui-icon-save"
							text={__('Save Settings', 'wds')}
							loading={!activating && loading}
							disabled={activating && loading}
							onClick={() => this.handleUpdate()}
						></Button>
					)
				}
			>
				<FloatingNoticePlaceholder id="smartcrawl-submodule-notice" />

				{active ? (
					<>
						{children}

						{Object.keys(deactivateProps).length > 0 && (
							<SettingsRow
								label={__('Deactivate', 'wds')}
								{...deactivateProps}
							>
								<Button
									ghost
									icon="sui-icon-power-on-off"
									text={__('Deactivate', 'wds')}
									loading={activating && loading}
									disabled={!activating && loading}
									onClick={() =>
										this.handleUpdate({ active: false })
									}
								></Button>
							</SettingsRow>
						)}
					</>
				) : (
					Object.keys(activateProps).length > 0 && (
						<DisabledComponent
							{...activateProps}
							nonceFields={false}
							button={
								<Button
									color="blue"
									text={__('Activate', 'wds')}
									loading={activating && loading}
									disabled={!activating && loading}
									onClick={() =>
										this.handleUpdate({ active: true })
									}
								/>
							}
							inner
						/>
					)
				)}
			</Box>
		);
	}
}

const mapStateToProps = (state) => ({ ...state });

const mapDispatchToProps = {
	updateProp: (key, value) => ({
		type: 'UPDATE_PROP',
		key,
		value,
	}),
	updateSubmodule: (value) => ({
		type: 'UPDATE_SUBMODULE',
		value,
	}),
	toggleLoading: () => ({
		type: 'TOGGLE_LOADING',
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(SubmoduleBox);
