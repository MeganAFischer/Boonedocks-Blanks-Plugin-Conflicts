import React from 'react';
import Tabs from '../../components/tabs';
import { __ } from '@wordpress/i18n';
import ConfigValues from '../../es6/config-values';
import classnames from 'classnames';
import NonceField from '../../components/nonce-field';
import RequestUtil from '../../utils/request-util';
import GutenbergEditor from '../../es6/gutenberg-editor';
import ClassicEditor from '../../es6/classic-editor';
import MetaboxSeo from './metabox-seo';
import MetaboxReadability from './metabox-readability';
import MetaboxSocial from './metabox-social';
import MetaboxAdvanced from './metabox-advanced';

export default class Metabox extends React.Component {
	constructor(props) {
		super(props);

		// Check if Gutenberg is active.
		if (ConfigValues.get_bool('gutenberg_active', 'metabox')) {
			this.editor = new GutenbergEditor();
		} else {
			this.editor = new ClassicEditor();
		}

		this.state = {
			loading: true,
			selectedTab: 'seo',
			previewTitle: '',
			previewDesc: '',
			focusKeywords: ConfigValues.get('focus_keywords', 'metabox')
				.split(',')
				.filter((v) => !!v),
			seo: {},
			readability: {},
		};

		this.refresh = this.refresh.bind(this);
	}

	componentDidMount() {
		window.addEventListener('load', this.refresh);

		this.editor.addEventListener('autosave', () => {
			this.refreshAnalysis(true);
		});
	}

	componentWillUnmount() {
		window.removeEventListener('load', this.refresh);
	}

	refresh() {
		this.refreshAnalysis(false);
	}

	refreshAnalysis(dirty) {
		this.setState({ loading: true });

		RequestUtil.post(
			'wds_analysis_get_editor_analysis',
			ConfigValues.get('nonce', 'metabox'),
			{
				post_id: this.editor.get_data().get_id(),
				is_dirty: dirty || this.editor.is_post_dirty() ? 1 : 0,
				wds_title: this.state.previewTitle,
				wds_description: this.state.previewDesc,
				wds_focus_keywords: this.state.focusKeywords.join(','),
			}
		).then((resp) => {
			this.setState({
				seo: resp.seo,
				readability: resp.readability,
				loading: false,
			});
		});
	}

	handleTabChange(tab) {
		event.preventDefault();
		event.stopPropagation();

		this.setState({
			selectedTab: tab,
		});
	}

	handleChangeTitle(value) {
		this.setState({ previewTitle: value });
	}

	handleChangeDesc(value) {
		this.setState({ previewDesc: value });
	}

	handleUpdateKeywords(keywords) {
		this.setState({ focusKeywords: keywords }, () => {
			this.refreshAnalysis(false);
		});
	}

	handleAutosave() {
		this.editor.autosave();
	}

	renderIssueCount(type) {
		const { loading, seo, readability, focusKeywords } = this.state;

		switch (type) {
			case 'seo':
				let errCnt;

				if (focusKeywords.length) {
					errCnt = 0;

					if (seo) {
						if (seo.primary_error_count) {
							errCnt += seo.primary_error_count;
						}

						if (seo.extra_keywords) {
							Object.values(seo.extra_keywords).forEach(
								(keyword) => {
									const check = seo.extra_checks[keyword];
									errCnt += Object.keys(
										check?.errors || {}
									).length;
								}
							);
						}
					}
				} else {
					errCnt = -1;
				}

				return (
					<span
						className={classnames('wds-issues', {
							'wds-item-loading': loading,
							'wds-issues-success': !loading && errCnt === 0,
							'wds-issues-warning': !loading && errCnt > 0,
							'wds-issues-invalid': !loading && errCnt === -1,
						})}
					>
						{errCnt > 0 && <span>{errCnt}</span>}
						{errCnt === -1 && <span>0</span>}
					</span>
				);
			case 'readability':
				return (
					<span
						className={classnames(
							'wds-issues',
							!!loading
								? 'wds-item-loading'
								: `wds-issues-${readability?.state || ''}`
						)}
					>
						{!loading &&
							(readability?.state === 'warning' ||
							readability?.state === 'error'
								? 1
								: readability?.state === 'invalid'
								? 0
								: '')}
					</span>
				);
		}

		return '';
	}

	generateTabs() {
		const { previewTitle, previewDesc, focusKeywords, seo, loading } =
			this.state;

		const tabs = {
			seo: {
				label: (
					<React.Fragment>
						{__('SEO', 'wds')}
						{this.renderIssueCount('seo-textdomain')}
					</React.Fragment>
				),
				component: (
					<MetaboxSeo
						previewTitle={previewTitle}
						previewDesc={previewDesc}
						onChangeTitle={(val) => this.handleChangeTitle(val)}
						onChangeDesc={(val) => this.handleChangeDesc(val)}
						focusKeywords={focusKeywords}
						onUpdateKeywords={(keywords) =>
							this.handleUpdateKeywords(keywords)
						}
						analysis={seo}
						loading={loading}
						onRefresh={() => this.handleAutosave()}
					></MetaboxSeo>
				),
			},
		};

		const { readability } = this.state;

		if (ConfigValues.get('readability_active', 'metabox') && readability) {
			tabs.readability = {
				label: (
					<React.Fragment>
						{__('Readability', 'wds')}
						{this.renderIssueCount('readability')}
					</React.Fragment>
				),
				component: (
					<MetaboxReadability
						analysis={readability}
						loading={loading}
						onRefresh={() => this.handleAutosave()}
					></MetaboxReadability>
				),
			};
		}

		const isSocialActive = ConfigValues.get_bool(
			'social_active',
			'metabox'
		);

		if (isSocialActive) {
			tabs.social = {
				label: __('Social', 'wds'),
				component: <MetaboxSocial></MetaboxSocial>,
			};
		}

		tabs.advanced = {
			label: __('Advanced', 'wds'),
			component: <MetaboxAdvanced></MetaboxAdvanced>,
		};

		return tabs;
	}

	render() {
		return (
			<React.Fragment>
				<NonceField
					name={ConfigValues.get('nonce_name', 'metabox')}
					nonce={ConfigValues.get('nonce', 'metabox')}
					referer={ConfigValues.get('referer', 'metabox')}
				/>
				<Tabs
					tabs={this.generateTabs()}
					value={this.state.selectedTab}
					onChange={(tab) => this.handleTabChange(tab)}
				></Tabs>
			</React.Fragment>
		);
	}
}
