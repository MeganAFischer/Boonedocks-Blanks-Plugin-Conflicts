import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import ConfigValues from '../../../es6/config-values';
import Button from '../../../components/button';
import classnames from 'classnames';
import InsertVariables from '../../../components/input-fields/insert-variables';
import OptimumIndicatorField from '../../../components/form-fields/optimum-indicator-field';
import TextareaInput from '../../../components/input-fields/textarea-input';
import PostObjectFetcher from '../../../es6/post-object-fetcher';
import PostObjectsCache from '../../../es6/post-objects-cache';
import MacroReplacement from '../../../es6/macro-replacement';
import GutenbergEditor from '../../../es6/gutenberg-editor';
import ClassicEditor from '../../../es6/classic-editor';
import StringUtils from '../../../es6/string-utils';
import FloatingNoticePlaceholder from '../../../components/floating-notice-placeholder';
import NoticeUtil from '../../../utils/notice-util';

const macros = ConfigValues.get('macros', 'metabox');
const titleMinLength = ConfigValues.get('title_min_length', 'metabox');
const titleMaxLength = ConfigValues.get('title_max_length', 'metabox');
const descMinLength = ConfigValues.get('metadesc_min_length', 'metabox');
const descMaxLength = ConfigValues.get('metadesc_max_length', 'metabox');

export default class GooglePreview extends React.Component {
	static defaultProps = {
		previewTitle: '',
		previewDesc: '',
		onChangeTitle: () => false,
		onChangeDesc: () => false,
	};

	constructor(props) {
		super(props);

		const postObjectsCache = new PostObjectsCache();
		const postObjectFetcher = new PostObjectFetcher(postObjectsCache);

		this.macroReplacement = new MacroReplacement(postObjectFetcher);

		// Check if Gutenberg is active.
		if (ConfigValues.get_bool('gutenberg_active', 'metabox')) {
			this.editor = new GutenbergEditor();
		} else {
			this.editor = new ClassicEditor();
		}

		this.state = {
			openForm: false,
			loading: false,
			error: false,
			title: ConfigValues.get('seo_title', 'metabox'),
			description: ConfigValues.get('seo_desc', 'metabox'),
			metaTitle: ConfigValues.get('meta_title', 'metabox'),
			metaDesc: ConfigValues.get('meta_desc', 'metabox'),
			phTitle: '',
			phDesc: '',
			permalink: ConfigValues.get('post_url', 'metabox'),
			editorTitle: '',
			editorDesc: '',
		};
	}

	componentDidMount() {
		if (window._wpLoadBlockEditor) {
			wp.data.subscribe(() => {
				this.refresh();
			});
		} else {
			this.refresh();
		}
	}

	// eslint-disable-next-line no-unused-vars
	componentDidUpdate(prevProps, prevState, snapshot) {
		const { editorTitle: prevEditorTitle, editorDesc: prevEditorDesc } =
			prevState;
		const { editorTitle, editorDesc } = this.state;

		if (editorTitle === prevEditorTitle && editorDesc === prevEditorDesc) {
			return;
		}

		this.refreshPreview();
		this.refreshPlaceholder();
	}

	refresh() {
		const post = this.editor.get_data();
		// Check if Gutenberg editor is active.
		if (window._wpLoadBlockEditor) {
			wp.data.subscribe(() => {
				this.setState({
					editorTitle: post.get_title(),
					editorDesc: post.get_content(),
				});
			});
		} else {
			this.setState({
				editorTitle: post.get_title(),
				editorDesc: post.get_content(),
			});
		}
	}

	refreshPlaceholder() {
		const post = this.editor.get_data();

		Promise.all([
			this.macroReplacement.replace(this.state.metaTitle, post),
			this.macroReplacement.replace(this.state.metaDesc, post),
		])
			.then((values) => {
				this.setState({
					phTitle: StringUtils.process_string(values[0]),
					phDesc: StringUtils.process_string(values[1]),
				});
			})
			.catch((error) => {
				this.setState({ error });
			});
	}

	refreshPreview() {
		this.setState({ loading: true }, () => {
			this.refreshPreviewTitle();
			this.refreshPreviewDesc();

			this.setState({ loading: false });
		});
	}

