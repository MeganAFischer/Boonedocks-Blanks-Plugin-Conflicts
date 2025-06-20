import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import ConfigValues from '../../es6/config-values';
import ConfigRequest from './config-request';
import ConfigApplyModal from './config-apply-modal';
import ConfigDeleteModal from './config-delete-modal';
import ConfigSaveModal from './config-save-modal';
import SUI from 'SUI';
import FileUtil from '../../utils/file-util';
import { escapeHTML } from '@wordpress/escape-html';

export default class Configs extends React.Component {
	constructor(props) {
		super(props);
		this.props = props;
		this.state = {
			applyingConfig: false,
			deletingConfig: false,
			creatingConfig: false,
			updatingConfig: false,
			uploadingConfig: false,
			requestInProgress: false,
			configs: ConfigValues.get('configs', 'config'),
		};
	}

	syncWithHub() {
		this.setState(
			{
				syncing: true,
			},
			() => {
				ConfigRequest.sync()
					.then((data) => {
						this.setConfigs(data.configs);
					})
					.catch(() => {
						this.showErrorNotice(
							__(
								'There was an error syncing your configs with the hub.',
								'wds'
							)
						);
					})
					.finally(() => {
						this.setState({ syncing: false });
					});
			}
		);
	}

	render() {
		const MainComponent = this.props.mainComponent;

		return (
			<React.Fragment>
				<MainComponent
					configs={this.state.configs}
					syncing={this.state.syncing}
					uploadInProgress={this.state.uploadingConfig}
					onSave={() => this.startCreatingConfig()}
					onUpload={(file) => this.uploadConfig(file)}
					onApply={(configId) => this.startApplyingConfig(configId)}
					onUpdate={(configId) => this.startUpdatingConfig(configId)}
					onDownload={(configId) => this.downloadConfig(configId)}
					onDelete={(configId) => this.startDeletingConfig(configId)}
					triggerSync={() => this.syncWithHub()}
				/>

				{this.maybeShowApplyModal()}
				{this.maybeShowDeleteModal()}
				{this.maybeShowUpdateModal()}
				{this.maybeShowCreateModal()}
			</React.Fragment>
		);
	}

	maybeShowApplyModal() {
		const applyingConfig = this.state.applyingConfig;
		return (
			applyingConfig && (
				<ConfigApplyModal
					configName={this.getConfigName(applyingConfig)}
					onClose={() => this.stopApplyingConfig()}
					onApply={() => this.applyConfig()}
					inProgress={applyingConfig && this.state.requestInProgress}
				/>
			)
		);
	}

	startApplyingConfig(configId) {
		this.setState({
			applyingConfig: configId,
		});
	}

	applyConfig() {
		this.setState(
			{
				requestInProgress: true,
			},
			() => {
				const configId = this.state.applyingConfig;
				ConfigRequest.applyConfig(configId)
					.then(() => {
						this.showSuccessNoticeWithConfigName(
							// translators: %s: Name of configuration.
							__('%s config has been applied successfully.'),
							configId
						);

						setTimeout(() => {
							window.location.reload();
						}, 3000);
					})
					.catch((err) => {
						this.showErrorNotice(
							err ||
								__(
									'There was an error applying the config.',
									'wds'
								)
						);
						this.stopApplyingConfig();
					});
			}
		);
	}

	stopApplyingConfig() {
		this.setState({
			applyingConfig: false,
			requestInProgress: false,
		});
	}

	maybeShowDeleteModal() {
		const deletingConfig = this.state.deletingConfig;
		return (
			deletingConfig && (
				<ConfigDeleteModal
					configName={this.getConfigName(deletingConfig)}
					onClose={() => this.stopDeletingConfig()}
					onDelete={() => this.deleteConfig()}
					inProgress={deletingConfig && this.state.requestInProgress}
				/>
			)
		);
	}

	startDeletingConfig(configId) {
		this.setState({
			deletingConfig: configId,
		});
	}

	deleteConfig() {
		this.setState(
			{
				requestInProgress: true,
			},
			() => {
				const configId = this.state.deletingConfig;
				ConfigRequest.deleteConfig(configId)
					.then((data) => {
						this.showSuccessNoticeWithConfigName(
							// translators: %s: Name of configuration.
							__('%s config has been deleted successfully.'),
							configId
						);
						this.setConfigs(data.configs);
					})
					.catch(() => {
						this.showErrorNotice(
							__('There was an error deleting the config.', 'wds')
						);
					})
					.finally(() => {
						this.stopDeletingConfig();
					});
			}
		);
	}

	stopDeletingConfig() {
		this.setState({
			deletingConfig: false,
			requestInProgress: false,
		});
	}

