import React from 'react';
import { __ } from '@wordpress/i18n';
import Button from '../../../components/button';
import SideTabs from '../../../components/side-tabs';
import SettingsRow from '../../../components/settings-row';
import NoticeUtil from '../../../utils/notice-util';
import { createInterpolateElement } from '@wordpress/element';
import FloatingNoticePlaceholder from '../../../components/floating-notice-placeholder';
import TextInputField from '../../../components/form-fields/text-input-field';

export default class CodeType extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			codeType: '0',
		};
	}

	render() {
		const { codeType } = this.state;

		return (
			<SettingsRow
				label={__('Add Breadcrumbs to your Webpage', 'wds')}
				description={__(
					'You can add breadcrumbs to any page on your website using the ‘shortcode’ on the post editor, or the ‘PHP code’ on the template page.',
					'wds'
				)}
			>
				<FloatingNoticePlaceholder id="smartcrawl-breadcrumb-copied" />

				<SideTabs
					tabs={{
						0: __('Shortcode', 'wds'),
						1: __('PHP Code', 'wds'),
					}}
					value={codeType}
					onChange={(checked) => this.handleChange(checked)}
				>
					{codeType === '0' && (
						<>
							<TextInputField
								readOnly
								value="[smartcrawl_breadcrumbs]"
								suffix={
									<Button
										icon="sui-icon-copy"
										text={__('Copy', 'wds')}
										onClick={() =>
											this.handleCopy(codeType)
										}
									></Button>
								}
							></TextInputField>
							<p className="sui-description">
								{createInterpolateElement(
									__(
										'Copy the shortcode and paste it to the desired location in the post editor to display the breadcrumbs on your page or post. <a>Learn more</a>',
										'wds'
									),
									{
										a: (
											<a
												href="https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/#add-breadcrumbs"
												target="_blank"
												rel="noreferrer"
												className="learn-more"
											/>
										),
									}
								)}
							</p>
						</>
					)}
					{codeType === '1' && (
						<>
							<TextInputField
								readOnly
								value="<?php smartcrawl_breadcrumbs(); ?>"
								suffix={
									<Button
										icon="sui-icon-copy"
										text={__('Copy', 'wds')}
										onClick={() =>
											this.handleCopy(codeType)
										}
									></Button>
								}
							></TextInputField>
							<p className="sui-description">
								{createInterpolateElement(
									__(
										'Copy the PHP code and paste it in the desired location within template editor to display the breadcrumbs on your page or post. <a>Learn more</a>',
										'wds'
									),
									{
										a: (
											<a
												href="https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/#add-breadcrumbs"
												target="_blank"
												rel="noreferrer"
												className="learn-more"
											/>
										),
									}
								)}
							</p>
						</>
					)}
				</SideTabs>
			</SettingsRow>
		);
	}

	handleChange(value) {
		this.setState({
			codeType: value,
		});
	}

	handleCopy(codeType) {
		if (codeType === '0') {
			navigator.clipboard
				.writeText('[smartcrawl_breadcrumbs]')
				.then(() => {
					NoticeUtil.showSuccessNotice(
						'smartcrawl-breadcrumb-copied',
						__('Shortcode copied successfully.', 'wds'),
						false
					);
				})
				.catch(() => {
					NoticeUtil.showErrorNotice(
						'smartcrawl-breadcrumb-copied',
						__(
							'Shortcode could not be copied to clipboard.',
							'wds'
						),
						false
					);
				});
		}
		if (codeType === '1') {
			navigator.clipboard
				.writeText('<?php smartcrawl_breadcrumbs(); ?>')
				.then(() => {
					NoticeUtil.showSuccessNotice(
						'smartcrawl-breadcrumb-copied',
						__(
							'The PHP Code copied successfully.',
							'wds'
						),
						false
					);
				})
				.catch(() => {
					NoticeUtil.showErrorNotice(
						'smartcrawl-breadcrumb-copied',
						__(
							'The PHP Code could not be copied to clipboard.',
							'wds'
						),
						false
					);
				});
		}
	}
}
