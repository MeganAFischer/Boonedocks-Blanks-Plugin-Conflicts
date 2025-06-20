import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import Button from '../../../components/button';
import Modal from '../../../components/modal';
import SelectField from '../../../components/form-fields/select-field';
import TextInputField from '../../../components/form-fields/text-input-field';
import ajaxUrl from 'ajaxUrl';
import update from 'immutability-helper';
import ConfigValues from '../../../es6/config-values';

const nonce = ConfigValues.get('nonce', 'admin');

export default class ExclusionModal extends React.Component {
	static defaultProps = {
		id: '',
		postTypes: {},
		onPostsUpdate: () => false,
		onSubmit: () => false,
		onClose: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			items: [],
			selectedType: Object.keys(this.props.postTypes)[0],
			isValidUrl: true,
		};
	}

	render() {
		const { id, postTypes, onClose } = this.props;
		const { items, selectedType, isValidUrl } = this.state;

		return (
			<Modal
				id={id}
				title={__('Add Exclusion', 'wds-texdomain')}
				description={__(
					'Choose which post you want to exclude.',
					'wds-texdomain'
				)}
				small={true}
				onEnter={() => this.handleSubmit()}
				onClose={onClose}
				footer={
					<React.Fragment>
						<Button
							ghost={true}
							text={__('Cancel', 'wds-texdomain')}
							onClick={onClose}
						/>
						<div className="sui-actions-right">
							<Button
								text={__('Add', 'wds-texdomain')}
								onClick={() => this.handleSubmit()}
								disabled={!items.length}
							/>
						</div>
					</React.Fragment>
				}
			>
				<SelectField
					label={__('Type', 'wds-texdomain')}
					options={postTypes}
					selectedValue={selectedType}
					onSelect={(value) => this.handleChangeType(value)}
				/>
				{selectedType === 'url' ? (
					<TextInputField
						id="ignore-url"
						label={__('Enter URL', 'wds')}
						placeholder={__('/url-to-post', 'wds')}
						description={__(
							'Enter the URL you want to exclude. Use “*” for wildcard URLs.',
							'wds'
						)}
						errorMessage={__(
							'Please use a relative URL only like /url-to-post.',
							'wds'
						)}
						isValid={isValidUrl}
						onChange={(url) => this.handleUpdateUrl(url)}
					></TextInputField>
				) : (
					<SelectField
						label={__('Post', 'wds-texdomain')}
						placeholder={__(
							'Start typing to search …',
							'wds-texdomain'
						)}
						selectedValue={items}
						multiple={true}
						tagging={true}
						ajaxUrl={() => this.getAjaxSearchUrl()}
						processResults={(data) => this.processResults(data)}
						onSelect={(values) => this.handleUpdateItems(values)}
					/>
				)}
			</Modal>
		);
	}

	handleSubmit() {
		if (this.props.onSubmit) {
			this.props.onSubmit(
				update(this.state.items, { $set: this.state.items }),
				this.state.selectedType
			);
		}
	}

	handleChangeType(type) {
		this.setState({ selectedType: type });
	}

	handleUpdateItems(items) {
		this.setState({ items });
	}

	handleUpdateUrl(url) {
		let items = [],
			isValidUrl = true;

		// Should start with a slash.
		if (url.lastIndexOf('/', 0) === 0) {
			// Remove all tags.
			url = url.replace(/(<([^>]+)>)/gi, '');

			items = [url];
		} else if (url !== '') {
			isValidUrl = false;
		}

		this.setState({
			items,
			isValidUrl,
		});
	}

	getAjaxSearchUrl() {
		if (!ajaxUrl) {
			return false;
		}

		return sprintf(
			'%1$s?action=smartcrawl_get_posts_paged&type=%2$s&_wds_nonce=%3$s',
			ajaxUrl,
			this.state.selectedType,
			nonce
		);
	}

	processResults(data) {
		const results = [],
			posts = [];

		data.posts.forEach((post) => {
			results.push({
				id: post.id,
				text: post.title,
			});

			posts.push(post);
		});

		if (this.props.onPostsUpdate) {
			this.props.onPostsUpdate(posts);
		}

		return {
			results,
		};
	}
}