	downloadConfig(configId) {
		const config = this.getConfig(configId);
		if (config && config.name) {
			const filename =
				'smartcrawl-config-' + config.name.replaceAll(' ', '-');
			FileUtil.triggerFileDownload(
				JSON.stringify(config, null, 2),
				filename
			);
		}
	}

	maybeShowUpdateModal() {
		const updatingConfig = this.state.updatingConfig;
		const config = this.getConfig(updatingConfig);
		if (!config) {
			return;
		}
		return (
			updatingConfig && (
				<ConfigSaveModal
					configName={config.name}
					configDescription={config.description}
					onClose={() => this.stopUpdatingConfig()}
					onSave={(updatedName, updatedDescription) =>
						this.updateConfig(
							updatingConfig,
							updatedName,
							updatedDescription
						)
					}
					inProgress={updatingConfig && this.state.requestInProgress}
					editMode={true}
				/>
			)
		);
	}

	startUpdatingConfig(configId) {
		this.setState({
			updatingConfig: configId,
		});
	}

	updateConfig(configId, configName, configDescription) {
		this.setState(
			{
				requestInProgress: true,
			},
			() => {
				ConfigRequest.updateConfig(
					configId,
					configName,
					configDescription
				)
					.then((data) => {
						this.showSuccessNoticeWithConfigName(
							// translators: %s: Name of configuration.
							__('%s config has been renamed successfully.'),
							configId
						);
						this.setConfigs(data.configs);
					})
					.catch(() => {
						this.showErrorNotice(
							__('There was an error updating the config.', 'wds')
						);
					})
					.finally(() => {
						this.stopUpdatingConfig();
					});
			}
		);
	}

	stopUpdatingConfig() {
		this.setState({
			updatingConfig: false,
			requestInProgress: false,
		});
	}

	maybeShowCreateModal() {
		const creatingConfig = this.state.creatingConfig;
		return (
			creatingConfig && (
				<ConfigSaveModal
					configName=""
					configDescription=""
					onClose={() => this.stopCreatingConfig()}
					onSave={(name, description) =>
						this.createConfig(name, description)
					}
					inProgress={creatingConfig && this.state.requestInProgress}
					editMode={false}
				/>
			)
		);
	}

	startCreatingConfig() {
		this.setState({
			creatingConfig: true,
		});
	}

	createConfig(configName, configDescription) {
		this.setState(
			{
				requestInProgress: true,
			},
			() => {
				ConfigRequest.createConfig(configName, configDescription)
					.then((data) => {
						this.setConfigs(data.configs, () => {
							this.showSuccessNoticeWithConfigName(
								// translators: %s: Name of configuration.
								__('%s config saved successfully.'),
								data.config_id
							);
						});
					})
					.catch(() => {
						this.showErrorNotice(
							__('There was an error creating the config.', 'wds')
						);
					})
					.finally(() => {
						this.stopCreatingConfig();
					});
			}
		);
	}

	stopCreatingConfig() {
		this.setState({
			creatingConfig: false,
			requestInProgress: false,
		});
	}

	uploadConfig(file) {
		this.setState(
			{
				uploadingConfig: true,
			},
			() => {
				ConfigRequest.uploadConfig(file)
					.then((data) => {
						this.setConfigs(data.configs, () => {
							this.showSuccessNoticeWithConfigName(
								// translators: %s: Name of configuration.
								__('%s config uploaded successfully.'),
								data.config_id
							);
						});
					})
					.catch(() => {
						this.showErrorNotice(
							__(
								'There was an error uploading the config.',
								'wds'
							)
						);
					})
					.finally(() => {
						this.setState({
							uploadingConfig: false,
						});
					});
			}
		);
	}

	getConfigName(configId) {
		const config = this.getConfig(configId);
		if (config) {
			return config.name;
		}

		return '';
	}

	getConfig(configId) {
		if (!configId) {
			return false;
		}

		const key = 'config-' + configId;
		if (!this.state.configs || !this.state.configs.hasOwnProperty(key)) {
			return false;
		}

		return this.state.configs[key];
	}

	showSuccessNoticeWithConfigName(message, configId) {
		const configName = this.getConfigName(configId);
		const notice = sprintf(
			message,
			'<strong>' + escapeHTML(configName) + '</strong>'
		);
		this.showNotice(notice, 'success', true);
	}

	showSuccessNotice(message) {
		this.showNotice(message, 'success');
	}

	showErrorNotice(message) {
		this.showNotice(message, 'error');
	}

	showNotice(message, type = 'success') {
		const icons = {
			error: 'warning-alert',
			info: 'info',
			warning: 'warning-alert',
			success: 'check-tick',
		};

		SUI.closeNotice('wds-config-notice');
		SUI.openNotice('wds-config-notice', '<p>' + message + '</p>', {
			type,
			icon: icons[type],
			dismiss: { show: true },
		});
	}

	setConfigs(configs, callback = () => false) {
		this.setState({ configs }, callback);
	}
}
