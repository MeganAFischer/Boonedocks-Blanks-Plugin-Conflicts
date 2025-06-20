import React from 'react';
import classnames from 'classnames';
import AccordionItem from '../accordion-item';
import { __ } from '@wordpress/i18n';
import AccordionItemOpenIndicator from '../accordion-item-open-indicator';
import Button from '../button';
import FloatingNoticePlaceholder from '../floating-notice-placeholder';
import NoticeUtil from '../../utils/notice-util';
import ConfigValues from '../../es6/config-values';
import LighthouseUtil from './utils/lighthouse-util';
import UrlUtil from '../../utils/url-util';

export default class LighthouseCheckItem extends React.Component {
	static defaultProps = {
		id: '',
		successTitle: '',
		failureTitle: '',
		successDescription: '',
		failureDescription: '',
		copyDescription: '',
		actionButton: '',
	};

	render() {
		const { id, actionButton } = this.props;

		let styleClass, iconClass;

		if (id === 'structured-data') {
			styleClass = classnames('sui-default', 'wds-structured-data-check');
			iconClass = 'sui-icon-info';
		} else {
			styleClass = this.isPassed() ? 'sui-success' : 'sui-warning';
			iconClass = this.isPassed()
				? 'sui-icon-check-tick'
				: 'sui-icon-warning-alert';
		}

		return (
			<AccordionItem
				className={styleClass}
				open={UrlUtil.getQueryParam('check') === id}
				header={
					<React.Fragment>
						<div className="sui-accordion-item-title sui-accordion-col-4">
							<span
								aria-hidden="true"
								className={classnames(styleClass, iconClass)}
							/>
							{this.getTitle()}
						</div>
						<div className="sui-accordion-col-4">
							{id === 'structured-data' && (
								<Button
									color="ghost"
									icon="sui-icon-target"
									text={__('Testing Tool', 'wds')}
									onClick={(e) =>
										this.handleClickTestingTool(e)
									}
								/>
							)}
							<AccordionItemOpenIndicator />
						</div>
					</React.Fragment>
				}
				footer={
					!this.isPassed() &&
					(!!this.props.copyDescription || !!actionButton) && (
						<React.Fragment>
							{!!this.props.copyDescription && (
								<Button
									ghost
									text={__('Copy Audit', 'wds')}
									onClick={() => this.copyAudit()}
									tooltip={__(
										'Copy audit details',
										'wds'
									)}
								/>
							)}

							<div className="sui-actions-right">
								{!!actionButton && actionButton}
							</div>
						</React.Fragment>
					)
				}
			>
				<FloatingNoticePlaceholder id="wds-lighthouse-audit-copied" />
				{this.getDescription()}
			</AccordionItem>
		);
	}

	getTitle() {
		if (this.isPassed()) {
			return this.props.successTitle;
		}
		return this.props.failureTitle;
	}

	getDescription() {
		if (this.isPassed()) {
			return this.props.successDescription;
		}
		return this.props.failureDescription;
	}

	isPassed() {
		const report = ConfigValues.get('report', 'lighthouse'),
			check = report[this.props.id];

		if (!check) {
			return false;
		}

		return (
			check.score === null ||
			check.score === undefined ||
			check.score === 1
		);
	}

	copyAudit() {
		navigator.clipboard
			.writeText(this.props.copyDescription())
			.then(() => {
				NoticeUtil.showSuccessNotice(
					'wds-lighthouse-audit-copied',
					__(
						'The audit has been copied successfully.',
						'wds'
					),
					false
				);
			})
			.catch(() => {
				NoticeUtil.showErrorNotice(
					'wds-lighthouse-audit-copied',
					__(
						'Audit could not be copied to clipboard.',
						'wds'
					),
					false
				);
			});
	}

	handleClickTestingTool(e) {
		e.stopPropagation();

		const testingTool = LighthouseUtil.testingTool();

		window.open(testingTool, '_blank');
	}
}
