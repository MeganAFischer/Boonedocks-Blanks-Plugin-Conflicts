import React from 'react';
import Modal from '../modal';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import ImportOptions from './import-options';
import ImportProgress from './import-progress';
import ImportSuccess from './import-success';
import ImportError from './import-error';
import RequestUtil from '../../utils/request-util';
import ConfigValues from '../../es6/config-values';

export default class ImportModal extends React.Component {
	static defaultProps = {
		source: '',
		sourceName: '',
		indexSettingsUrl: '',
		nonce: '',
		isMultisite: false,
		onClose: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			importing: false,
			success: false,
			error: false,
			progress: 0,
			siteProgress: 0,
			deactivationUrl: '',
			options: [
				{
					name: 'import-options',
					checked: true,
					label: __('Plugin Options', 'wds-texdomain'),
					description: createInterpolateElement(
						sprintf(
							// translators: 1: source plugin name, 2: plugin title
							__(
								'Import <strong>%1$s</strong> settings that are relevant to <strong>%2$s</strong>.',
								'wds-texdomain'
							),
							this.props.sourceName,
							ConfigValues.get('plugin_title', 'admin')
						),
						{ strong: <strong /> }
					),
				},
				{
					name: 'import-term-meta',
					checked: true,
					label: __('Term Meta', 'wds-texdomain'),
					description: __(
						'Import your title & meta settings for categories, tags and custom taxonomies.',
						'wds-texdomain'
					),
				},
				{
					name: 'import-post-meta',
					checked: true,
					attributes: { 'data-dependent': 'keep-existing-post-meta' },
					label: __('Post Meta', 'wds-texdomain'),
					description: __(
						'Import your title & meta settings for posts and pages.',
						'wds-texdomain'
					),
				},
				{
					name: 'keep-existing-post-meta',
					checked: false,
					advanced: true,
					label: __(
						'Keep Existing Post Meta & Focus Keywords',
						'wds-texdomain'
					),
					description: createInterpolateElement(
						sprintf(
							// translators: %s: plugin title
							__(
								'If you have already set up <strong>%s</strong> on some posts and pages then enable this option to keep those values from getting overwritten.',
								'wds-texdomain'
							),
							ConfigValues.get('plugin_title', 'admin')
						),
						{ strong: <strong /> }
					),
				},
			],
		};
	}

	render() {
		const { onClose, source, sourceName, isMultisite, indexSettingsUrl } =
			this.props;
		const {
			importing,
			progress,
			siteProgress,
			success,
			error,
			deactivationUrl,
			options,
		} = this.state;

		return (
			<Modal
				id="wds-import-status"
				title={__('Import', 'wds-texdomain')}
				onClose={onClose}
			>
				{!importing && !error && !success && (
					<ImportOptions
						sourceName={sourceName}
						options={options}
						onStart={(opt) => this.handleStart(opt)}
						onChange={(target) => this.handleToggleOption(target)}
					/>
				)}
				{importing && (
					<ImportProgress
						progress={progress}
						isMultisite={isMultisite}
						siteProgress={siteProgress}
						sourceName={sourceName}
					/>
				)}

				{error && (
					<ImportError
						error={error}
						onRetry={() => this.handleRetry()}
						onClose={onClose}
					/>
				)}

				{success && (
					<ImportSuccess
						source={source}
						sourceName={sourceName}
						deactivationUrl={deactivationUrl}
						indexSettingsUrl={indexSettingsUrl}
						onClose={onClose}
					/>
				)}
			</Modal>
		);
	}

	handleRetry() {
		this.setState({
			error: false,
			success: false,
			importing: false,
		});
	}

	handleStart(options) {
		this.setState({
			importing: true,
			progress: 0,
			siteProgress: 0,
		});

		this.import(options);
	}

	handleSuccess(deactivationUrl) {
		setTimeout(() => {
			this.setState({
				importing: false,
				success: true,
				deactivationUrl,
			});
		}, 500);
	}

	handleError(error) {
		this.setState({
			importing: false,
			error: error || true,
		});
	}

	handleToggleOption(target) {
		const { options } = this.state;

		const index = options.findIndex((opt) => opt.name === target);
		options[index].checked = !options[index].checked;

		this.setState({ options });
	}

	import(options, restart) {
		const { nonce } = this.props;

		if (!nonce) {
			return;
		}

		const { source } = this.props;

		RequestUtil.post(`import_${source}_data`, nonce, {
			restart,
			items_to_import: options,
		}).then(
			(resp) => {
				this.updateProgress(resp.status).then(() => {
					this.maybeUpdateSiteProgress(resp.status);
					if (resp.in_progress) {
						this.import(options, 0);
					} else {
						this.handleSuccess(
							resp.deactivation_url
								? decodeURIComponent(
										resp.deactivation_url
								  ).replaceAll('&amp;', '&')
								: false
						);
					}
				});
			},
			(error) => {
				this.handleError(error);
			}
		);
	}

	maybeUpdateSiteProgress(status) {
		if (this.props.isMultisite) {
			const total = status.total_sites || 0,
				completed = status.completed_sites || 0,
				siteProgress = total > 0 ? (completed / total) * 100 : 100;

			let progress = this.state.progress;
			if (
				siteProgress !== 100 &&
				siteProgress !== this.state.siteProgress
			) {
				// Get ready for the next site by resetting progress to 0
				progress = 0;
			}

			this.setState({
				siteProgress,
				progress,
			});
		}
	}

	updateProgress(status) {
		return new Promise((resolve) => {
			const remaining = status.remaining_posts || 0,
				completed = status.completed_posts || 0,
				total = remaining + completed;

			this.setState({
				progress: total > 0 ? (completed / total) * 100 : 100,
			});
			setTimeout(resolve, 500);
		});
	}
}
