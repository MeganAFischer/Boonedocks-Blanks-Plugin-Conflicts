import React from 'react';
import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import ConfigValues from '../../../es6/config-values';
import Box from '../../../components/boxes/box';
import TextInputField from '../../../components/form-fields/text-input-field';
import { createInterpolateElement } from '@wordpress/element';
import Button from '../../../components/button';
import RequestUtil from '../../../utils/request-util';
import NoticeUtil from '../../../utils/notice-util';

const img = ConfigValues.get('empty_box_logo', 'admin') || false;

class Connect extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			error: false,
			accessId: '',
			secretKey: '',
			connecting: false,
		};
	}

	handleUpdate(key, val) {
		this.setState({ [key]: val });
	}

	handleConnect() {
		const { accessId, secretKey } = this.state;

		if (!accessId || !secretKey) {
			this.setState({ error: true });
			return;
		}

		const { updateOption, updateProp, toggleLoading } = this.props;

		this.setState({ connecting: true });
		toggleLoading();

		RequestUtil.post(
			`smartcrawl_update_moz_conn`,
			ConfigValues.get('nonce', 'admin'),
			{
				access_id: accessId,
				secret_key: secretKey,
			}
		)
			.then((resp) => {
				updateOption('access_id', accessId);
				updateOption('secret_key', secretKey);
				updateProp('metrics', resp.metrics);
			})
			.catch(() => {
				NoticeUtil.showErrorNotice(
					'smartcrawl-submodule-notice',
					__('Failed to connect to Moz.', 'wds')
				);
			})
			.finally(() => {
				this.setState({ connecting: false });
				toggleLoading();
			});
	}

	render() {
		const { loading } = this.props;
		const { error, accessId, secretKey, connecting } = this.state;

		return (
			<Box title={__('Moz', 'wds')}>
				<div className="sui-message sui-message-lg">
					{!!img && (
						<img
							src={img}
							aria-hidden="true"
							className="wds-disabled-image"
							alt={__('Disabled component', 'wds')}
						/>
					)}
					<div className="sui-message-content">
						<p>
							{__(
								'Moz provides reports that tell you how your site stacks up against the competition with all of the important SEO measurement tools - ranking, links, and much more.',
								'wds'
							)}
						</p>
					</div>
				</div>

				<div className="wds-moz-fields">
					<div className="wds-moz-fields-inner">
						<p className="sui-description">
							{createInterpolateElement(
								__(
									'Connect your Moz account. You can get the API credentials <a>here</a>',
									'wds'
								),
								{
									a: (
										<a
											href="https://moz.com/api/dashboard"
											target="_blank"
											rel="noreferrer"
										/>
									),
								}
							)}
						</p>

						<TextInputField
							label={__('Access ID', 'wds')}
							placeholder={__(
								'Enter your Moz Access ID',
								'wds'
							)}
							errorMessage={__(
								'Please enter a valid Moz Access ID',
								'wds'
							)}
							onChange={(val) =>
								this.handleUpdate('accessId', val)
							}
							isValid={!error || accessId}
							disabled={connecting || loading}
						/>

						<TextInputField
							label={__('Secret Key', 'wds')}
							placeholder={__(
								'Enter your Moz Secret Key',
								'wds'
							)}
							errorMessage={__(
								'Please enter a valid Moz Secret Key',
								'wds'
							)}
							onChange={(val) =>
								this.handleUpdate('secretKey', val)
							}
							isValid={!error || secretKey}
							disabled={connecting || loading}
						/>

						<Button
							color="blue"
							text={__('Connect', 'wds')}
							loading={connecting}
							disabled={loading || (!accessId && !secretKey)}
							onClick={() => this.handleConnect()}
						/>
					</div>
				</div>

				<p className="wds-moz-signup-notice">
					<small>
						{createInterpolateElement(
							__(
								"Don't have an account yet? <a>Sign up free</a>",
								'wds'
							),
							{
								a: (
									<a
										href="https://moz.com/products/api/pricing"
										target="_blank"
										rel="noreferrer"
									/>
								),
							}
						)}
					</small>
				</p>
			</Box>
		);
	}
}

const mapStateToProps = (state) => ({ ...state });

const mapDispatchToProps = {
	updateOption: (key, value) => ({
		type: 'UPDATE_OPTION',
		key,
		value,
	}),
	updateProp: (key, value) => ({
		type: 'UPDATE_PROP',
		key,
		value,
	}),
	toggleLoading: () => ({
		type: 'TOGGLE_LOADING',
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(Connect);
