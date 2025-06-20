import React from 'react';
import { __ } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckFocus extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="focus"
				ignored={data.ignored}
				status={data.status}
				recommendation={this.getRecommendation()}
				statusMsg={this.getStatusMessage()}
				moreInfo={this.getMoreInfo()}
				onIgnore={onIgnore}
				onUnignore={onUnignore}
			/>
		);
	}

	getRecommendation() {
		const { state } = this.props.data.result;
		return (
			<p>
				{!state
					? __(
							'In order to give your content the best possible chance to be discovered, it is best to select some focus keyphrases or key phrases, to give it some context.',
							'wds'
					  )
					: __(
							'Nice work, now that we know what your article is about we can be more specific in analysis.',
							'wds'
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;

		return !state
			? __('There are no focus keyphrases', 'wds')
			: __('There are some focus keyphrases', 'wds');
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					'Selecting focus keyphrases helps describe what your content is about.',
					'wds'
				)}
			</p>
		);
	}
}
