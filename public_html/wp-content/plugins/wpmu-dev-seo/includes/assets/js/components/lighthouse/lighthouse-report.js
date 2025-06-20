import React from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import LighthouseReportGroup from './lighthouse-report-group';
import Notice from '../notices/notice';
import { createInterpolateElement } from '@wordpress/element';
import Button from '../button';
import classnames from 'classnames';
import UrlUtil from '../../utils/url-util';
import ConfigValues from '../../es6/config-values';
import LighthouseCheckDocumentTitle from './checks/lighthouse-check-document-title';
import LighthouseCheckMetaDescription from './checks/lighthouse-check-meta-description';
import LighthouseCheckLinkText from './checks/lighthouse-check-link-text';
import LighthouseCheckHreflang from './checks/lighthouse-check-hreflang';
import LighthouseCheckCanonical from './checks/lighthouse-check-canonical';
import LighthouseCheckImageAlt from './checks/lighthouse-check-image-alt';
import LighthouseCheckHttpStatusCode from './checks/lighthouse-check-http-status-code';
import LighthouseCheckIsCrawlable from './checks/lighthouse-check-is-crawlable';
import LighthouseCheckRobotsTxt from './checks/lighthouse-check-robots-txt';
import LighthouseCheckCrawlableAnchors from './checks/lighthouse-check-crawlable-anchors';
import LighthouseCheckStructuredData from './checks/lighthouse-check-structured-data';

const isMember = ConfigValues.get('is_member', 'admin') === '1';

export default class LighthouseReport extends React.Component {
	static defaultProps = {
		startTime: false,
	};

	constructor(props) {
		super(props);

		this.report = ConfigValues.get('report', 'lighthouse');

		const timestamp = ConfigValues.get('timestamp', 'lighthouse');
		const remainingMinutes = Math.ceil(
			5 - (Date.now() / 1000 - timestamp) / 60
		);

		this.state = {
			remainingMinutes: remainingMinutes > 0 ? remainingMinutes : 0,
		};
	}

	componentDidMount() {
		this.updateRemainingMinutes();
		this.gotoActiveCheckItem();
	}

	render() {
		const { remainingMinutes } = this.state;

		const isActive =
			!UrlUtil.getQueryParam('tab') ||
			UrlUtil.getQueryParam('tab') === 'tab_lighthouse';
		const device = UrlUtil.getQueryParam('device');

		return (
			<React.Fragment>
				{!!remainingMinutes && (
					<Notice type="grey" message={this.getCooldownMessage()} />
				)}

				<div
					ref={(ref) => (this.ref = ref)}
					id="tab_lighthouse"
					className={classnames(
						'wds-lighthouse-device-' + device,
						!!isActive || 'hidden'
					)}
				>
					{this.retrieveGroups().map((group) => {
						const checks = group.checks;

						const failingCount = Object.keys(checks).reduce(
							(prev, curr) =>
								prev +
								(this.report[curr] === undefined ||
								this.report[curr].score === null ||
								this.report[curr].score === undefined ||
								this.report[curr].score === 1
									? 0
									: 1),
							0
						);

						return (
							<LighthouseReportGroup
								key={group.id}
								id={group.id}
								label={group.label}
								description={group.description}
								notice={group.notice}
								failingCount={failingCount}
							>
								{Object.keys(checks)
									.filter((key) => {
										return (
											(this.report[key] !== undefined &&
												this.report[key].weight) ||
											key === 'structured-data'
										);
									})
									.map((key) => {
										const HtmlTag = checks[key];

										return <HtmlTag key={key} id={key} />;
									})}
							</LighthouseReportGroup>
						);
					})}

					{!isMember && (
						<div className="wds-vertical-tab-section sui-box">
							<div id="wds-lighthouse-report-upsell-notice">
								<Notice
									type="purple"
									message={createInterpolateElement(
										__(
											'Upgrade to Pro to schedule automated tests and send white label email reports directly to your clients. Never miss a beat with your search engine optimization.<br/> <a/>',
											'wds'
										),
										{
											br: <br />,
											a: (
												<Button
													target="_blank"
													color="purple"
													text={__(
														'Unlock now with Pro',
														'wds'
													)}
													href="https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=smartcrawl_lighthouse_report_upsell_notice"
												/>
											),
										}
									)}
								/>
							</div>
						</div>
					)}
				</div>
			</React.Fragment>
		);
	}

	updateRemainingMinutes() {
		const { remainingMinutes } = this.state;

		if (!remainingMinutes) {
			return;
		}

		setTimeout(() => {
			this.setState({ remainingMinutes: remainingMinutes - 1 }, () => {
				this.updateRemainingMinutes();
			});
		}, 60000);
	}

	getCooldownMessage() {
		return createInterpolateElement(
			sprintf(
				// translators: %1$s: plugin title, %2$s: minutes.
				_n(
					'<strong>%1$s</strong> is just catching her breath - you can run another test in %2$s minute.',
					'<strong>%1$s</strong> is just catching her breath - you can run another test in %2$s minutes.',
					this.state.remainingMinutes
				),
				ConfigValues.get('plugin_title', 'admin'),
				this.state.remainingMinutes
			),
			{ strong: <strong /> }
		);
	}

	gotoActiveCheckItem() {
		setTimeout(() => {
			const domActiveItem = this.ref.querySelector(
				'.sui-accordion-item.sui-accordion-item--open'
			);
			if (domActiveItem) {
				domActiveItem.scrollIntoView();
			}
		}, 0);
	}

	retrieveGroups() {
		return [
			{
				id: 'content',
				label: __('Content audits', 'wds'),
				description: __(
					'Make sure search engines understand your content.',
					'wds'
				),
				notice: __(
					"You don't have any outstanding content audit – Google is loving it.",
					'wds'
				),
				checks: {
					'document-title': LighthouseCheckDocumentTitle,
					'meta-description': LighthouseCheckMetaDescription,
					'link-text': LighthouseCheckLinkText,
					hreflang: LighthouseCheckHreflang,
					canonical: LighthouseCheckCanonical,
					'image-alt': LighthouseCheckImageAlt,
				},
			},
			{
				id: 'visibility',
				label: __('Crawling and indexing audits', 'wds'),
				description: __(
					'Make sure search engines can crawl and index your page.',
					'wds'
				),
				notice: __(
					'Way to go! It appears your Homepage is crawlable and indexable!',
					'wds'
				),
				checks: {
					'http-status-code': LighthouseCheckHttpStatusCode,
					'is-crawlable': LighthouseCheckIsCrawlable,
					'robots-txt': LighthouseCheckRobotsTxt,
					//plugins: LighthouseCheckPlugins,
					'crawlable-anchors': LighthouseCheckCrawlableAnchors,
				},
			},
			{
				id: 'responsive',
				label: __('Responsive audits', 'wds'),
				description: __(
					'Make your page mobile friendly.',
					'wds'
				),
				notice: __(
					'Your page is mobile-friendly – Google is loving it.',
					'wds'
				),
				checks: {
					//viewport: LighthouseCheckViewport,
					//'font-size': LighthouseCheckFontSize,
					//'tap-targets': LighthouseCheckTapTargets,
				},
			},
			{
				id: 'manual',
				label: __('Manual audits', 'wds'),
				description: __(
					'The Lighthouse structured data audit is manual, so it does not affect your Lighthouse SEO score.',
					'wds'
				),
				checks: {
					'structured-data': LighthouseCheckStructuredData,
				},
			},
		];
	}
}
