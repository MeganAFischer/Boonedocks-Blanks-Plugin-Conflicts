import React from 'react';
import classnames from 'classnames';
import AccordionItem from '../../../components/accordion-item';
import AccordionItemOpenIndicator from '../../../components/accordion-item-open-indicator';
import { __ } from '@wordpress/i18n';
import Button from '../../../components/button';

export default class SeoAnalysisCheckItem extends React.Component {
	static defaultProps = {
		id: '',
		status: false,
		ignored: false,
		recommendation: [],
		statusMsg: '',
		moreInfo: '',
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const {
			id,
			status,
			ignored,
			recommendation,
			statusMsg,
			moreInfo,
			onIgnore,
			onUnignore,
		} = this.props;

		return (
			<AccordionItem
				id={'wds-check-' + id}
				className={classnames(
					'wds-check-item',
					ignored
						? 'wds-check-invalid disabled'
						: id === 'title-secondary-keywords' && !status
						? ''
						: status
						? 'sui-success wds-check-success'
						: 'sui-warning wds-check-warning'
				)}
				header={
					<React.Fragment>
						<div className="sui-accordion-item-title sui-accordion-col-8">
							<span
								aria-hidden={true}
								className={classnames(
									ignored ||
										(id === 'title-secondary-keywords' &&
											!status)
										? 'sui-icon-info'
										: status
										? 'sui-success sui-icon-check-tick'
										: 'sui-warning sui-icon-info'
								)}
							></span>
							{statusMsg}
						</div>
						<div className="sui-accordion-col-4">
							{ignored ? (
								<Button
									id={'wds-unignore-check-' + id}
									className="wds-unignore"
									color="ghost"
									icon="sui-icon-undo"
									text={__('Restore', 'wds-texdomain')}
									onClick={onUnignore}
								></Button>
							) : (
								<AccordionItemOpenIndicator />
							)}
						</div>
					</React.Fragment>
				}
			>
				{!ignored && (
					<React.Fragment>
						<div className="wds-recommendation">
							<strong>
								{__('Recommendation', 'wds-texdomain')}
							</strong>
							{recommendation}
						</div>
						<div className="wds-more-info">
							<strong>{__('More Info', 'wds-texdomain')}</strong>
							{moreInfo}
						</div>

						<div className="wds-ignore-container">
							<Button
								className={classnames('wds-ignore')}
								color="ghost"
								icon="sui-icon-eye-hide"
								text={__('Ignore', 'wds-texdomain')}
								onClick={onIgnore}
							/>
						</div>
					</React.Fragment>
				)}
			</AccordionItem>
		);
	}
}
