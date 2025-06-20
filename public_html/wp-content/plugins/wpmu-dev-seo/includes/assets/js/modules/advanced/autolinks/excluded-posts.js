import React from 'react';
import { __ } from '@wordpress/i18n';
import ConfigValues from '../../../es6/config-values';
import SettingsRow from '../../../components/settings-row';
import TextareaInputField from '../../../components/form-fields/textarea-input-field';
import RequestUtil from '../../../utils/request-util';
import NoticeUtil from '../../../utils/notice-util';
import List from './list/list';
import ExclusionModal from './exclusion-modal';
import FloatingNoticePlaceholder from '../../../components/floating-notice-placeholder';
import { connect } from 'react-redux';

class ExcludedPosts extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			loadingPosts: false,
			openDialog: false,
			excludedPosts: [],
			excludedUrls: ConfigValues.get('excluded_urls', 'autolinks'),
		};
	}

	componentDidMount() {
		this.loadPosts();
	}

	render() {
		const { loading, options, updateOption } = this.props;
		const { excludedPosts, openDialog, loadingPosts } = this.state;

		const exclusions = options.ignorepost
				.split(',')
				.map((excl) => excl.trim())
				.filter((excl) => !!excl),
			postTypes = ConfigValues.get('post_types', 'admin');

		return (
			<SettingsRow
				label={__('Exclusions', 'wds')}
				description={__(
					'Provide a comma-separated list of keywords that you would like to exclude. You can also select individual posts/pages/URLs for exclusion.',
					'wds'
				)}
				direction="column"
			>
				<FloatingNoticePlaceholder id="wds-postlist-notice" />

				<TextareaInputField
					label={__('Excluded Keywords', 'wds')}
					placeholder={__('eg: SEO', 'wds')}
					value={options.ignore}
					onChange={(val) => updateOption('ignore', val)}
					disabled={loading}
				></TextareaInputField>

				<label className="sui-label">
					{__('Excluded Posts/Pages/URLs', 'wds-texdomain')}
				</label>

				<List
					items={exclusions}
					posts={excludedPosts}
					loading={loadingPosts}
					disabled={loading}
					types={postTypes}
					onRemove={(value, type) => this.handleRemove(value, type)}
					onAdd={() => this.setState({ openDialog: true })}
				/>

				{openDialog && (
					<ExclusionModal
						id="wds-postlist-selector"
						postTypes={postTypes}
						onPostsUpdate={(posts) => this.handlePostsUpdate(posts)}
						onSubmit={(values, type) =>
							this.handleItemsAdd(values, type)
						}
						onClose={() => this.toggleModal()}
					/>
				)}
			</SettingsRow>
		);
	}

	handleRemove(item) {
		const { options, updateOption } = this.props;
		const exclusions = options.ignorepost
			.split(',')
			.map((excl) => excl.trim())
			.filter((excl) => excl !== item);

		updateOption('ignorepost', exclusions.join(','));
	}

	loadPosts() {
		const { options } = this.props;

		this.setState({ loadingPosts: true });

		const ids = options.ignorepost
			.split(',')
			.map((excl) => excl.trim())
			.filter((excl) => excl && !isNaN(excl));

		RequestUtil.post(
			'smartcrawl_get_posts_by_ids',
			ConfigValues.get('nonce', 'admin'),
			{
				posts: ids,
			}
		)
			.then((data) => {
				this.setState({
					excludedPosts: data.posts,
				});
			})
			.catch((error) => {
				NoticeUtil.showErrorNotice(
					'wds-postlist-notice',
					error ||
						__(
							'An error occurred. Please try again.',
							'wds'
						),
					false
				);
			})
			.finally(() => {
				this.setState({ loadingPosts: false });
			});
	}

	handleItemsAdd(values, type) {
		const { options, updateOption } = this.props;
		const exclusions = options.ignorepost
			.split(',')
			.map((excl) => excl.trim())
			.filter((excl) => excl && !isNaN(excl));

		values.forEach((value) => {
			const val = 'url' === type ? value : parseInt(value);

			if (exclusions.indexOf(val) === -1) {
				exclusions.push(val);
			}
		});

		updateOption('ignorepost', exclusions.join(','));

		this.setState({
			openDialog: false,
		});
	}

	handlePostsUpdate(updatablePosts) {
		let { excludedPosts } = this.state;

		if (!excludedPosts) {
			excludedPosts = {};
		}

		const ids = excludedPosts ? Object.keys(excludedPosts) : [];

		updatablePosts.forEach((post) => {
			if (ids.indexOf(post.id) === -1) {
				excludedPosts[post.id] = post;
			}
		});

		this.setState({ excludedPosts });
	}

	toggleModal() {
		this.setState({ openDialog: !this.state.openDialog });
	}
}

const mapStateToProps = (state) => ({ ...state.autolinks });

const mapDispatchToProps = {
	updateOption: (key, value) => ({
		type: 'UPDATE_OPTION',
		key,
		value,
	}),
	toggleLoading: () => ({
		type: 'TOGGLE_LOADING',
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(ExcludedPosts);
