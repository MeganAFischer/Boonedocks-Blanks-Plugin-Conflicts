import React from 'react';
import GooglePreview from './seo/google-preview';
import SeoAnalysisContainer from './seo/seo-analysis-container';
import ConfigValues from '../../es6/config-values';

export default class MetaboxSeo extends React.Component {
	static defaultProps = {
		previewTitle: '',
		previewDesc: '',
		focusKeywords: [],
		analysis: {},
		onChangeTitle: () => false,
		onChangeDesc: () => false,
		onUpdateKeywords: () => false,
		loading: false,
		onRefresh: () => false,
	};

	render() {
		const {
			previewTitle,
			previewDesc,
			focusKeywords,
			onChangeTitle,
			onChangeDesc,
			onUpdateKeywords,
			analysis,
			loading,
			onRefresh,
		} = this.props;

		const isSeoActive = ConfigValues.get_bool('seo_active', 'metabox');
		const isOnpageActive = ConfigValues.get_bool(
			'onpage_active',
			'metabox'
		);

		return (
			<div className="wds_seo">
				{isOnpageActive && (
					<div className="wds-metabox-section">
						<GooglePreview
							previewTitle={previewTitle}
							previewDesc={previewDesc}
							onChangeTitle={(val) => onChangeTitle(val)}
							onChangeDesc={(val) => onChangeDesc(val)}
						></GooglePreview>
					</div>
				)}
				{isSeoActive && (
					<div className="wds-metabox-section">
						<SeoAnalysisContainer
							keywords={focusKeywords}
							onUpdateKeywords={onUpdateKeywords}
							analysis={analysis}
							loading={loading}
							onRefresh={onRefresh}
						></SeoAnalysisContainer>
					</div>
				)}
			</div>
		);
	}
}
