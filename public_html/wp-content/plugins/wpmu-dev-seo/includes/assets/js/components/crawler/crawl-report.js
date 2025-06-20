import React from 'react';
import CrawlItemGroup3xx from './crawl-item-group-3xx';
import CrawlItemGroup4xx from './crawl-item-group-4xx';
import CrawlItemGroup5xx from './crawl-item-group-5xx';
import CrawlItemGroupInaccessible from './crawl-item-group-inaccessible';
import CrawlItemGroupSitemap from './crawl-item-group-sitemap';
import CrawlItemSitemap from './crawl-item-sitemap';
import CrawlItem from './crawl-item';
import CrawlRequest from './crawl-request';
import CrawlItemRedirectModal from './crawl-item-redirect-modal';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Button from '../button';
import ConfigValues from '../../es6/config-values';
import FloatingNoticePlaceholder from '../floating-notice-placeholder';
import NoticeUtil from '../../utils/notice-util';
import portalComponent from '../portal-component';
import memoizeOne from 'memoize-one';

export default class CrawlReport extends React.Component {
	static defaultProps = {
		onActiveIssueCountChange: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			issues: ConfigValues.get('issues', 'crawler') || {},
			redirects: ConfigValues.get('redirects', 'crawler') || [],
			redirectInProgress: false,
			requestInProgress: false,
		};

