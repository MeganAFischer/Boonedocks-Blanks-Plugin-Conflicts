import React from 'react';
import { __ } from '@wordpress/i18n';
import Toggle from '../../../components/toggle';
import SettingsRow from '../../../components/settings-row';
import TextInputField from '../../../components/form-fields/text-input-field';
import { connect } from 'react-redux';

const optionalSettings = {
	allow_empty_tax: {
		label: __('Allow autolinks to empty taxonomies', 'wds'),
		description: __(
			'Allows autolinking to taxonomies that have no posts assigned to them.',
			'wds'
		),
	},
	excludeheading: {
		label: __('Prevent linking in heading tags', 'wds'),
		description: __(
			'Excludes headings from autolinking.',
			'wds'
		),
	},
	onlysingle: {
		label: __('Process only single posts and pages', 'wds'),
		description: __(
			'Process only single posts and pages',
			'wds'
		),
	},
	allowfeed: {
		label: __('Process RSS feeds', 'wds'),
		description: __(
			'Autolinking will also occur in RSS feeds.',
			'wds'
		),
	},
	casesens: {
		label: __('Case sensitive matching', 'wds'),
		description: __(
			'Only autolink the exact string match.',
			'wds'
		),
	},
	customkey_preventduplicatelink: {
		label: __('Prevent duplicate links', 'wds'),
		description: __(
			'Only link to a specific URL once per page/post.',
			'wds'
		),
	},
	target_blank: {
		label: __('Open links in new tab', 'wds'),
		description: __(
			'Adds the target=“_blank” tag to links to open a new tab when clicked.',
			'wds'
		),
	},
	rel_nofollow: {
		label: __('Nofollow autolinks', 'wds'),
		description: __(
			'Adds the nofollow meta tag to autolinks to prevent search engines following those URLs when crawling your website.',
			'wds'
		),
	},
	exclude_no_index: {
		label: __('Prevent linking on no-index pages', 'wds'),
		description: __(
			'Prevent autolinking on no-index pages.',
			'wds'
		),
	},
	exclude_image_captions: {
		label: __('Prevent linking on image captions', 'wds'),
		description: __(
			'Prevent links from being added to image captions.',
			'wds'
		),
	},
	disable_content_cache: {
		label: __('Prevent caching for autolinked content', 'wds'),
		description: __(
			'Some page builder plugins and themes conflict with object cache when automatic linking is enabled. Enable this option to disable object cache for autolinked content.',
			'wds'
		),
	},
};

class Settings extends React.Component {
	render() {
		const { options, updateOption, loading } = this.props;

		return (
			<>
				<SettingsRow
					label={__('Min lengths', 'wds')}
					description={__(
						'Define the shortest title and taxonomy length to autolink. Smaller titles will be ignored.',
						'wds'
					)}
				>
					<div className="sui-row sui-no-margin-bottom">
						<div className="sui-col-auto">
							<TextInputField
								type="number"
								label={__('Posts & pages', 'wds')}
								className="sui-input-sm"
								value={options.cpt_char_limit}
								onChange={(val) =>
									updateOption('cpt_char_limit', val)
								}
								disabled={loading}
							></TextInputField>
						</div>
						<div className="sui-col-auto">
							<TextInputField
								type="number"
								label={__(
									'Archives & taxonomies',
									'wds'
								)}
								className="sui-input-sm"
								value={options.tax_char_limit}
								onChange={(val) =>
									updateOption('tax_char_limit', val)
								}
								disabled={loading}
							></TextInputField>
						</div>
					</div>
					<p className="sui-description">
						{__(
							'We recommend a minimum of 10 chars for each type.',
							'wds'
						)}
					</p>
				</SettingsRow>
				<SettingsRow
					label={__('Max limits', 'wds')}
					description={__(
						'Set the max amount of links you want to appear per post.',
						'wds'
					)}
				>
					<div className="sui-row sui-no-margin-bottom">
						<div className="sui-col-auto">
							<TextInputField
								type="number"
								label={__('Per post total', 'wds')}
								className="sui-input-sm"
								value={options.link_limit}
								onChange={(val) =>
									updateOption('link_limit', val)
								}
								disabled={loading}
							></TextInputField>
						</div>
						<div className="sui-col-auto">
							<TextInputField
								type="number"
								label={__(
									'Per keyword group',
									'wds'
								)}
								className="sui-input-sm"
								value={options.single_link_limit}
								onChange={(val) =>
									updateOption('single_link_limit', val)
								}
								disabled={loading}
							></TextInputField>
						</div>
					</div>
					<p className="sui-description">
						{__(
							'Use 0 to allow unlimited automatic links.',
							'wds'
						)}
					</p>
				</SettingsRow>
				<SettingsRow
					label={__('Optional Settings', 'wds')}
					description={__(
						'Configure extra settings for absolute control over autolinking.',
						'wds'
					)}
				>
					{Object.keys(optionalSettings).map((key, index) => {
						return (
							<div className="sui-row" key={index}>
								<div className="sui-col-2">
									<Toggle
										value={key}
										label={optionalSettings[key].label}
										description={
											optionalSettings[key].description
										}
										checked={options[key]}
										onChange={(val) =>
											updateOption(key, val)
										}
									/>
								</div>
							</div>
						);
					})}
				</SettingsRow>
			</>
		);
	}
}

const mapStateToProps = (state) => ({ ...state.autolinks });

const mapDispatchToProps = {
	updateOption: (key, value) => ({
		type: 'UPDATE_OPTION',
		key,
		value,
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(Settings);