	refreshPreviewTitle() {
		const post = this.editor.get_data();

		const { title, metaTitle } = this.state;

		this.macroReplacement
			.replace(title || metaTitle, post)
			.then((value) => {
				this.props.onChangeTitle(StringUtils.process_string(value));
			})
			.catch((error) => {
				this.setState({ error });
			});
	}

	refreshPreviewDesc() {
		const post = this.editor.get_data();

		const { description, metaDesc } = this.state;

		this.macroReplacement
			.replace(description || metaDesc, post)
			.then((value) => {
				this.props.onChangeDesc(StringUtils.process_string(value));
			})
			.catch((error) => {
				this.setState({ error });
			});
	}

	handleChangeTitle(title) {
		this.setState({ title }, () => {
			this.refreshPreviewTitle();
		});
	}

	handleChangeDesc(description) {
		this.setState({ description }, () => {
			this.refreshPreviewDesc();
		});
	}

	render() {
		const {
			openForm,
			loading,
			error,
			phTitle,
			phDesc,
			permalink,
			title,
			description,
		} = this.state;

		const { previewTitle, previewDesc } = this.props;

		return (
			<>
				<FloatingNoticePlaceholder id="wds-metabox-preview-error" />

				{!!error &&
					NoticeUtil.showErrorNotice(
						'wds-metabox-preview-error',
						error,
						false
					)}

				<div className="wds-metabox-preview">
					<label className="sui-label">
						{__('Google Preview', 'wds')}
					</label>

					<div
						className={classnames(
							'wds-preview-container',
							loading && 'wds-preview-loading'
						)}
					>
						<div className="wds-preview">
							<div className="wds-preview-title">
								<h3>
									<a href={permalink}>
										{StringUtils.truncate_string(
											previewTitle,
											titleMaxLength
										)}
									</a>
								</h3>
							</div>
							<div className="wds-preview-url">
								<a href={permalink}>{permalink}</a>
							</div>
							<div className="wds-preview-meta">
								{StringUtils.truncate_string(
									previewDesc,
									descMaxLength
								)}
							</div>
						</div>
						<p className="wds-preview-description">
							{__(
								'A preview of how your title and meta will appear in Google Search.',
								'wds'
							)}
						</p>
					</div>
				</div>
				<div className="wds-edit-meta">
					<Button
						icon="sui-icon-pencil"
						color="ghost"
						text={__('Edit Meta', 'wds-text-domain')}
						onClick={() => this.setState({ openForm: !openForm })}
					></Button>

					{!!openForm && (
						<div className="sui-border-frame">
							<OptimumIndicatorField
								control={InsertVariables}
								dropdownWrapper="#wds-metabox-container"
								label={
									<>
										{__('SEO Title', 'wds')}
										<span className="sui-label-light">
											{sprintf(
												/* translators: 1, 2: Min/max length */
												__(
													' - Include your focus keyphrases. %1$d-%2$d characters recommended.',
													'wds'
												),
												titleMinLength,
												titleMaxLength
											)}
										</span>
									</>
								}
								name="wds_title"
								value={title}
								variables={macros}
								placeholder={phTitle}
								preview={previewTitle}
								onChange={(value) =>
									this.handleChangeTitle(value)
								}
								lower={titleMinLength}
								upper={titleMaxLength}
							/>

							<OptimumIndicatorField
								control={InsertVariables}
								dropdownWrapper="#wds-metabox-container"
								label={
									<>
										{__('Description', 'wds')}
										<span className="sui-label-light">
											{sprintf(
												/* translators: 1, 2: Min/max length */
												__(
													' - Include your focus keyphrases. %1$d-%2$d characters recommended.',
													'wds'
												),
												descMinLength,
												descMaxLength
											)}
										</span>
									</>
								}
								rows="2"
								name="wds_metadesc"
								value={description}
								variables={macros}
								preview={previewDesc}
								placeholder={phDesc}
								inputControl={TextareaInput}
								onChange={(value) =>
									this.handleChangeDesc(value)
								}
								lower={descMinLength}
								upper={descMaxLength}
							/>
						</div>
					)}
				</div>
			</>
		);
	}
}
