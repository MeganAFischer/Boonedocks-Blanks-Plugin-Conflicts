import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SettingsRow from '../../../components/settings-row';
import InsertVariablesField from '../../../components/form-fields/insert-variables-field';
import { connect } from 'react-redux';

class Formats extends React.Component {
	handleChange(type, value) {
		const { options, updateOption } = this.props;

		if (!Object.keys(options.labels).length) {
			options.labels = {};
		}

		options.labels[type] = value;

		updateOption('labels', options.labels);
	}

	render() {
		const { formats, options, loading } = this.props;

		return (
			<SettingsRow
				label={__('Breadcrumbs Label Format', 'wds')}
				description={__(
					'Customize your breadcrumbs label formats across your site. ',
					'wds'
				)}
			>
				<div className="sui-border-frame">
					{formats.map((format, index) => {
						return (
							<div className="sui-row" key={index}>
								<div className="sui-col-2">
									<InsertVariablesField
										label={sprintf(
											/* translators: %s: Breadcrumb type name */
											__('%s Label Format'),
											format.title || format.label
										)}
										value={
											options.labels[format.type] ||
											format.placeholder
										}
										variables={format.variables}
										placeholder={format.placeholder}
										disabled={loading}
										onChange={(val) =>
											this.handleChange(format.type, val)
										}
									/>
								</div>
							</div>
						);
					})}
				</div>
			</SettingsRow>
		);
	}
}

const mapStateToProps = (state) => ({ ...state.breadcrumbs });

const mapDispatchToProps = {
	updateOption: (key, value) => ({
		type: 'UPDATE_OPTION',
		key,
		value,
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(Formats);
