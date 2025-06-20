import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckImgaltsKeywords extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="imgalts-keywords"
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
		const {
			state,
			percent,
			img_cnt: imgCnt,
			focus_img_cnt: focusImgCnt,
		} = this.props.data.result;

		let message;

		if (state) {
			message = __(
				"Alternative attribute text for images help search engines correctly index images and aid visually impaired readers. The text is also used in place of the image if it's unable to load. You should add alternative text for all images in your content.",
				'wds'
			);
		} else if (0 === imgCnt) {
			message = __(
				'Images are a great addition to any piece of content and it’s highly recommended to have imagery on your pages. Consider adding a few images that relate to your body content to enhance the reading experience of your article. Where possible, it’s also a great opportunity to include your focus keyphrase(s) to further associate the article with the topic you’re writing about.',
				'wds'
			);
		} else if (percent > 75) {
			message = sprintf(
				// translators: %d images with focus count, %d image count.
				__(
					'%1$d/%2$d images on this page have alt text with your keyphrase(s) which is too much. Whilst it’s great that you have image alternative text with your focus keyphrase(s), you can also get penalized for having too many keyphrases on a page. Try to include your keyphrase(s) in image alt texts only when it makes sense.',
					'wds'
				),
				focusImgCnt,
				imgCnt
			);
		} else if (percent === 0) {
			message = __(
				'None of the images on this page have alt text containing your focus keyphrase. It’s recommended practice to have your topic keyphrases in a few of your images to further associate the article with the topic you’re writing about. Add your keyphrase to one or more of your images, but be careful not to overdo it.',
				'wds'
			);
		} else {
			message = sprintf(
				// translators: %d images with focus count, %d image count.
				__(
					'%1$d/%2$d images on this page have alt text with your chosen keyphrase(s). Alternative attribute text for images helps search engines correctly index images and aid visually impaired readers. It’s recommended practice to have your topic keyphrases in a good number of your images to further associate the article with the topic you’re writing about. Add your keyphrase(s) to a few more of your images, but be careful not to overdo it.',
					'wds'
				),
				focusImgCnt,
				imgCnt
			);
		}

		return <p>{message}</p>;
	}

	getStatusMessage() {
		const {
			state,
			percent,
			has_featured: hasFeatured,
			img_cnt: imgCnt,
		} = this.props.data.result;

		let message;

		if (state) {
			message = __(
				'A good balance of images contain the focus keyphrase(s) in their alt attribute text',
				'wds'
			);
		} else if (imgCnt && !hasFeatured) {
			message = __(
				'You have included a focus keyphrase in the alternative text of too many images in your content',
				'wds'
			);
		} else if (percent > 75) {
			message = __(
				'Too many of your image alt texts contain the focus keyphrase(s)',
				'wds'
			);
		} else if (0 === percent) {
			message = __(
				'None of your image alt texts contain the focus keyphrase(s)',
				'wds'
			);
		} else {
			message = __(
				'Too few of your image alt texts contain the focus keyphrase(s)',
				'wds'
			);
		}

		return message;
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					"Image alternative text attributes help search engines correctly index images, aid visually impaired readers, and the text is used in place of the image if it's unable to load. You should add alternative text for all images in your content.",
					'wds'
				)}
			</p>
		);
	}
}
