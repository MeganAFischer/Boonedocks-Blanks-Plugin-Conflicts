import React from 'react';
import { __ } from '@wordpress/i18n';
import Button from '../../../components/button';
import classnames from 'classnames';
import AccordionItem from '../../../components/accordion-item';
import AccordionItemOpenIndicator from '../../../components/accordion-item-open-indicator';
import RequestUtil from '../../../utils/request-util';
import ConfigValues from '../../../es6/config-values';
import update from 'immutability-helper';
import GutenbergEditor from '../../../es6/gutenberg-editor';
import ClassicEditor from '../../../es6/classic-editor';

export default class ReadabilityAnalysisContent extends React.Component {
	static defaultProps = {
		ignored: false,
		state: '',
	};

	constructor(props) {
		super(props);

		// Check if Gutenberg is active.
		if (ConfigValues.get_bool('gutenberg_active', 'metabox')) {
			this.editor = new GutenbergEditor();
		} else {
			this.editor = new ClassicEditor();
		}
	}

	levelsMap() {
		const veryEasy = __('Very easy to read', 'wds'),
			easy = __('Easy to read', 'wds'),
			fairlyEasy = __('Fairly easy to read', 'wds'),
			plain = __('Standard', 'wds'),
			fairlyDifficult = __('Fairly difficult to read', 'wds'),
			difficult = __('Difficult to read', 'wds'),
			confusing = __('Very difficult to read', 'wds'),
			easyTag = __('Easy', 'wds'),
			plainTag = __('Standard', 'wds'),
			difficultTag = __('Difficult', 'wds'),
			fairlyDifficultTag = __('Fairly difficult', 'wds');

		const map = {};

		map[veryEasy] = {
			min: 90,
			max: 100,
			tag: easyTag,
		};
		map[easy] = {
			min: 80,
			max: 89.9,
			tag: easyTag,
		};
		map[fairlyEasy] = {
			min: 70,
			max: 79.9,
			tag: easyTag,
		};
		map[plain] = {
			min: 60,
			max: 69.9,
			tag: plainTag,
		};
		map[fairlyDifficult] = {
			min: 50,
			max: 59.9,
			tag: fairlyDifficultTag,
		};
		map[difficult] = {
			min: 30,
			max: 49.9,
			tag: difficultTag,
		};
		map[confusing] = {
			min: 0,
			max: 29.9,
			tag: difficultTag,
		};

		return map;
	}

	handleIgnore() {
		const id = 'readability';

		RequestUtil.post(
			'wds_analysis_ignore_check',
			ConfigValues.get('nonce', 'metabox'),
			{
				post_id: this.editor.get_data().get_id(),
				check_id: id,
			}
		).then(() => {
			this.setState({
				checks: update(this.state.checks, {
					[id]: { ignored: { $set: true } },
				}),
			});
		});
	}

	handleUnignore() {
		const id = 'readability';

		RequestUtil.post(
			'wds_analysis_unignore_check',
			ConfigValues.get('nonce', 'metabox'),
			{
				post_id: this.editor.get_data().get_id(),
				check_id: id,
			}
		).then(() => {
			this.setState({
				checks: update(this.state.checks, {
					[id]: { ignored: { $set: false } },
				}),
			});
		});
	}

	renderLevels() {
		return (
			<table className="sui-table">
				<thead>
					<tr>
						<th>{__('Score', 'wds')}</th>
						<th>{__('Description', 'wds')}</th>
					</tr>
				</thead>

				<tbody>
					{Object.keys(this.levelsMap()).map((label, index) => {
						const lvMap = this.levelsMap()[label];

						return (
							<tr key={index}>
								<td>
									{Math.ceil(lvMap.min)}
									{' - '}
									{Math.ceil(lvMap.max)}
								</td>
								<td>{label}</td>
							</tr>
						);
					})}
				</tbody>
			</table>
		);
	}

	render() {
		const { ignored, state, level } = this.props;

		return (
			<div className="wds-report-inner">
				<div className="wds-accordion sui-accordion">
					<AccordionItem
						className={classnames(
							'wds-check-item',
							`sui-${state}`,
							`wds-check-${state}`,
							{ disabled: !!ignored }
						)}
						header={
							<React.Fragment>
								<div className="sui-accordion-item-title sui-accordion-col-8">
									<span
										aria-hidden="true"
										className={classnames(
											`sui-${state}`,
											state === 'success'
												? 'sui-icon-check-tick'
												: 'sui-icon-info'
										)}
									></span>
									{__(
										'Flesch-Kincaid Test',
										'wds'
									)}
								</div>

								<div className="sui-accordion-col-4">
									{ignored ? (
										<Button
											className="wds-unignore"
											color="ghost"
											icon="sui-icon-undo"
											text={__(
												'Restore',
												'wds-texdomain'
											)}
											onClick={() =>
												this.handleUnignore()
											}
										></Button>
									) : (
										<React.Fragment>
											<span
												className={classnames(
													'sui-tag',
													`sui-tag-${state}`
												)}
											>
												{level}
											</span>
											<AccordionItemOpenIndicator />
										</React.Fragment>
									)}
								</div>
							</React.Fragment>
						}
					>
						<strong>{__('Overview', 'wds')}</strong>
						<p className="sui-description">
							{__(
								'The Flesch-Kincaid readability tests are readability tests designed to indicate how difficult a passage is to understand. Here are the benchmarks.',
								'wds'
							)}
						</p>
						{this.renderLevels()}

						<strong>{__('How to fix', 'wds')}</strong>
						<p className="sui-description">
							{__(
								'Try to use shorter sentences, with less difficult words to improve readability.',
								'wds'
							)}
						</p>

						<div className="wds-ignore-container">
							<Button
								className="wds-ignore"
								color="ghost"
								icon="sui-icon-eye-hide"
								text={__('Ignore', 'wds-texdomain')}
								onClick={() => this.handleIgnore()}
							></Button>

							<span>
								{__(
									'This will ignore warnings for this particular post.',
									'wds'
								)}
							</span>
						</div>
					</AccordionItem>
				</div>
			</div>
		);
	}
}
