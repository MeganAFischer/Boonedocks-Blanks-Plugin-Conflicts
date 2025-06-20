import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import Button from '../../../components/button';
import Notice from '../../../components/notices/notice';
import ReadabilityAnalysisContent from './readability-analysis-content';
import classnames from 'classnames';
import { createInterpolateElement } from '@wordpress/element';

export default class ReadabilityAnalysisContainer extends React.Component {
	static defaultProps = {
		analysis: {},
		loading: false,
		onRefresh: () => false,
	};

	levelDescription(level) {
		const veryEasy = __('Very easy to read', 'wds'),
			easy = __('Easy to read', 'wds'),
			fairlyEasy = __('Fairly easy to read', 'wds'),
			plain = __('Standard', 'wds'),
			fairlyDifficult = __('Fairly difficult to read', 'wds'),
			difficult = __('Difficult to read', 'wds'),
			confusing = __('Very difficult to read', 'wds');

		const map = {};

		map[veryEasy] = veryEasy.toLowerCase();
		map[easy] = easy.toLowerCase();
		map[fairlyEasy] = fairlyEasy.toLowerCase();
		map[plain] = __('in plain language', 'wds');
		map[fairlyDifficult] = fairlyDifficult.toLowerCase();
		map[difficult] = difficult.toLowerCase();
		map[confusing] = confusing.toLowerCase();

		return map[level]
			? createInterpolateElement(
					sprintf(
						/* translators: %s: Level description */
						__(
							'Your content is <strong>%s</strong>.',
							'wds'
						),
						map[level]
					),
					{ strong: <strong /> }
			  )
			: '';
	}

	render() {
		const { analysis, loading, onRefresh } = this.props;

		const totalScore = 100;
		const levelDescription = this.levelDescription(analysis.level);

		return (
			<div className="wds-readability-analysis-container">
				<div className="wds-readability-report wds-report">
					<div
						id="wds-readability-stats"
						className={classnames(
							'sui-summary',
							'sui-summary-sm',
							analysis.whitelabel_class
						)}
					>
						<div className="sui-summary-image-space"></div>

						<div className="sui-summary-segment">
							<div className="sui-summary-details">
								<span className="sui-summary-large">
									{analysis.score}
								</span>
								<span
									className={classnames(
										`sui-${analysis.state}`,
										analysis.state === 'success'
											? 'sui-icon-check-tick'
											: 'sui-icon-info'
									)}
								></span>
								<span className="sui-summary-percent">
									{'/'}
									{totalScore}
								</span>
								<span className="sui-summary-sub">
									{__('Readability score', 'wds')}
								</span>
							</div>
						</div>

						<div className="sui-summary-segment">
							{levelDescription && (
								<small>{levelDescription}</small>
							)}

							<Button
								className="wds-refresh-analysis wds-analysis-readability"
								color="ghost"
								icon="sui-icon-update"
								text={__('Refresh', 'wds')}
								loading={loading}
								disabled={!!analysis.refresh_disabled}
								onClick={onRefresh}
							></Button>
						</div>
					</div>

					<p className="wds-interstitial-text">
						<small>
							<strong>{__('Difficult', 'wds')}</strong>{' '}
							{__('= Less than 60', 'wds')}
						</small>
						<small>
							<strong>{__('OK', 'wds')}</strong>{' '}
							{__('= 60 to 70', 'wds')}
						</small>
						<small>
							<strong>{__('Easy', 'wds')}</strong>{' '}
							{__('= 70+', 'wds')}
						</small>
					</p>

					{!!loading && (
						<Notice
							type={false}
							className="wds-analysis-working"
							loading={loading}
							message={__(
								'Analyzing content. Please wait a few moments.',
								'wds'
							)}
						></Notice>
					)}

					<ReadabilityAnalysisContent
						state={analysis.state}
						ignored={analysis.ignored}
						level={analysis.level}
					></ReadabilityAnalysisContent>

					<p className="wds-interstitial-text">
						<small>
							{__(
								'More advanced readability tests coming soon.',
								'wds'
							)}
						</small>
					</p>
				</div>
			</div>
		);
	}
}
