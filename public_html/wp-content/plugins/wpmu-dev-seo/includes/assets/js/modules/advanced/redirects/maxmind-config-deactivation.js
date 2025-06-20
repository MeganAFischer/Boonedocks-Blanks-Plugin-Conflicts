import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Notice from '../../../components/notices/notice';
import Button from '../../../components/button';
import ConfigValues from '../../../es6/config-values';
import RequestUtil from '../../../utils/request-util';
import TextInputField from '../../../components/form-fields/text-input-field';
import { connect } from 'react-redux';

class MaxmindConfigDeactivation extends React.Component {
	static defaultProps = {
		key: '',
	};

	constructor(props) {
		super(props);

		this.state = {
			errMsg: '',
			loading: false,
		};
	}

	handleDisconnect(e) {
		e.preventDefault();

		this.setState({ loading: true }, () => {
			RequestUtil.post(
				'smartcrawl_reset_geodb',
				ConfigValues.get('nonce', 'admin')
			)
				.then(() => {
					this.props.updateProp('maxmind_license', '');
				})
				.catch((err) => {
					this.setState({ errMsg: err.message });
				})
				.finally(() => {
					this.setState({ loading: false });
				});
		});

		return false;
	}

	render() {
		const { maxmindKey } = this.props;
		const { errMsg, loading } = this.state;

		return (
			<>
				<TextInputField
					label={
						<>
							{__('Maxmind License Key', 'wds')}
							<span className="sui-tag sui-tag-green sui-tag-sm">
								{__('Connected', 'wds')}
							</span>
						</>
					}
					description={createInterpolateElement(
						sprintf(
							// translators: %s: plugin title
							__(
								'Your site is connected to above Maxmind license key. <strong>%s</strong> automatically downloads latest GeoLite2 data weekly. You can use the disconnect button above to change the license key.',
								'wds'
							),
							ConfigValues.get('plugin_title', 'admin')
						),
						{ strong: <strong /> }
					)}
					prefix={
						<span className="sui-icon-key" aria-hidden="true" />
					}
					suffix={
						<>
							<Button
								icon="sui-icon-plug-disconnected"
								text={__('Disconnect', 'wds')}
								onClick={(e) => this.handleDisconnect(e)}
								loading={loading}
							></Button>
						</>
					}
					value={maxmindKey}
					readOnly={true}
					loading={loading}
				></TextInputField>

				{!!errMsg && <Notice type="error" message={errMsg} />}
			</>
		);
	}
}

const mapStateToProps = (state) => ({
	maxmindKey: state.redirects.maxmind_license,
});

const mapDispatchToProps = {
	updateProp: (key, value) => ({
		type: 'UPDATE_PROP',
		key,
		value,
	}),
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(MaxmindConfigDeactivation);
