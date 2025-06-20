import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Button from '../../../components/button';
import MascotMessage from '../../../components/mascot-message';
import FocusKeywords from './focus-keywords';
import SeoAnalysisContent from './seo-analysis-content';
import Notice from '../../../components/notices/notice';
import ConfigValues from '../../../es6/config-values';

export default class SeoAnalysisContainer extends React.Component {
	static defaultProps = {
		keywords: [],
		onUpdateKeywords: () => false,
		analysis: {},
		loading: false,
		onRefresh: () => false,
	};

	render() {
		const { keywords, analysis, loading, onUpdateKeywords, onRefresh } =
			this.props;

		return (
			<div className="wds-seo-analysis-container">
				<div className="wds-seo-analysis-label">
					<strong>{__('SEO Analysis', 'wds')}</strong>

					<Button
						className="wds-refresh-analysis wds-analysis-seo"
						color="ghost"
						icon="sui-icon-update"
						text={__('Refresh', 'wds')}
						loading={loading}
						onClick={onRefresh}
					></Button>
				</div>

				<div className="sui-box-body">
					<MascotMessage
						msgKey="metabox-seo-analysis"
						message={createInterpolateElement(
							sprintf(
								// translators: %s: plugin title
								__(
									'This tool helps you optimize your content to give it the ' +
										'best chance of being found in search engines when people are ' +
										'looking for it. Start by choosing a few focus keyphrases ' +
										'that best describe your article, then <strong>%s</strong> will ' +
										'give you recommendations to make sure your content is highly optimized.',
									'wds'
								),
								ConfigValues.get('plugin_title', 'admin')
							),
							{ strong: <strong /> }
						)}
					></MascotMessage>
				</div>

				<FocusKeywords
					keywords={keywords}
					onUpdateKeywords={onUpdateKeywords}
					loading={loading}
				></FocusKeywords>

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

				{!loading && (
					<SeoAnalysisContent
						analysis={analysis}
					></SeoAnalysisContent>
				)}
			</div>
		);
	}
}
