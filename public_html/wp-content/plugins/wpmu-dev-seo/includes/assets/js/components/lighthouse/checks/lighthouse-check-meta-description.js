import React from 'react';
import LighthouseCheckItem from '../lighthouse-check-item';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Notice from '../../notices/notice';
import LighthouseUtil from '../utils/lighthouse-util';
import LighthouseToggle from '../lighthouse-toggle';
import LighthouseTag from '../lighthouse-tag';
import Button from '../../button';
import ConfigValues from '../../../es6/config-values';

export default class LighthouseCheckMetaDescription extends React.Component {
	static defaultProps = {
		id: 'meta-description',
	};

	render() {
		return (
			<LighthouseCheckItem
				id={this.props.id}
				successTitle={__(
					'Document has a meta description',
					'wds'
				)}
				failureTitle={__(
					'Document does not have a meta description',
					'wds'
				)}
				successDescription={this.successDescription()}
				failureDescription={this.failureDescription()}
				copyDescription={() => this.copyDescription()}
				actionButton={this.getActionButton()}
			/>
		);
	}

	commonDescription() {
		return (
			<React.Fragment>
				<strong>{__('Overview', 'wds')}</strong>
				<p>
					{createInterpolateElement(
						__(
							'The <strong><meta name="description"></strong> element provides a summary of a page\'s content that search engines include in search results. A high-quality, unique meta description makes your page appear more relevant and can increase your search traffic.',
							'wds'
						),
						{ strong: <strong /> }
					)}
				</p>
			</React.Fragment>
		);
	}

	successDescription() {
		return (
			<React.Fragment>
				<div className="wds-lh-section">{this.commonDescription()}</div>

				<div className="wds-lh-section">
					<strong>{__('Status', 'wds')}</strong>
					<Notice
						type="success"
						icon="sui-icon-info"
						message={__(
							'Your homepage has a meta description, well done!',
							'wds'
						)}
					/>
				</div>
			</React.Fragment>
		);
	}

	failureDescription() {
		return (
			<React.Fragment>
				<div className="wds-lh-section">
					{this.commonDescription()}
					<p>{__('The audit fails if:', 'wds')}</p>
					<ul>
						<li>
							{createInterpolateElement(
								__(
									'If your page doesn\'t have a <strong><meta name="description"></strong> element.',
									'wds'
								),
								{
									strong: <strong />,
								}
							)}
						</li>
						<li>
							{createInterpolateElement(
								__(
									'The <strong>content</strong> attribute of the <strong><meta name="description"></strong> element is empty.',
									'wds'
								),
								{
									strong: <strong />,
								}
							)}
						</li>
					</ul>
				</div>

				<div className="wds-lh-section">
					<strong>{__('Status', 'wds')}</strong>
					<Notice
						type="warning"
						icon="sui-icon-info"
						message={__(
							"We couldn't find a meta description tag on your homepage.",
							'wds'
						)}
					/>
				</div>

				<div className="wds-lh-section">
					<strong>
						{__('How to add a meta description', 'wds')}
					</strong>
					<p>
						{createInterpolateElement(
							__(
								'Open the <strong>Titles & Meta</strong> editor and add a meta description (and title) for your homepage. While you\'re there, set up your default format for all other post types to ensure you always have a good quality <meta name="description"> output.',
								'wds'
							),
							{
								strong: <strong />,
							}
						)}
					</p>
				</div>

				<LighthouseToggle
					text={__('Read More - Best practices', 'wds')}
				>
					<strong>
						{__(
							'Meta description best practices',
							'wds'
						)}
					</strong>
					<ul>
						<li>
							{__(
								'Use a unique description for each page.',
								'wds'
							)}
						</li>
						<li>
							{__(
								'Make descriptions relevant and concise. Avoid vague descriptions like "Home page”.',
								'wds'
							)}
						</li>
						<li>
							{createInterpolateElement(
								__(
									"Avoid <a>keyword stuffing</a>. It doesn't help users, and search engines may mark the page as spam.",
									'wds'
								),
								{
									a: (
										<a
											href="https://developers.google.com/search/docs/advanced/guidelines/irrelevant-keywords"
											target="_blank"
											rel="noreferrer"
										/>
									),
								}
							)}
						</li>
						<li>
							{__(
								"Descriptions don't have to be complete sentences; they can contain structured data.",
								'wds'
							)}
						</li>
					</ul>

					<div className="wds-lh-highlight-container">
						<p>
							<strong className="wds-lh-red-word">
								{__('Don’t. ', 'wds')}
							</strong>
							{__('Too vague.', 'wds')}
						</p>
						<div className="wds-lh-highlight wds-lh-highlight-error">
							<LighthouseTag
								tag="meta"
								attributes={{
									name: 'description',
									content: __(
										'Donut recipe',
										'wds'
									),
								}}
							/>
						</div>

						<p>
							<strong className="wds-lh-green-word">
								{__('Do. ')}
							</strong>
							{__('Descriptive yet concise.', 'wds')}
						</p>
						<div className="wds-lh-highlight wds-lh-highlight-success">
							<LighthouseTag
								tag="meta"
								attributes={{
									name: 'description',
									content: __(
										"Mary's simple recipe for maple bacon donuts makes a sticky, sweet treat with just a hint of salt that you'll keep coming back for.",
										'wds'
									),
								}}
							/>
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

	getActionButton() {
		if (!LighthouseUtil.isTabAllowed('onpage')) {
			return '';
		}

		return (
			<Button
				href={LighthouseUtil.tabUrl('onpage')}
				icon="sui-icon-plus"
				text={__('Add Description', 'wds')}
			/>
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
				'Failing Audit: Document does not have a meta description',
				'wds'
			) +
			'\n\n' +
			__(
				"Status: We couldn't find a meta description tag on your homepage.",
				'wds'
			) +
			'\n\n' +
			__('Overview:', 'wds') +
			'\n' +
			__(
				'The <meta name="description"> element provides a summary of a page\'s content that search engines include in search results. A high-quality, unique meta description makes your page appear more relevant and can increase your search traffic.',
				'wds'
			) +
			'\n' +
			__('The audit fails if:', 'wds') +
			'\n' +
			__(
				'- If your page doesn\'t have a <meta name="description"> element.',
				'wds'
			) +
			'\n' +
			__(
				'- The content attribute of the <meta name="description"> element is empty.',
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
