import React from 'react';
import Notice from '../../notices/notice';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import LighthouseUtil from '../utils/lighthouse-util';
import LighthouseCheckItem from '../lighthouse-check-item';
import LighthouseTable from '../tables/lighthouse-table';
import LighthouseTag from '../lighthouse-tag';
import ConfigValues from '../../../es6/config-values';

export default class LighthouseCheckCrawlableAnchors extends React.Component {
	static defaultProps = {
		id: 'crawlable-anchors',
	};

	render() {
		return (
			<LighthouseCheckItem
				id={this.props.id}
				successTitle={__('Links are crawlable', 'wds')}
				failureTitle={__('Links are not crawlable', 'wds')}
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
				<div className="wds-lh-section">
					<strong>{__('Overview', 'wds')}</strong>
					<p>
						{createInterpolateElement(
							__(
								"Google can follow links only if they are an <strong/>. Links that use other formats won't be followed by Google's crawlers. Google cannot follow links without a href, or links created by script events.",
								'wds'
							),
							{
								strong: (
									<strong>
										{__(
											'<a> tag with a href attribute',
											'wds'
										)}
									</strong>
								),
							}
						)}
					</p>
				</div>
			</React.Fragment>
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
						icon="sui-icon-info"
						message={__(
							'Way to go! It appears all your links are crawlable!',
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
				{this.commonDescription()}
				<div className="wds-lh-section">
					<strong>{__('Status', 'wds')}</strong>
					<Notice
						type="warning"
						icon="sui-icon-info"
						message={__(
							"We've detected some of your links are not crawlable.",
							'wds'
						)}
					/>

					{this.renderTable()}
				</div>

				<div className="wds-lh-section">
					<p>
						{__(
							"Here are examples of links that Google can and can't follow:",
							'wds'
						)}
					</p>

					<div className="wds-lh-highlight-container">
						<p>
							<strong className="wds-lh-green-word">
								{__('Can follow:')}
							</strong>
						</p>
						<div className="wds-lh-highlight wds-lh-highlight-success">
							<LighthouseTag
								tag="a"
								attributes={{ href: 'https://example.com' }}
							/>
							<br />
							<LighthouseTag
								tag="a"
								attributes={{ href: '/relative/path/file' }}
							/>
						</div>

						<p>
							<strong className="wds-lh-red-word">
								{__("Can't follow:")}
							</strong>
						</p>
						<div className="wds-lh-highlight wds-lh-highlight-error">
							<LighthouseTag tag="a" />
							<br />
							<LighthouseTag
								tag="a"
								attributes={{ routerLink: 'some/path' }}
							/>
							<br />
							<LighthouseTag
								tag="span"
								attributes={{ href: 'https://example.com' }}
							/>
							<br />
							<LighthouseTag
								tag="span"
								attributes={{
									onclick: 'goto("https://example.com")',
								}}
							/>
						</div>
					</div>
				</div>

				<div className="wds-lh-section">
					<strong>{__('Link to resolvable URLs', 'wds')}</strong>
					<p>
						{__(
							'Ensure that the URL linked to by your <a> tag is an actual web address that Googlebot can send requests to, for example:',
							'wds'
						)}
					</p>

					<div className="wds-lh-highlight-container">
						<p>
							<strong className="wds-lh-green-word">
								{__('Can resolve:')}
							</strong>
						</p>
						<div className="wds-lh-highlight wds-lh-highlight-success">
							<span className="wds-lh-tag">
								https://example.com/stuff
							</span>
							<br />
							<span className="wds-lh-tag">/products</span>
							<br />
							<span className="wds-lh-tag">
								/products.php?id=123
							</span>
						</div>

						<p>
							<strong className="wds-lh-red-word">
								{__("Can't resolve:")}
							</strong>
						</p>
						<div className="wds-lh-highlight wds-lh-highlight-error">
							<span className="wds-lh-tag">
								javascript:goTo(&apos;products&apos;)
							</span>
							<br />
							<span className="wds-lh-tag">
								javascript:window.location.href=&apos;/products&apos;
							</span>
							<br />
							<span className="wds-lh-tag">#</span>
							<br />
						</div>
					</div>
				</div>
			</React.Fragment>
		);
	}

	renderTable() {
		return (
			<LighthouseTable
				id={this.props.id}
				header={[
					__('Failing links', 'wds'),
					<React.Fragment key={0}>
						{createInterpolateElement(
							__('Link Text <span/>', 'wds'),
							{ span: this.getLinkTextTooltip() }
						)}
					</React.Fragment>,
				]}
				rows={this.getRows()}
			/>
		);
	}

	getLinkTextTooltip() {
		return (
			<React.Fragment>
				<span
					className="sui-tooltip sui-tooltip-constrained"
					data-tooltip={__(
						'To locate the Link text on your homepage, use the Find tool of your browser.',
						'wds'
					)}
				>
					<span
						className="sui-notice-icon sui-icon-info sui-sm"
						aria-hidden="true"
					/>
				</span>
			</React.Fragment>
		);
	}

	getActionButton() {
		return LighthouseUtil.editHomepageButton();
	}

	copyDescription() {
		return (
			sprintf(
				// translators: %s: Device label.
				__('Tested Device: %s', 'wds'),
				LighthouseUtil.getDeviceLabel()
			) +
			'\n' +
			__('Audit Type: Indexing audits', 'wds') +
			'\n\n' +
			__('Failing Audit: Links are not crawlable', 'wds') +
			'\n\n' +
			__(
				"Status: We've detected some of your links are not crawlable.",
				'wds'
			) +
			'\n\n' +
			LighthouseUtil.getFlattenedDetails(
				[__('Failing links', 'wds'), __('Link Text', 'wds')],
				this.getRows()
			) +
			__('Overview:', 'wds') +
			'\n' +
			__(
				"Google can follow links only if they are an <a> tag with a href attribute. Links that use other formats won't be followed by Google's crawlers. Google cannot follow links without a href, or links created by script events.",
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

	getRows() {
		const items = LighthouseUtil.getRawDetails(this.props.id)?.items;

		const rows = [];

		if (items) {
			items.forEach((item) => {
				rows.push([item.node?.snippet, item.node?.nodeLabel]);
			});
		}

		return rows;
	}
}
