import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckBoldedKeyword extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="bolded-keyword"
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
		const { state, type } = this.props.data.result;

		return (
			<p>
				{state
					? __(
							'It’s best practice to bold your secondary keyphrase at least once throughout your content.',
							'wds'
					  )
					: sprintf(
							/* translators: %s keyphrase type label */
							__(
								'You bolded your %s at least once in your content. Good work!',
								'wds'
							),
							type
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state, type } = this.props.data.result;

		return state
			? sprintf(
					/* translators: %s keyphrase label */
					__('The %s is bolded in your content.', 'wds'),
					type
			  )
			: sprintf(
					/* translators: %s keyphrase label */
					__(
						"You haven't bolded this %s in your content.",
						'wds'
					),
					type
			  );
	}

	getMoreInfo() {
		const { type } = this.props.data.result;

		return (
			<p>
				{sprintf(
					/* translators: %s keyphrase type label */
					__(
						'Bold keyphrases can help visitors and Google identify what is important on the page. You should consider bolding this %s at least once in your content.',
						'wds'
					),
					type
				)}
			</p>
		);
	}
}
