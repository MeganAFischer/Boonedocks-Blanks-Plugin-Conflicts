import React from 'react';
import { __ } from '@wordpress/i18n';
import Button from '../../button';
import RequestUtil from '../../../utils/request-util';
import ConfigValues from '../../../es6/config-values';

export default class ConflictingPluginItem extends React.Component {
	static defaultProps = {
		name: '',
		plugin: '',
		network: false,
		onProgress: () => false,
		onDeactivate: () => false,
		onError: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			inProgress: false,
		};
	}

	handleProgress(val) {
		const { onProgress } = this.props;

		this.setState({ inProgress: val }, () => {
			onProgress(val);
		});
	}

	handleDeactivation() {
		const { plugin, network, onDeactivate, onError } = this.props;

		this.handleProgress(true);
		onError();

		RequestUtil.post(
			'smartcrawl_deactivate_plugin',
			ConfigValues.get('settings_nonce', 'admin'),
			{
				plugin,
				network: network === false ? 0 : 1,
			}
		)
			.then(
				() => {
					onDeactivate(plugin);
				},
				(error) => {
					onError(plugin, error);
				}
			)
			.finally(() => {
				this.handleProgress(false);
			});
	}

	render() {
		const { name, plugin } = this.props;

		if (!name || !plugin) {
			return '';
		}

		const { inProgress } = this.state;

		return (
			<div className="wds-conf-plug-item">
				<div className="sui-box">
					<span className="sui-icon-plugin-2" aria-hidden="true" />
					{name}
					<Button
						icon="sui-icon-power-on-off"
						tooltip={__('Deactivate Plugin', 'wds-texdomain')}
						onClick={() => this.handleDeactivation()}
						loading={inProgress}
					/>
				</div>
			</div>
		);
	}
}
