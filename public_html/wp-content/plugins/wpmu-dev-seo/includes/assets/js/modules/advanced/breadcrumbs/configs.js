import React from 'react';
import { __ } from '@wordpress/i18n';
import SettingsRow from '../../../components/settings-row';
import Toggle from '../../../components/toggle';
import TextInputField from '../../../components/form-fields/text-input-field';
import { connect } from 'react-redux';

const configOpts = {
	add_prefix: {
		label: __('Add Prefix to Breadcrumbs', 'wds'),
		description: __(
			'Enable this option to include a prefix at the beginning of the breadcrumbs.',
			'wds'
		),
	},
	home_trail: {
		label: __('Add homepage to the breadcrumbs trail', 'wds'),
		description: __(
			'Enable this option to add the homepage to the breadcrumbs.',
			'wds'
		),
	},
	hide_post_title: {
		label: __('Hide Post Title', 'wds'),
		description: __(
			'Enable this option to hide the post title from the breadcrumbs trail.',
			'wds'
		),
	},
	disable_woo: {
		label: __('Disable WooCommerce Breadcrumbs', 'wds'),
		description: __(
			'Enable this option to hide the default WooCommerce product breadcrumbs from your site.',
			'wds'
		),
	},
};

class Configs extends React.Component {
	render() {
		const { options, loading, updateOption } = this.props;

		return (
			<SettingsRow
				label={__('Configurations', 'wds')}
				description={__(
					'Enable and configure the additional breadcrumbs settings for your site.',
					'wds'
				)}
			>
				{Object.keys(configOpts).map((key, index) => {
					const config = configOpts[key];
					return (
						<div className="sui-row" key={index}>
							<div className="sui-col-2">
								<Toggle
									label={config.label}
									description={config.description}
									checked={options[key]}
									disabled={loading}
									onChange={(val) => updateOption(key, val)}
								/>

								{key === 'add_prefix' && !!options[key] && (
									<div className="sui-border-frame">
										<TextInputField
											label={__(
												'Prefix',
												'wds'
											)}
											placeholder={__(
												'Eg. Location',
												'wds'
											)}
											value={options.prefix}
											disabled={loading}
											onChange={(val) =>
												updateOption('prefix', val)
											}
										></TextInputField>
									</div>
								)}
								{key === 'home_trail' && !!options[key] && (
									<div className="sui-border-frame">
										<TextInputField
											placeholder={__(
												'Eg. Location',
												'wds'
											)}
											value={
												options.home_label ||
												__('Home', 'wds')
											}
											disabled={loading}
											onChange={(val) =>
												updateOption('home_label', val)
											}
										></TextInputField>
									</div>
								)}
							</div>
						</div>
					);
				})}
			</SettingsRow>
		);
	}
}

const mapStateToProps = (state) => ({ ...state.breadcrumbs });

const mapDispatchToProps = {
	updateOption: (key, value) => ({
		type: 'UPDATE_OPTION',
		key,
		value,
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(Configs);
