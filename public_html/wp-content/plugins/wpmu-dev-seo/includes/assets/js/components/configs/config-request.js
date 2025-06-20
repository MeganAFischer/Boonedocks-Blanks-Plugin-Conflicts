import ConfigValues from '../../es6/config-values';
import RequestUtil from '../../utils/request-util';

export default class ConfigRequest {
	static sync() {
		return this.post('smartcrawl_sync_configs');
	}

	static applyConfig(configId) {
		return this.post('smartcrawl_apply_config', { config_id: configId });
	}

	static deleteConfig(configId) {
		return this.post('smartcrawl_delete_config', { config_id: configId });
	}

	static updateConfig(configId, configName, configDescription) {
		return this.post('smartcrawl_update_config', {
			config_id: configId,
			name: configName,
			description: configDescription,
		});
	}

	static createConfig(configName, configDescription) {
		return this.post('smartcrawl_create_config', {
			name: configName,
			description: configDescription,
		});
	}

	static uploadConfig(file) {
		return RequestUtil.uploadFile(
			'smartcrawl_upload_config',
			ConfigValues.get('nonce', 'config'),
			file
		);
	}

	static post(action, data) {
		const nonce = ConfigValues.get('nonce', 'config');
		return RequestUtil.post(action, nonce, data);
	}
}
