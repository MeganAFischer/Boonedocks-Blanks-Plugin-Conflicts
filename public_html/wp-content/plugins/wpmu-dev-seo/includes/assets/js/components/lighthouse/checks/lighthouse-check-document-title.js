import React from 'react';
import Notice from '../../notices/notice';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import LighthouseUtil from '../utils/lighthouse-util';
import LighthouseCheckItem from '../lighthouse-check-item';
import LighthouseToggle from '../lighthouse-toggle';
import LighthouseTag from '../lighthouse-tag';
import Button from '../../button';
import ConfigValues from '../../../es6/config-values';

export default class LighthouseCheckDocumentTitle extends React.Component {
	static defaultProps = {
		id: 'document-title',
	};

	render() {
		return (
			<LighthouseCheckItem
				id={this.props.id}
				successTitle={__(
					'Document has a <title> element',
					'wds'
				)}
				failureTitle={__(
					"Document doesn't have a <title> element",
					'wds'
				)}
				successDescription={this.successDescription()}
				failureDescription={this.failureDescription()}
				copyDescription={() => this.copyDescription()}
				actionButton={this.getActionButton()}
			/>
		);
	}

	getActionButton() {
		if (!LighthouseUtil.isTabAllowed('onpage')) {
			return '';
		}

		return (
			<Button
				href={LighthouseUtil.tabUrl('onpage')}
				icon="sui-icon-plus"
				text={__('Add Title', 'wds')}
			/>
		);
	}

	commonDescription() {
		return (
			<div className="wds-lh-section">
				<strong>{__('Overview', 'wds')}</strong>
				<p>
					{__(
						'Having a <title> element on every page helps all your users:',
						'wds'
					)}
				</p>
				<ul>
					<li>
						{__(
							'Search engine users rely on the title to determine whether a page is relevant to their search.',
							'wds'
						)}
					</li>
					<li>
						{__(
							'The title also gives users of screen readers and other assistive technologies an overview of the page. The title is the first text that an assistive technology announces.',
							'wds'
						)}
					</li>
				</ul>
			</div>
		);
	}

	successDescription() {
		return (
			<React.Fragment>
				{this.commonDescription()}

				<div className="wds-lh-section">
					<strong>{__('Status', 'wds')}</strong>
					<Notice
						type="success"
						message={__(
							'Your homepage has a <title> element, well done!',
							'wds'
						)}
						icon="sui-icon-info"
					/>
				</div>
			</React.Fragment>
		);
	}

	failureDescription() {
		return (
			<React.Fragment>
				{this.commonDescription()}
				<div className="wds-lh-section">
					<strong>{__('Status', 'wds')}</strong>
					<Notice
						type="warning"
						icon="sui-icon-info"
						message={__(
							"We couldn't find a <title> tag on your homepage.",
							'wds'
						)}
					/>
				</div>

				<div className="wds-lh-section">
					<strong>
						{__('How to add a title', 'wds')}
					</strong>
					<p>
						{createInterpolateElement(
							__(
								'Open the <strong>Titles & Meta</strong> editor and add a meta title (and description) for your homepage. While you’re there, set up your default format for all other post types to ensure you always have a good quality <title> output.',
								'wds'
							),
							{
								strong: <strong />,
							}
						)}
					</p>
				</div>

				<LighthouseToggle text={__('Read More - Best practices')}>
					<strong>
						{__('Tips for creating great titles', 'wds')}
					</strong>
					<p>
						{__(
							'Having a <title> element on every page helps all your users:'
						)}
					</p>
					<ul>
						<li>
							{__(
								'Use a unique title for each page.',
								'wds'
							)}
						</li>
						<li>
							{__(
								'Make titles descriptive and concise. Avoid vague titles like "Home."',
								'wds'
							)}
						</li>
						<li>
							{__(
								"Avoid keyword stuffing. It doesn't help users, and search engines may mark the page as spam.",
								'wds'
							)}
						</li>
						<li>
							{__(
								"It's OK to brand your titles, but do so concisely.",
								'wds'
							)}
						</li>
					</ul>

					<div className="wds-lh-highlight-container">
						<p>
							<strong className="wds-lh-red-word">
								{__('Don’t.', 'wds')}
							</strong>
							{__('Too vague.', 'wds')}
						</p>
						<div className="wds-lh-highlight wds-lh-highlight-error">
							<LighthouseTag tag="title">
								{__('Donut recipe', 'wds')}
							</LighthouseTag>
						</div>

						<p>
							<strong className="wds-lh-green-word">
								{__('Do. ')}
							</strong>
							{__('Descriptive yet concise.', 'wds')}
						</p>
						<div className="wds-lh-highlight wds-lh-highlight-success">
							<LighthouseTag tag="title">
								{__(
									"Mary's quick maple bacon donut recipe",
									'wds'
								)}
							</LighthouseTag>
						</div>
					</div>

					<p>
						{createInterpolateElement(
							__(
								"See Google's <a>Create good titles and snippets in Search Results</a> page for more details about these tips.",
								'wds'
							),
							{
								a: (
									<a
										href="https://developers.google.com/search/docs/advanced/appearance/good-titles-snippets"
										target="_blank"
										rel="noreferrer"
									/>
								),
							}
						)}
					</p>
				</LighthouseToggle>
			</React.Fragment>
		);
	}

	copyDescription() {
		return (
			sprintf(
				// translators: %s: Device label.
				__('Tested Device: %s', 'wds'),
				LighthouseUtil.getDeviceLabel()
			) +
			'\n' +
			__('Audit Type: Content audits', 'wds') +
			'\n\n' +
			__(
				"Failing Audit: Document doesn't have a <title> element",
				'wds'
			) +
			'\n\n' +
			__(
				"Status: We couldn't find a <title> tag on your homepage.",
				'wds'
			) +
			'\n\n' +
			__('Overview:', 'wds') +
			'\n' +
			__(
				'Having a <title> element on every page helps all your users:',
				'wds'
			) +
			'\n' +
			__(
				'- Search engine users rely on the title to determine whether a page is relevant to their search.',
				'wds'
			) +
			'\n' +
			__(
				'- The title also gives users of screen readers and other assistive technologies an overview of the page. The title is the first text that an assistive technology announces.',
				'wds'
			) +
			'\n\n' +
			createInterpolateElement(
				sprintf(
					// translators: %s: plugin title
					__(
						'For more information please check the SEO Audits section in <strong>%s</strong> plugin.',
						'wds'
					),
					ConfigValues.get('plugin_title', 'admin')
				),
				{ strong: <strong /> }
			)
		);
	}
}
