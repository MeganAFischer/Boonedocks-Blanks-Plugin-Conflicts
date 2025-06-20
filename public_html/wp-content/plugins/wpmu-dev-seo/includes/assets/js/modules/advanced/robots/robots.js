import React from 'react';
import { connect } from 'react-redux';
import DisabledComponent from '../../../components/disabled-component';
import { __, sprintf } from '@wordpress/i18n';
import Box from '../../../components/boxes/box';
import SubmoduleBox from '../../../components/layout/submodule-box';
import { createInterpolateElement } from '@wordpress/element';
import ConfigValues from '../../../es6/config-values';
import Notice from '../../../components/notices/notice';
import SettingsRow from '../../../components/settings-row';
import TextareaInputField from '../../../components/form-fields/textarea-input-field';
import Toggle from '../../../components/toggle';
import TextInputField from '../../../components/form-fields/text-input-field';
import $ from 'jQuery';

const fileUrl =
	ConfigValues.get('home_url', 'admin').replace(/\/$/, '') + '/robots.txt';

class Robots extends React.Component {
	componentDidMount() {
		$(document).on(
			'input propertychange',
			'.wds-robots-txt textarea',
			(e) => this.adjustHeight(e.target)
		);

		$('.wds-robots-txt textarea').each((ind, e) => this.adjustHeight(e));
	}

	componentDidUpdate() {
		$('.wds-robots-txt textarea').each((ind, e) => this.adjustHeight(e));
	}

	adjustHeight(e) {
		let scrollHeight = e.scrollHeight;

		if (!scrollHeight && e.value.includes('\n')) {
			scrollHeight = (e.value.split('\n').length + 1) * 22;
		}

		e.style.height = '1px';
		e.style.height = scrollHeight + 'px';
	}

	render() {
		const { already_exist: alreadyExist, rootdir_exist: rootdirExist } =
			this.props;

		if (alreadyExist) {
			return (
				<Box
					title={__('Robots.txt Editor', 'wds')}
					className="wds-robots-txt"
				>
					<DisabledComponent
						message={__(
							'Search engines use web crawlers (bots) to explore and index the internet. A robots.txt file is a critical text file that tells those bots what they can and can’t index, and where things are.',
							'wds'
						)}
						nonceFields={false}
						notice={createInterpolateElement(
							__(
								"We've detected an existing <a>robots.txt</a> file that we are unable to edit. You will need to remove it before you can enable this feature.",
								'wds'
							),
							{
								a: <a href={fileUrl} />,
							}
						)}
						inner
					/>
				</Box>
			);
		}

		if (!rootdirExist) {
			return (
				<Box
					title={__('Robots.txt Editor', 'wds')}
					className="wds-robots-txt"
				>
					<DisabledComponent
						message={__(
							'Search engines use web crawlers (bots) to explore and index the internet. A robots.txt file is a critical text file that tells those bots what they can and can’t index, and where things are.',
							'wds'
						)}
						notice={__(
							"We've detected your site is installed on a sub-directory. Robots.txt files only work when added to the root directory of a domain, so you'll need to change how your WordPress installation is set up to use this feature.",
							'wds'
						)}
						nonceFields={false}
						inner
					/>
				</Box>
			);
		}

		const {
			robots_output: robotsOutput,
			sitemap_enabled: sitemapEnabled,
			override_native: overrideNative,
			sitemap_url: sitemapUrl,
			options,
			loading,
			updateOption,
		} = this.props;

		return (
			<SubmoduleBox
				name="robots"
				title={__('Robots.txt Editor', 'wds')}
				className="wds-robots-txt"
				activateProps={{
					message: __(
						'Search engines use web crawlers (bots) to explore and index the internet. A robots.txt file is a critical text file that tells those bots what they can and can’t index, and where things are.',
						'wds'
					),
				}}
				deactivateProps={{
					description: __(
						'No longer need a Robots.txt file? This will deactivate this feature and remove the file.',
						'wds'
					),
				}}
			>
				<p>
					{__(
						'Search engines use web crawlers (bots) to explore and index the internet. A robots.txt file is a critical text file that tells those bots what they can and can’t index, and where things are.',
						'wds'
					)}
				</p>

				<Notice
					type="info"
					message={createInterpolateElement(
						sprintf(
							// translators: %s: Link to sitemap url.
							__(
								'Your robots.txt is active and visible to bots. You can view it at <a>%s</a>',
								'wds'
							),
							fileUrl
						),
						{
							a: (
								<a
									href={fileUrl}
									target="_blank"
									rel="noreferrer"
								/>
							),
						}
					)}
				/>

				<SettingsRow
					label={__('Output', 'wds')}
					description={__(
						'Here’s a preview of your current robots.txt output. Customize your robots.txt file below.',
						'wds'
					)}
				>
					<TextareaInputField
						label={__('Robots.txt preview', 'wds')}
						value={robotsOutput}
						readOnly
						disabled={loading}
					/>
				</SettingsRow>

				<SettingsRow
					label={__('Include Sitemap', 'wds')}
					description={__(
						"It's really good practice to instruct search engines where to find your sitemap. If enabled, we will automatically add the required code to your robots file.",
						'wds'
					)}
				>
					<Toggle
						label={__('Link to my Sitemap', 'wds')}
						checked={!options.sitemap_directive_disabled}
						disabled={loading}
						onChange={(val) =>
							updateOption('sitemap_directive_disabled', !val)
						}
					>
						{sitemapEnabled ? (
							<>
								<TextInputField
									label={__('Sitemap URL', 'wds')}
									value={sitemapUrl}
									readOnly
								/>

								{!!overrideNative && (
									<Notice
										type=""
										message={createInterpolateElement(
											sprintf(
												// translators: %s: plugin title
												__(
													"We've detected you're using <strong>%s</strong>'s built in sitemap and will output this for you automatically.",
													'wds'
												),
												ConfigValues.get(
													'plugin_title',
													'admin'
												)
											),
											{ strong: <strong /> }
										)}
									/>
								)}
							</>
						) : (
							<TextInputField
								label={__('Sitemap URL', 'wds')}
								description={createInterpolateElement(
									__(
										'Copy and paste the URL to your sitemap. E.g <strong>/sitemap.xml</strong> or <strong>https://example.com/sitemap.xml</strong>',
										'wds'
									),
									{ strong: <strong /> }
								)}
								value={options.custom_sitemap_url}
								disabled={loading}
								onChange={(val) =>
									updateOption('custom_sitemap_url', val)
								}
							/>
						)}
					</Toggle>
				</SettingsRow>
				<SettingsRow
					label={__('Customize', 'wds')}
					description={createInterpolateElement(
						__(
							'Customize the robots.txt output here. We have <a>full documentation</a> on a range of examples and options for your robots.txt file.',
							'wds'
						),
						{
							a: (
								<a
									href="https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/#robots-txt-editor"
									target="_blank"
									rel="noreferrer"
								/>
							),
						}
					)}
				>
					<TextareaInputField
						label={__(
							'Edit your robots.txt file',
							'wds'
						)}
						value={options.custom_directives}
						disabled={loading}
						onChange={(val) =>
							updateOption('custom_directives', val)
						}
					/>
				</SettingsRow>
			</SubmoduleBox>
		);
	}
}

const mapStateToProps = (state) => ({ ...state.robots });

const mapDispatchToProps = {
	updateOption: (key, value) => ({
		type: 'UPDATE_OPTION',
		key,
		value,
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(Robots);
