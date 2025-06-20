import React from 'react';
import Modal from '../modal';
import { __ } from '@wordpress/i18n';
import Button from '../button';
import RequestUtil from '../../utils/request-util';
import { createInterpolateElement } from '@wordpress/element';
import NoticeUtil from '../../utils/notice-util';
import ProgressBar from '../progress-bar';
import AccordionItem from '../accordion-item';
import AccordionItemOpenIndicator from '../accordion-item-open-indicator';

export default class SitemapTroubleshootModal extends React.Component {
	static defaultProps = {
		nonce: '',
		sitemapUrl: '',
		onClose: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			inProgress: false,
			checked: false,
			fixed: false,
			progress: 0,
			issue: '',
			message: '',
			actionText: '',
			actionUrl: '',
			initialSitemapStatus: false,
		};
	}

	showGetStarted() {
		const { checked, inProgress } = this.state;

		return !checked || inProgress;
	}

	getStarted() {
		const { inProgress, progress } = this.state;

		return (
			<React.Fragment>
				{!inProgress && (
					<p className="sui-description">
						{__(
							'Click the button below to detect any problems with your sitemap. This will only take a few seconds.',
							'wds'
						)}
					</p>
				)}

				{inProgress && (
					<ProgressBar
						progress={progress}
						stateMessage={__('Checking sitemap…')}
					/>
				)}

				<div style={{ marginBottom: '20px' }}>
					<Button
						text={__('Start', 'wds')}
						icon={'sui-icon-arrow-right'}
						id="wds-start-troubleshooting-sitemap"
						onClick={() => this.handleAction()}
						loading={inProgress}
					/>
				</div>
			</React.Fragment>
		);
	}

	showSuccess() {
		const { checked, fixed, inProgress } = this.state;

		return checked && !inProgress && fixed;
	}

	success() {
		const { sitemapUrl } = this.props;
		const { fixed, issue } = this.state;

		return (
			<React.Fragment>
				{fixed && !issue && (
					<p className="sui-description">
						{__(
							'Hurray! No problems with your sitemap were detected.',
							'wds'
						)}
					</p>
				)}

				{fixed && issue && (
					<p className="sui-description">
						{__(
							'We detected and automatically resolved 1 problem with your sitemap. See additional details below.'
						)}
					</p>
				)}

				<Button
					className="wds-troubleshoot-open-sitemap-button"
					ghost={true}
					icon="sui-icon-sitemap"
					href={sitemapUrl}
					target="_blank"
					text={__('Open sitemap', 'wds-texdomain')}
				/>

				{issue && this.issueAccordion(true)}

				{this.checkAgainFooter()}
			</React.Fragment>
		);
	}

	checkAgainFooter() {
		const { onClose } = this.props;
		const { inProgress } = this.state;

		return (
			<div className="wds-troubleshoot-check-again-footer">
				<Button
					text={__('Cancel', 'wds')}
					onClick={onClose}
					ghost={true}
					disabled={inProgress}
				/>

				<Button
					text={__('Check Again', 'wds')}
					icon={'sui-icon-refresh'}
					id="wds-start-troubleshooting-sitemap"
					onClick={() => this.handleAction()}
					loading={inProgress}
				/>
			</div>
		);
	}

	showIssueList() {
		const { checked, fixed, inProgress } = this.state;

		return checked && !inProgress && !fixed;
	}

	issueList() {
		return (
			<React.Fragment>
				<p className="sui-description">
					{__(
						'We’ve detected 1 problem with your sitemap. Click the problem listed below for more information.'
					)}
				</p>

				{this.issueAccordion(false)}

				{this.checkAgainFooter()}
			</React.Fragment>
		);
	}

	issueAccordion(success) {
		const { issue, actionText, actionUrl } = this.state;
		const className = success
			? 'sui-icon-check-tick sui-success'
			: 'sui-icon-warning-alert sui-error';

		return (
			<div className="sui-accordion sui-accordion-flushed">
				<AccordionItem
					header={
						<React.Fragment>
							<div className="sui-accordion-item-title sui-accordion-col-4">
								<span
									aria-hidden="true"
									className={className}
								/>{' '}
								{issue}
							</div>

							<div className="sui-accordion-col-2">
								<AccordionItemOpenIndicator />
							</div>
						</React.Fragment>
					}
				>
					{this.formatMessage()}

					{!success && actionText && actionUrl && (
						<div style={{ marginTop: '15px' }}>
							<Button
								ghost={true}
								href={actionUrl}
								target="_blank"
								text={actionText}
							/>
						</div>
					)}
				</AccordionItem>
			</div>
		);
	}

	render() {
		const { onClose } = this.props;
		const { inProgress } = this.state;

		const showGetStarted = this.showGetStarted();
		const showSuccess = this.showSuccess();
		const showIssueList = this.showIssueList();

		return (
			<Modal
				id="wds-troubleshoot-sitemap-modal"
				title={__('Troubleshoot Sitemap', 'wds')}
				small={true}
				dialogClasses={{
					'sui-modal-md': true,
					'sui-modal-sm': false,
				}}
				onClose={onClose}
				disableCloseButton={inProgress}
				beforeTitle={
					<div className="wds-troubleshoot-header">
						{showSuccess && (
							<span
								className="sui-icon-check-tick sui-success"
								aria-hidden="true"
							/>
						)}
						{showIssueList && (
							<span
								className="sui-icon-cross-close sui-error"
								aria-hidden="true"
							/>
						)}
						{showGetStarted && (
							<span className="wds-troubleshoot-wrench-and-screw-icon" />
						)}
					</div>
				}
			>
				{showSuccess && this.success()}
				{showIssueList && this.issueList()}
				{showGetStarted && this.getStarted()}
			</Modal>
		);
	}

	formatMessage() {
		const { message } = this.state;

		return createInterpolateElement(message || '', {
			code: <code />,
			br: <br />,
			strong: <strong />,
		});
	}

	handleAction() {
		this.setState(
			{
				initialSitemapStatus: false,
				inProgress: true,
				progress: 0,
			},
			() => {
				this.startTroubleshooting()
					.then(() => this.showDummyProgress(15))
					.then(() => this.performFinalChecks())
					.catch(() => {
						const errorMessage = __(
							'The AJAX request failed.',
							'wds'
						);
						this.showErrorNotice(errorMessage);
						this.setState({
							issue: __('Request Failed', 'wds'),
							message: errorMessage,
						});
					})
					.finally(() => {
						this.setState(
							{
								progress: 100,
							},
							() => {
								setTimeout(() => {
									this.setState({
										inProgress: false,
										checked: true,
									});
								}, 1000);
							}
						);
					});
			}
		);
	}

	startTroubleshooting() {
		return new Promise((resolve, reject) => {
			this.post('wds_troubleshoot_sitemap')
				.then((data) => {
					this.setState(
						{
							initialSitemapStatus: data?.status,
						},
						resolve
					);
				})
				.catch(reject);
		});
	}

	showDummyProgress(seconds) {
		return new Promise((resolve) => {
			let promise = Promise.resolve();
			for (let i = 0; i < seconds * 2; i++) {
				promise = promise.then(() => this.updateDummyProgress());
			}
			promise.then(resolve);
		});
	}

	performFinalChecks() {
		const { initialSitemapStatus } = this.state;
		const { sitemapUrl } = this.props;
		return this.post('wds_recheck_sitemaps', {
			status: initialSitemapStatus,
			sitemap: sitemapUrl,
		}).then((data) => {
			this.setState({
				fixed: !!data?.fixed,
				issue: data?.issue,
				message: data?.message,
				actionText: data?.action_text,
				actionUrl: data?.action_url,
			});
		});
	}

	updateDummyProgress() {
		return new Promise((resolve) => {
			const { progress } = this.state;
			setTimeout(() => {
				const newProgress = progress + this.getRandom(1, 3);
				this.setState(
					{
						progress: newProgress > 99 ? 99 : newProgress,
					},
					resolve
				);
			}, 500);
		});
	}

	getRandom(min, max) {
		return Math.random() * (max - min) + min;
	}

	showErrorNotice(errorMessage) {
		NoticeUtil.showErrorNotice(
			'wds-troubleshoot-notice',
			errorMessage,
			false
		);
	}

	post(action, data = {}) {
		const { nonce } = this.props;
		return RequestUtil.post(action, nonce, data);
	}
}
