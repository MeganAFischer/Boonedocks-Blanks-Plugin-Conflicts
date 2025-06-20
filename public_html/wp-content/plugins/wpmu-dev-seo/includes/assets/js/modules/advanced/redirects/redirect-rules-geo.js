import React from 'react';
import RedirectRulesGeoItem from './redirect-rules-geo-item';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Button from '../../../components/button';
import MaxmindConfigActivation from './maxmind-config-activation';
import { connect } from 'react-redux';
import ConfigValues from '../../../es6/config-values';
import Toggle from '../../../components/toggle';
import UpsellNotice from '../../../components/notices/upsell-notice';
import Notice from '../../../components/notices/notice';
import Checkbox from '../../../components/checkbox';
import RequestUtil from '../../../utils/request-util';

const isMember = ConfigValues.get('is_member', 'admin') === '1';

class RedirectRulesGeo extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			successNotice: false,
			isNewFeature:
				ConfigValues.get('new_feature_status', 'admin') !== '4',
		};
	}

	handleActivate() {
		this.setState({ successNotice: true }, () => {
			setTimeout(() => {
				this.setState({ successNotice: false });
			}, 3000);
		});
	}

	handleToggleRules() {
		this.props.toggleRules();

		if (this.state.isNewFeature) {
			this.setState({ isNewFeature: false }, () => {
				RequestUtil.post(
					'smartcrawl_new_feature_status',
					ConfigValues.get('nonce', 'admin'),
					{ step: 4 }
				);
			});
		}
	}

	askDeleting(ind = -1) {
		this.props.onAskRuleDelete(ind);
	}

	render() {
		const { rulesEnabled } = this.props;

		return (
			<>
				<div className="sui-form-field wds-toggle-field">
					<Toggle
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
							</>
						}
						checked={rulesEnabled}
						disabled={!isMember}
						onChange={() => this.handleToggleRules()}
					/>
				</div>
				{!isMember && (
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
										href="https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=smartcrawl_redirect_location_based_modal_upsell"
									/>
								),
							}
						)}
					/>
				)}
				{rulesEnabled && this.renderRules()}
			</>
		);
	}

	renderRules() {
		const { maxmindKey } = this.props;

		if (!maxmindKey) {
			return <MaxmindConfigActivation />;
		}

		const { dstDisabled, toggleTo } = this.props;
		const { successNotice } = this.state;

		return (
			<>
				{successNotice && (
					<Notice
						type="success"
						message={__(
							'Successfully connected to Maxmind’s GeoLite2 database.',
							'wds'
						)}
					/>
				)}

				<div className="sui-border-frame wds-redirect-to-opt">
					<Checkbox
						label={
							<>
								{__(
									'Disable default Redirect To',
									'wds'
								)}{' '}
								<span
									className="sui-tooltip"
									data-tooltip={__(
										'Check this option to use only location-based redirects.',
										'wds'
									)}
									style={{ '--tooltip-width': '184px' }}
								>
									<span
										className="sui-icon-info"
										aria-hidden="true"
									/>
								</span>
							</>
						}
						checked={dstDisabled}
						onChange={() => toggleTo()}
					></Checkbox>
				</div>

				{this.renderGeoItems()}
			</>
		);
	}

	renderGeoItems() {
		const { rules, ruleKeys, loading, createRule } = this.props;

		const conflictings = [];

		for (let i = 0; i < rules.length - 1; i++) {
			for (let j = i + 1; j < rules.length; j++) {
				const common = rules[i].countries.filter((country) =>
					rules[j].countries.includes(country)
				);

				if (common.length > 0) {
					conflictings.push(j);
				}
			}
		}

		return (
			<>
				{conflictings.length > 0 && (
					<Notice
						type="error"
						message={__(
							"You've added one or more conflicting location rules.",
							'wds'
						)}
					/>
				)}

				<div className="wds-redirect-rules-container">
					{rules.length > 0 && (
						<div className="sui-accordion">
							{rules.map((rule, index) => (
								<RedirectRulesGeoItem
									key={ruleKeys[index]}
									index={index}
									conflicting={conflictings.includes(index)}
								/>
							))}
						</div>
					)}

					<Button
						dashed={true}
						text={__('Add Rule', 'wds')}
						icon="sui-icon-plus"
						onClick={() => createRule()}
						disabled={loading}
					></Button>

					<p className="sui-description">
						{__(
							'Click the add rules button to add location-based rules.',
							'wds'
						)}
					</p>
				</div>
			</>
		);
	}
}

const mapStateToProps = (state) => ({
	...state,
	maxmindKey: state.maxmind_license,
});

const mapDispatchToProps = {
	toggleRules: () => ({
		type: 'TOGGLE_RULES',
	}),
	toggleTo: () => ({
		type: 'TOGGLE_TO',
	}),
	createRule: () => ({
		type: 'CREATE_RULE',
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(RedirectRulesGeo);
