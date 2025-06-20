import React from 'react';
import Notice from '../notices/notice';
import VerticalTab from '../vertical-tab';
import { __ } from '@wordpress/i18n';
import UrlUtil from '../../utils/url-util';

export default class LighthouseError extends React.Component {
	static defaultProps = {
		message: '',
	};

	render() {
		const isActive =
			!UrlUtil.getQueryParam('tab') ||
			UrlUtil.getQueryParam('tab') === 'tab_lighthouse';

		return (
			<VerticalTab
				id="tab_lighthouse"
				title={__('SEO audit', 'wds')}
				isActive={isActive}
			>
				<Notice type="error" message={this.props.message} />
			</VerticalTab>
		);
	}
}
