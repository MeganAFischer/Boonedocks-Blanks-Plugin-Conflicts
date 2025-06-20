import React from 'react';
import { __ } from '@wordpress/i18n';
import Notice from '../../../components/notices/notice';
import Button from '../../../components/button';
import ConfigValues from '../../../es6/config-values';
import { createInterpolateElement } from '@wordpress/element';
import RequestUtil from '../../../utils/request-util';
import TextInputField from '../../../components/form-fields/text-input-field';
import UpsellNotice from '../../../components/notices/upsell-notice';
import { connect } from 'react-redux';

const isMember = ConfigValues.get('is_member', 'admin') === '1';

class MaxmindConfigActivation extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			key: '',
			errMsg: '',
			loading: false,
		};
	}

	handleChange(e) {
		this.setState({ key: e, errMsg: '' });
	}

	handleDownload(e) {
		e.preventDefault();

		this.setState({ loading: true }, () => {
			RequestUtil.post(
				'smartcrawl_download_geodb',
				ConfigValues.get('nonce', 'admin'),
				{
					license_key: this.state.key,
				}
			)
				.then((resp) => {
					this.setState(
						{
							key: '',
						},
						() => {
							this.props.updateProp('maxmind_license', resp.key);
							if (this.props.maxmindUpdate) {
								this.props.maxmindUpdate(
									'maxmind_license',
									resp.key
								);
								this.props.maxmindUpdate(
									'redirect',
									this.props
								);
							}
						}
					);
				})
				.catch((errMsg) => {
					this.setState({
						errMsg:
							errMsg === 'Unauthorized'
								? __(
										'Invalid license key. Please check that you have entered the correct key and try again.',
										'wds'
								  )
								: errMsg,
					});
				})
				.finally(() => {
					this.setState({
						loading: false,
					});
				});
		});

		return false;
	}

	render() {
		if (!isMember) {
			return (
				<UpsellNotice
					message={createInterpolateElement(
						__(
							'<a>Unlock with SmartCrawl Pro</a> to unlock the Location-Based Redirects feature.',
							'wds'
						),
						{
							a: (
								<a
									target="_blank"
									className="wds-maxmind-upsell-notice"
									href="https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=smartcrawl_redirect_location_based_settings_upsell"
								/>
							),
						}
					)}
				/>
			);
		}

		const { errMsg, key, loading } = this.state;

		return (
			<>
				<Notice
					type="info"
					message={createInterpolateElement(
						__(
							'Location-based redirection uses Maxmind’s GeoLite2 Database. <a1>Create a free account</a1> and get the <a2>license key</a2> to download the latest Geo IP Database.',
							'wds'
						),
						{
							a1: (
								<a
									target="_blank"
									href="https://www.maxmind.com/en/geolite2/signup"
									rel="noreferrer"
								/>
							),
							a2: (
								<a
									target="_blank"
									href="https://www.maxmind.com/en/accounts/current/license-key"
									rel="noreferrer"
								/>
							),
						}
					)}
				/>

				<TextInputField
					placeholder={__('Enter license key')}
					label={__('Maxmind License Key', 'wds')}
					prefix={
						<span className="sui-icon-key" aria-hidden="true" />
					}
					suffix={
						<Button
							icon="sui-icon-download"
							text={__('Download', 'wds')}
							onClick={(e) => this.handleDownload(e)}
							disabled={!key || !!errMsg}
							loading={loading}
						></Button>
					}
					value={key}
					onChange={(e) => this.handleChange(e)}
					loading={loading}
					isValid={!errMsg}
					errorMessage={errMsg}
				></TextInputField>

				{!!key && !errMsg && (
					<Notice
						type=""
						icon="sui-icon-info"
						message={__(
							'Note that it could take up to 5 mins to activate the license.',
							'wds'
						)}
					/>
				)}
			</>
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
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(MaxmindConfigActivation);
