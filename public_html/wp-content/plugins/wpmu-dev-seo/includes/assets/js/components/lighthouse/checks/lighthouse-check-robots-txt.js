import React from 'react';
import LighthouseCheckItem from '../lighthouse-check-item';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Notice from '../../notices/notice';
import LighthouseUtil from '../utils/lighthouse-util';
import LighthouseTable from '../tables/lighthouse-table';
import Button from '../../button';
import ConfigValues from '../../../es6/config-values';

export default class LighthouseCheckRobotsTxt extends React.Component {
	static defaultProps = {
		id: 'robots-txt',
	};

	render() {
		return (
			<LighthouseCheckItem
				id={this.props.id}
				successTitle={__('robots.txt is valid', 'wds')}
				failureTitle={__('robots.txt is not valid', 'wds')}
				successDescription={this.successDescription()}
				failureDescription={this.failureDescription()}
				copyDescription={() => this.copyDescription()}
				activeButton={this.getActionButton()}
			/>
		);
	}

	commonDescription() {
		return (
			<React.Fragment>
				<div className="wds-lh-section">
					<strong>{__('Overview', 'wds')}</strong>
					<p>
						{__(
							"The robots.txt file tells search engines which of your site's pages they can crawl. An invalid robots.txt configuration can cause two types of problems:",
							'wds'
						)}
					</p>
					<ul>
						<li>
							{__(
								'It can keep search engines from crawling public pages, causing your content to show up less often in search results.',
								'wds'
							)}
						</li>
						<li>
							{__(
								'It can cause search engines to crawl pages you may not want shown in search results.',
								'wds'
							)}
						</li>
					</ul>
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
							"We've detected a robots.txt file, nice work.",
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
							'The robots.txt file is not valid.',
							'wds'
						)}
					/>
					<p>
						{__(
							'If your robots.txt file is malformed, crawlers may not be able to understand how you want your website to be crawled or indexed.',
							'wds'
						)}
					</p>
					{this.renderTable()}
				</div>

				<div className="wds-lh-section">
					<strong>
						{__(
							'How to fix problems with robots.txt',
							'wds'
						)}
					</strong>
					<p>
						{createInterpolateElement(
							sprintf(
								// translators: %s: plugin title
								__(
									'<strong>%s</strong> can automatically add a robots.txt file for you, and link to your sitemap. Jump to <strong>Advanced Tools / Robots.txt Editor</strong> and fix the issues in your robots.txt file.',
									'wds'
								),
								ConfigValues.get('plugin_title', 'admin')
							),
							{ strong: <strong /> }
						)}
					</p>
				</div>
			</React.Fragment>
		);
	}

	renderTable() {
		return (
			<LighthouseTable
				id={this.props.id}
				header={[
					__('Line Number', 'wds'),
					__('Content', 'wds'),
					__('Error', 'wds'),
				]}
				rows={this.getRows()}
			/>
		);
	}

	getActionButton() {
		if (!LighthouseUtil.isTabAllowed('autolinks')) {
			return '';
		}

		return (
			<Button
				text={__('Edit Robots.txt', 'wds')}
				href={
					LighthouseUtil.tabUrl('autolinks') + '&tab=tabRobotsEditor'
				}
				icon="sui-icon-wrench-tool"
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
			__('Audit Type: Indexing audits', 'wds') +
			'\n\n' +
			__('Failing Audit: robots.txt is not valid', 'wds') +
			'\n\n' +
			__('Status: The robots.txt file is not valid.', 'wds') +
			'\n' +
			__(
				'If your robots.txt file is malformed, crawlers may not be able to understand how you want your website to be crawled or indexed.',
				'wds'
			) +
			'\n\n' +
			LighthouseUtil.getFlattenedDetails(
				[
					__('Line Number', 'wds'),
					__('Content', 'wds'),
					__('Error', 'wds'),
				],
				this.getRows()
			) +
			__('Overview:', 'wds') +
			'\n' +
			__(
				"The robots.txt file tells search engines which of your site's pages they can crawl. An invalid robots.txt configuration can cause two types of problems:",
				'wds'
			) +
			'\n\n' +
			__(
				'- It can keep search engines from crawling public pages, causing your content to show up less often in search results.',
				'wds'
			) +
			'\n' +
			__(
				'- It can cause search engines to crawl pages you may not want shown in search results.',
				'wds'
			) +
			'\n' +
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
				rows.push([item.index, item.line, item.message]);
			});
		}

		return rows;
	}
}