		this.getAllActiveIssueKeysMemoized = memoizeOne((issues) =>
			this.getAllActiveIssueKeys(issues)
		);
		this.getActiveSitemapIssuesMemoized = memoizeOne((issues) =>
			this.getActiveSitemapIssueCount(issues)
		);
	}

	componentDidUpdate() {
		const issues = this.getIssues();
		const activeIssues = this.getAllActiveIssueKeysMemoized(issues);
		const activeSitemapIssues = this.getActiveSitemapIssuesMemoized(issues);

		this.props.onActiveIssueCountChange(
			activeIssues.length,
			activeSitemapIssues
		);
	}

	render() {
		const issues = this.getIssues() || {};
		const potentialTypes = ['3xx', '4xx', '5xx', 'sitemap', 'inaccessible'];
		const types = Array.from(
			new Set([...Object.keys(issues), ...potentialTypes])
		);
		const allActiveIssueKeys = this.getAllActiveIssueKeysMemoized(issues);

		return (
			<React.Fragment>
				{allActiveIssueKeys.length > 0 && this.getIgnoreAllButton()}

				<p>
					{createInterpolateElement(
						sprintf(
							// translators: %s: plugin title
							__(
								'Here are potential issues <strong>%s</strong> has picked up. We recommend fixing them up to ensure you aren’t penalized by search engines - you can however ignore any of these warnings.',
								'wds'
							),
							ConfigValues.get('plugin_title', 'admin')
						),
						{ strong: <strong /> }
					)}
				</p>

				<FloatingNoticePlaceholder id="wds-crawl-report-notice" />

				<div className="sui-accordion wds-draw-left">
					{types.map((type) => {
						const GroupComponent = this.getGroupComponent(type);

						if (!GroupComponent) {
							return '';
						}

						const typeIssues = issues.hasOwnProperty(type)
							? issues[type]
							: {};
						const renderIssue =
							type === 'sitemap'
								? (key, issue) =>
										this.renderSitemapIssue(key, issue)
								: (key, issue) => this.renderIssue(key, issue);
						const renderControls =
							type === 'sitemap'
								? (tp, activeTab, count) =>
										this.renderSitemapControls(
											tp,
											activeTab,
											count
										)
								: (tp, activeTab, count) =>
										this.renderControls(
											tp,
											activeTab,
											count
										);

						return (
							<GroupComponent
								type={type}
								key={type}
								activeIssues={this.getActiveIssues(typeIssues)}
								ignoredIssues={this.getIgnoredIssues(
									typeIssues
								)}
								renderIssue={renderIssue}
								renderControls={renderControls}
							/>
						);
					})}
				</div>

				{this.maybeShowRedirectModal()}
			</React.Fragment>
		);
	}

	renderSitemapIssue(key, issue) {
		return (
			<CrawlItemSitemap
				{...issue}
				key={key}
				loading={this.state.requestInProgress === key}
				disabled={this.state.requestInProgress}
				onIgnore={() => this.ignoreItem(key)}
				onAddToSitemap={() => this.addToSitemap(key)}
				onRestore={() => this.restoreItem(key)}
			/>
		);
	}

	renderIssue(key, issue) {
		return (
			<CrawlItem
				{...issue}
				key={key}
				loading={this.state.requestInProgress === key}
				disabled={this.state.requestInProgress}
				onRedirect={() => this.startRedirecting(key)}
				onIgnore={() => this.ignoreItem(key)}
				onRestore={() => this.restoreItem(key)}
				redirects={this.state.redirects}
			/>
		);
	}

	addToSitemap(key) {
		this.setState(
			{
				requestInProgress: key,
			},
			() => {
				const issue = this.getIssue(key);
				CrawlRequest.addToSitemap(issue.path)
					.then((data) => {
						this.showSuccessNotice(
							__(
								'The missing item has been added to your sitemap as an extra URL.',
								'wds'
							)
						);

						this.setState({
							issues: data.issues,
						});
					})
					.catch(this.showError)
					.finally(() => this.markRequestAsComplete());
			}
		);
	}

	ignoreItem(key) {
		this.setState(
			{
				requestInProgress: key,
			},
			() => {
				CrawlRequest.ignoreIssue(key)
					.then((data) => {
						this.showSuccessNotice(
							__('The issue has been ignored.', 'wds')
						);

						this.setState({
							issues: data.issues,
						});
					})
					.catch(this.showError)
					.finally(() => this.markRequestAsComplete());
			}
		);
	}

	restoreItem(key) {
		this.setState(
			{
				requestInProgress: key,
			},
			() => {
				CrawlRequest.restoreIssue(key)
					.then((data) => {
						this.showSuccessNotice(
							__('The issue has been restored.', 'wds')
						);
						this.setState({
							issues: data.issues,
						});
					})
					.catch(this.showError)
					.finally(() => this.markRequestAsComplete());
			}
		);
	}

	maybeShowRedirectModal() {
		if (!this.state.redirectInProgress) {
			return false;
		}

		const key = this.state.redirectInProgress;
		const issue = this.getIssue(key);
		if (!issue) {
			return false;
		}

		return (
			<CrawlItemRedirectModal
				source={issue.path}
				destination={issue.redirect}
				onSave={(redirectUrl) => this.redirect(issue.path, redirectUrl)}
				onClose={() => this.stopRedirecting()}
				requestInProgress={this.state.requestInProgress}
			/>
		);
	}

	startRedirecting(key) {
		this.setState({ redirectInProgress: key });
	}

	redirect(source, destination) {
		this.setState(
			{
				requestInProgress: true,
			},
			() => {
				CrawlRequest.redirect(source, destination)
					.then((data) => {
						this.showSuccessNotice(
							__(
								'The URL has been redirected successfully.',
								'wds'
							)
						);

						this.setState({
							redirectInProgress: false,
							issues: data.issues,
						});
					})
					.catch(this.showError)
					.finally(() => this.markRequestAsComplete());
			}
		);
	}

	stopRedirecting() {
		this.setState({ redirectInProgress: false });
	}

	renderControls(type, activeTab, count) {
		if (activeTab === 'issues') {
			return this.getIgnoreAllOfTypeButton(type, count);
		}
		return this.getRestoreAllButton(type, count);
	}

	getIgnoreAllOfTypeButton(type, count) {
		return (
			<Button
				id={'wds-ignore-all-' + type}
				icon="sui-icon-eye-hide"
				text={count > 1 ? __('Ignore All', 'wds') : __('Ignore', 'wds')}
				ghost={true}
				loading={this.state.requestInProgress === type}
				disabled={this.state.requestInProgress}
				onClick={() => this.ignoreAllOfType(type)}
			/>
		);
	}

	getIgnoreAllButton() {
		const IgnoreAllButton = portalComponent(
			Button,
			'wds-ignore-all-button-placeholder'
		);
		return (
			<IgnoreAllButton
				id="wds-crawler-ignore-all"
				text={__('Ignore All', 'wds')}
				ghost={true}
				icon="sui-icon-eye-hide"
				onClick={() => this.ignoreAll()}
				loading={this.state.requestInProgress === 'ignore-all'}
				disabled={this.state.requestInProgress}
			/>
		);
	}

	getRestoreAllButton(type, count) {
		return (
			<Button
				id={'wds-restore-all-' + type}
				icon="sui-icon-plus"
				text={
					count > 1
						? __('Restore All', 'wds')
						: __('Restore', 'wds')
				}
				ghost={true}
				loading={this.state.requestInProgress === type}
				disabled={this.state.requestInProgress}
				onClick={() => this.restoreAll(type)}
			/>
		);
	}

	renderSitemapControls(type, activeTab, count) {
		if (activeTab !== 'issues') {
			return this.getRestoreAllButton(type);
		}

		return (
			<React.Fragment>
				{this.getIgnoreAllOfTypeButton(type, count)}

				<Button
					id="wds-add-all-to-sitemap"
					icon="sui-icon-plus"
					text={__('Add All to Sitemap', 'wds')}
					color="blue"
					loading={
						this.state.requestInProgress === 'add-all-to-sitemap'
					}
					disabled={this.state.requestInProgress}
					onClick={() => this.addAllToSitemap(type)}
				/>
			</React.Fragment>
		);
	}

	restoreAll(type) {
		const issues = this.getIssues();
		const keys = Object.keys(this.getIgnoredIssues(issues[type]));
		this.setState(
			{
				requestInProgress: type,
			},
			() => {
				CrawlRequest.restoreIssue(keys)
					.then((data) => {
						this.showSuccessNotice(
							__(
								'The issues have been restored.',
								'wds'
							)
						);

						this.setState({
							issues: data.issues,
						});
					})
					.catch(this.showError)
					.finally(() => this.markRequestAsComplete());
			}
		);
	}

	ignoreAll() {
		const issues = this.getIssues();
		const keys = this.getAllActiveIssueKeys(issues);
		this.setState(
			{
				requestInProgress: 'ignore-all',
			},
			() => {
				CrawlRequest.ignoreIssue(keys)
					.then((data) => {
						this.showSuccessNotice(
							__(
								'The issues have been ignored.',
								'wds'
							)
						);

						this.setState({
							issues: data.issues,
						});
					})
					.catch(this.showError)
					.finally(() => this.markRequestAsComplete());
			}
		);
	}

	getActiveSitemapIssueCount(issues) {
		const activeSitemapIssues = this.getActiveIssues(issues.sitemap || {});
		return Object.keys(activeSitemapIssues).length;
	}

	getAllActiveIssueKeys(issues) {
		const flattenedIssues = this.getFlattenedIssues(issues);
		return Object.keys(this.getActiveIssues(flattenedIssues));
	}

	getFlattenedIssues(issues) {
		return Object.keys(issues).reduce(
			(result, key) => Object.assign(result, issues[key]),
			{}
		);
	}

	ignoreAllOfType(type) {
		const issues = this.getIssues();
		const keys = Object.keys(this.getActiveIssues(issues[type]));
		this.setState(
			{
				requestInProgress: type,
			},
			() => {
				CrawlRequest.ignoreIssue(keys)
					.then((data) => {
						this.showSuccessNotice(
							__(
								'The issues have been ignored.',
								'wds'
							)
						);

						this.setState({
							issues: data.issues,
						});
					})
					.catch(this.showError)
					.finally(() => this.markRequestAsComplete());
			}
		);
	}

	addAllToSitemap(type) {
		const issues = this.getIssues();
		const keys = Object.keys(this.getActiveIssues(issues[type]));
		const paths = keys.map((key) => issues[type][key].path);
		this.setState(
			{
				requestInProgress: 'add-all-to-sitemap',
			},
			() => {
				CrawlRequest.addToSitemap(paths)
					.then((data) => {
						this.showSuccessNotice(
							__(
								'The missing items have been added to your sitemap as extra URLs.',
								'wds'
							)
						);

						this.setState({
							issues: data.issues,
						});

						setTimeout(() => window.location.reload(), 1500);
					})
					.catch(this.showError)
					.finally(() => this.markRequestAsComplete());
			}
		);
	}

	getGroupComponent(type) {
		const map = {
			'3xx': CrawlItemGroup3xx,
			'4xx': CrawlItemGroup4xx,
			'5xx': CrawlItemGroup5xx,
			inaccessible: CrawlItemGroupInaccessible,
			sitemap: CrawlItemGroupSitemap,
		};

		if (!map.hasOwnProperty(type)) {
			return false;
		}

		return map[type];
	}

	getIssue(key) {
		const issues = this.getIssues();
		let issue = false;
		Object.keys(issues).some((type) => {
			const issueKey = Object.keys(issues[type]).find(
				(issKey) => issKey === key
			);

			if (issueKey) {
				issue = issues[type][issueKey];
			}
			return issueKey;
		});
		return issue;
	}

	getActiveIssues(issues) {
		return this.filterIssues((key) => !issues[key].ignored, issues);
	}

	getIgnoredIssues(issues) {
		return this.filterIssues((key) => issues[key].ignored, issues);
	}

	filterIssues(filter, issues) {
		issues = issues || {};

		return Object.keys(issues)
			.filter(filter)
			.reduce(
				(result, key) => Object.assign(result, { [key]: issues[key] }),
				{}
			);
	}

	getIssues() {
		return this.state.issues || {};
	}

	showSuccessNotice(message) {
		NoticeUtil.showSuccessNotice('wds-crawl-report-notice', message, false);
	}

	showError(message) {
		NoticeUtil.showErrorNotice(
			'wds-crawl-report-notice',
			message ||
				__(
					'An unknown error occurred, please reload the page and try again.',
					'wds'
				),
			false
		);
	}

	markRequestAsComplete() {
		this.setState({ requestInProgress: false });
	}
}
