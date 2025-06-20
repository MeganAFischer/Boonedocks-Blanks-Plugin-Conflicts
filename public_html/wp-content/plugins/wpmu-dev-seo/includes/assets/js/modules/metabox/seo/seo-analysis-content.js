import React from 'react';
import Tabs from '../../../components/tabs';
import SeoAnalysisTabContent from './seo-analysis-tab-content';
import SeoAnalysisTabLabel from './seo-analysis-tab-label';
import FormattingUtil from '../../../utils/formatting-util';

export default class SeoAnalysisContent extends React.Component {
	static defaultProps = {
		analysis: {},
	};

	constructor(props) {
		super(props);

		const primaryKeyword = this.props.analysis.primary_keyword;

		this.state = {
			selectedTab: primaryKeyword || '',
		};
	}

	handleTabChange(tab) {
		event.preventDefault();
		event.stopPropagation();

		this.setState({
			selectedTab: tab,
		});
	}

	render() {
		const { analysis } = this.props;

		const tabs = {};

		if (analysis.primary_keyword) {
			tabs[analysis.primary_keyword] = {
				label: (
					<SeoAnalysisTabLabel
						isPrimary={true}
						hasError={analysis.primary_error_count > 0}
						text={analysis.primary_keyword}
					></SeoAnalysisTabLabel>
				),
				component: (
					<SeoAnalysisTabContent
						errCnt={analysis.primary_error_count}
						checks={analysis.primary_checks}
					></SeoAnalysisTabContent>
				),
			};
		}

		if (analysis.extra_keywords) {
			Object.values(analysis.extra_keywords).forEach((keyword) => {
				const check =
					analysis.extra_checks[
						FormattingUtil.sanitizeHtmlClass(keyword)
					];

				if (check) {
					tabs[keyword] = {
						label: (
							<SeoAnalysisTabLabel
								hasError={
									Object.keys(check.errors || {}).length > 0
								}
								text={keyword}
							></SeoAnalysisTabLabel>
						),
						component: (
							<SeoAnalysisTabContent
								errCnt={Object.keys(check.errors || {}).length}
								checks={check.checks || {}}
							></SeoAnalysisTabContent>
						),
					};
				}
			});
		}

		return (
			<Tabs
				className="wds-seo-analysis"
				tabs={tabs}
				value={this.state.selectedTab}
				onChange={(tab) => this.handleTabChange(tab)}
			></Tabs>
		);
	}
}
