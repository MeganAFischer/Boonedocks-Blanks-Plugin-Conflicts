import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import SettingsRow from '../../../components/settings-row';
import ConfigValues from '../../../es6/config-values';
import Select from '../../../components/input-fields/select';
import Toggle from '../../../components/toggle';
import Checkbox from '../../../components/checkbox';
import RequestUtil from '../../../utils/request-util';
import $ from 'jQuery';
import { getRedirectTypes } from '../../../utils/redirect-utils';
import MaxmindConfigDeactivation from './maxmind-config-deactivation';
import MaxmindConfigActivation from './maxmind-config-activation';
import { connect } from 'react-redux';
import SubmoduleBox from '../../../components/layout/submodule-box';

const isMember = ConfigValues.get('is_member', 'admin') === '1';

class RedirectSettings extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			isNewFeature:
				ConfigValues.get('new_feature_status', 'admin') !== '3',
		};

		this.newFeature = React.createRef();
	}

	componentDidMount() {
		if (!this.state.isNewFeature) {
			return;
		}

		$(window).on('scroll', () => {
			if (this.state.isNewFeature && this.isElementInViewport()) {
				this.setState({ isNewFeature: false }, () => {
					RequestUtil.post(
						'smartcrawl_new_feature_status',
						ConfigValues.get('nonce', 'admin'),
						{ step: 3 }
					);
				});
			}
		});
	}

	isElementInViewport() {
		if (!this.newFeature.current) {
			return;
		}

		const $target = $(this.newFeature.current);
		const elementTop = $target.offset().top;
		const elementBottom = elementTop + $target.height();
		const viewportTop = $(window).scrollTop();
		const viewportBottom = viewportTop + $(window).height();

		return elementBottom > viewportTop && elementTop < viewportBottom;
	}

	render() {
		const {
			active,
			options,
			maxmind_license: maxmindKey,
			updateOption,
		} = this.props;

		return (
			<SubmoduleBox
				name="redirects"
				title={
					active
						? __('Settings', 'wds')
						: __('URL Redirection', 'wds')
				}
				activateProps={{
					message: createInterpolateElement(
						sprintf(
							// translators: %s: plugin title
							__(
								'Configure <strong>%s</strong> to automatically redirect traffic from one URL to another. Use this tool if you have changed a page’s URL and wish to keep traffic flowing to the new page.',
								'wds'
							),
							ConfigValues.get('plugin_title', 'admin')
						),
						{ strong: <strong /> }
					),
				}}
				deactivateProps={{
					description: __(
						'No longer need URL Redirection? This will deactivate this feature and disable all redirects. Your redirects will not be deleted.',
						'wds'
					),
				}}
			>
				<SettingsRow
					label={__('Redirect attachments', 'wds')}
					description={__(
						'Redirect attachments to their respective file, preventing them from appearing in the SERPs.',
						'wds'
					)}
				>
					<Toggle
						label={__('Redirect attachments', 'wds')}
						checked={options.attachments}
						onChange={(val) => updateOption('attachments', val)}
					>
						<Checkbox
							label={__(
								'Redirect image attachments only',
								'wds'
							)}
							defaultChecked={options.images_only}
							onChange={(val) => updateOption('images_only', val)}
						></Checkbox>
						<p
							className="sui-description"
							style={{ marginLeft: '25px' }}
						>
							{__(
								'Select this option if you only want to redirect attachments that are an image.',
								'wds'
							)}
						</p>
					</Toggle>
				</SettingsRow>

				<SettingsRow
					label={__('Default Redirection Type', 'wds')}
					description={__(
						'Select the redirection type that you would like to be used as default.',
						'wds'
					)}
				>
					<Select
						minimumResultsForSearch="-1"
						options={getRedirectTypes()}
						selectedValue={options.default_type}
						onSelect={(type) => updateOption('default_type', type)}
					></Select>
				</SettingsRow>

				<SettingsRow
					label={
						<>
							{__('Location-based Rules', 'wds')}
							{!isMember && (
								<span
									className="sui-tag sui-tag-pro sui-tooltip"
									data-tooltip={__(
										'Unlock with SmartCrawl Pro to gain access to location-based redirections rules.',
										'wds'
									)}
								>
									{__('Pro', 'wds')}
								</span>
							)}
							{!!isMember &&
								ConfigValues.get(
									'new_feature_status',
									'admin'
								) !== '3' && (
									<span
										className="sui-tag sui-tag-green sui-tag-sm"
										ref={this.newFeature}
									>
										{__('New', 'wds')}
									</span>
								)}
						</>
					}
					description={__(
						'Add location-based redirect rules to ensure users see the most relevant content based on their locations.',
						'wds'
					)}
				>
					{maxmindKey ? (
						<MaxmindConfigDeactivation />
					) : (
						<MaxmindConfigActivation />
					)}
				</SettingsRow>
			</SubmoduleBox>
		);
	}
}

const mapStateToProps = (state) => ({ ...state.redirects });

const mapDispatchToProps = {
	updateOption: (key, value) => ({
		type: 'UPDATE_OPTION',
		key,
		value,
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(RedirectSettings);
