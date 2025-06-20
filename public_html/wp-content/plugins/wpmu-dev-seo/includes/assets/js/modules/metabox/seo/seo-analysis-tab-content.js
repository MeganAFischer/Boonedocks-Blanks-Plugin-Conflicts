import React from 'react';
import { sprintf, _n, __ } from '@wordpress/i18n';
import Notice from '../../../components/notices/notice';
import update from 'immutability-helper';
import RequestUtil from '../../../utils/request-util';
import ConfigValues from '../../../es6/config-values';
import GutenbergEditor from '../../../es6/gutenberg-editor';
import ClassicEditor from '../../../es6/classic-editor';
import SeoAnalysisCheckKeywordsUsed from './checks/post/seo-analysis-check-keywords-used';
import SeoAnalysisCheckFocus from './checks/post/seo-analysis-check-focus';
import SeoAnalysisCheckFocusStopWords from './checks/post/seo-analysis-check-focus-stopwords';
import SeoAnalysisCheckMetadescHandcraft from './checks/post/seo-analysis-check-metadesc-handcraft';
import SeoAnalysisCheckMetadescKeywords from './checks/post/seo-analysis-check-metadesc-keywords';
import SeoAnalysisCheckTitleKeywords from './checks/post/seo-analysis-check-title-keywords';
import SeoAnalysisCheckTitleLength from './checks/post/seo-analysis-check-title-length';
import SeoAnalysisCheckSlugKeywords from './checks/post/seo-analysis-check-slug-keywords';
import SeoAnalysisCheckSlugUnderscores from './checks/post/seo-analysis-check-slug-underscores';
import SeoAnalysisCheckMetadescLength from './checks/post/seo-analysis-check-metadesc-length';
import SeoAnalysisCheckImgaltsKeywords from './checks/endpoint/seo-analysis-check-imgalts-keywords';
import SeoAnalysisCheckContentLength from './checks/endpoint/seo-analysis-check-content-length';
import SeoAnalysisCheckKeywordDensity from './checks/endpoint/seo-analysis-check-keyword-density';
import SeoAnalysisCheckLinksCount from './checks/endpoint/seo-analysis-check-links-count';
import SeoAnalysisCheckNofollowLinks from './checks/endpoint/seo-analysis-check-nofollow-links';
import SeoAnalysisCheckParaKeywords from './checks/endpoint/seo-analysis-check-para-keywords';
import SeoAnalysisCheckSubheadingsKeywords from './checks/endpoint/seo-analysis-check-subheadings-keywords';
import SeoAnalysisCheckBoldedKeyword from './checks/endpoint/seo-analysis-check-bolded-keyword';
import SeoAnalysisCheckTitleSecondaryKeywords from './checks/post/seo-analysis-check-title-secondary-keywords';

const SeoAnalysisCheckComponents = {
	// Checks that deal with raw post data.
	keywords_used: SeoAnalysisCheckKeywordsUsed,
	focus: SeoAnalysisCheckFocus,
	focus_stopwords: SeoAnalysisCheckFocusStopWords,
	metadesc_handcraft: SeoAnalysisCheckMetadescHandcraft,
	metadesc_keywords: SeoAnalysisCheckMetadescKeywords,
	title_keywords: SeoAnalysisCheckTitleKeywords,
	title_length: SeoAnalysisCheckTitleLength,
	slug_keywords: SeoAnalysisCheckSlugKeywords,
	slug_underscores: SeoAnalysisCheckSlugUnderscores,
	metadesc_length: SeoAnalysisCheckMetadescLength,
	// Checks that deal with final rendered content.
	imgalts_keywords: SeoAnalysisCheckImgaltsKeywords,
	content_length: SeoAnalysisCheckContentLength,
	keyword_density: SeoAnalysisCheckKeywordDensity,
	links_count: SeoAnalysisCheckLinksCount,
	nofollow_links: SeoAnalysisCheckNofollowLinks,
	para_keywords: SeoAnalysisCheckParaKeywords,
	subheadings_keywords: SeoAnalysisCheckSubheadingsKeywords,
	// Check for extra keywords.
	bolded_keyword: SeoAnalysisCheckBoldedKeyword,
	title_secondary_keywords: SeoAnalysisCheckTitleSecondaryKeywords,
};

export default class SeoAnalysisTabContent extends React.Component {
	static defaultProps = {
		errCnt: 0,
		checks: {},
	};

	constructor(props) {
		super(props);

		// Check if Gutenberg is active.
		if (ConfigValues.get_bool('gutenberg_active', 'metabox')) {
			this.editor = new GutenbergEditor();
		} else {
			this.editor = new ClassicEditor();
		}

		this.state = {
			checks: this.props.checks,
		};
	}

	handleIgnore(id) {
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

	handleUnignore(id) {
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

	render() {
		const { errCnt } = this.props;
		const { checks } = this.state;

		return (
			<div className="wds-report">
				<div className="wds-report-inner">
					<Notice
						type={errCnt ? 'warning' : 'success'}
						message={
							errCnt
								? sprintf(
										/* translators: %d: error count. */
										_n(
											'You have %d SEO recommendation. We recommend you satisfy as many improvements as possible to ensure your content gets found.',
											'You have %d SEO recommendations. We recommend you satisfy as many improvements as possible to ensure your content gets found.',
											errCnt,
											'wds-texdomain'
										),
										errCnt
								  )
								: __(
										'All SEO recommendations are met. Your content is as optimized as possible - nice work!',
										'wds-texdomain'
								  )
						}
					></Notice>

					<div className="wds-accordion sui-accordion">
						{Object.keys(checks).map((key, index) => {
							const ComponentName =
								SeoAnalysisCheckComponents[key];

							return ComponentName ? (
								<ComponentName
									key={index}
									data={checks[key]}
									onIgnore={() => this.handleIgnore(key)}
									onUnignore={() => this.handleUnignore(key)}
								/>
							) : (
								''
							);
						})}
					</div>
				</div>
			</div>
		);
	}
}
