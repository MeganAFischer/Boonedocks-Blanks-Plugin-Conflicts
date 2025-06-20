import React from 'react';
import { __ } from '@wordpress/i18n';
import Button from '../../../components/button';
import TextInput from '../../../components/input-fields/text-input';
import { createInterpolateElement } from '@wordpress/element';
import GutenbergEditor from '../../../es6/gutenberg-editor';
import ClassicEditor from '../../../es6/classic-editor';
import { uniqueId } from 'lodash-es';
import Notice from '../../../components/notices/notice';
import ConfigValues from '../../../es6/config-values';
import RequestUtil from '../../../utils/request-util';
import TextInputField from '../../../components/form-fields/text-input-field';

export default class FocusKeywords extends React.Component {
	static defaultProps = {
		keywords: [],
		loading: false,
		onUpdateKeywords: () => false,
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
			newKeyword: '',
			loading: this.props.loading,
		};
	}

	// eslint-disable-next-line no-unused-vars
	componentDidUpdate(prevProps, prevState) {
		const { loading } = this.props;

		if (prevProps.loading !== loading) {
			this.setState({ loading });
		}
	}

	handleNewKeyword(newKeyword) {
		this.setState({ newKeyword });
	}

	addKeyword() {
		const { onUpdateKeywords } = this.props;
		const { newKeyword } = this.state;

		this.setState({ loading: true });

		RequestUtil.post(
			'wds_analysis_add_keyword',
			ConfigValues.get('nonce', 'metabox'),
			{
				post_id: this.editor.get_data().get_id(),
				keyword: newKeyword,
			}
		).then((resp) => {
			onUpdateKeywords(resp.split(','));

			this.setState({ newKeyword: '' });
		});
	}

	removeKeyword(keyword) {
		const { onUpdateKeywords } = this.props;

		this.setState({ loading: true });

		RequestUtil.post(
			'wds_analysis_remove_keyword',
			ConfigValues.get('nonce', 'metabox'),
			{
				post_id: this.editor.get_data().get_id(),
				keyword,
			}
		).then((resp) => {
			onUpdateKeywords(resp.split(',').filter((kw) => kw));

			this.setState({ newKeyword: '' });
		});
	}

	render() {
		const { keywords } = this.props;
		const { newKeyword, loading } = this.state;

		const focusInputId = uniqueId('wds-focus-input-');

		return (
			<div className="wds-focus-keyword sui-border-frame sui-form-field">
				<label className="sui-label wds-label" htmlFor={focusInputId}>
					{__('Focus keyphrase', 'wds')}
				</label>
				<p className="sui-description wds-description">
					{createInterpolateElement(
						__(
							'You can analyze the post content for up to 3 focus keyphrases. ' +
								'The SEO recommendations for each keyphrase will be displayed in ' +
								'separate tabs below. Enter each keyphrase you want to analyze and ' +
								'click the <strong>Add keyphrase</strong> button, or enter multiple ' +
								'keyphrases separated by commas and click the <strong>Add keyphrase</strong> ' +
								'button only once.',
							'wds'
						),
						{ strong: <strong /> }
					)}
				</p>
				<TextInputField
					id={focusInputId}
					name="wds_focus_input"
					placeholder={__(
						'E.g. broken iphone screen',
						'wds'
					)}
					value={newKeyword}
					onChange={(value) => this.handleNewKeyword(value)}
					loading={loading}
					suffix={
						<Button
							id="wds_add_keyword"
							disabled={keywords.length > 2}
							loading={loading}
							text={__('Add Keyphrase(s)', 'wds')}
							onClick={() => this.addKeyword()}
						></Button>
					}
				></TextInputField>
				<TextInput
					type="hidden"
					id="wds_focus"
					name="wds_focus"
					readOnly="readonly"
					value={keywords.join(',')}
				/>

				{keywords.length > 0 && (
					<div
						className="wds-added-keywords-tags"
						id="wds-added-keywords-tags"
					>
						<label className="sui-label">
							{__('Added Keyphrases', 'wds')}
						</label>
						<div className="sui-pagination-active-filters">
							{keywords.map((keyword) => (
								<span
									className="sui-active-filter"
									key={keyword}
								>
									{keyword}
									<span
										className="sui-active-filter-remove wds-remove-keyword"
										onClick={() =>
											this.removeKeyword(keyword)
										}
									></span>
								</span>
							))}
						</div>
					</div>
				)}

				{!keywords.length && (
					<Notice
						type="inactive"
						className="wds-notice"
						icon="sui-icon-info"
						message={__(
							'You need to add focus keyphrases to see recommendations for this article.',
							'wds'
						)}
					></Notice>
				)}
			</div>
		);
	}
}
