import React from 'react';
import { __ } from '@wordpress/i18n';
import { uniqueId } from 'lodash-es';
import TextInputField from '../../../components/form-fields/text-input-field';
import SettingsRow from '../../../components/settings-row';
import { connect } from 'react-redux';

class Separators extends React.Component {
	render() {
		const { options, separators, loading, updateOption } = this.props;

		return (
			<SettingsRow
				label={__('Breadcrumbs Separator', 'wds')}
				description={__(
					'Select a breadcrumbs separator from the list or add a custom separator. You can also use HTML characters.',
					'wds'
				)}
			>
				<div className="wds-preset-separators">
					{Object.keys(separators).map((key) => {
						const id = uniqueId(key);

						return (
							<React.Fragment key={key}>
								<input
									type="radio"
									id={id}
									value={key}
									autoComplete="off"
									disabled={loading}
									checked={
										!options.custom_sep &&
										options.separator === key
									}
									onChange={(e) => {
										updateOption(
											'separator',
											e.target.value
										);
										updateOption('custom_sep', '');
									}}
								/>
								<label
									className="separator-selector"
									htmlFor={id}
								>
									{separators[key]}
								</label>
							</React.Fragment>
						);
					})}
				</div>
				<div className="wds-custom-separator">
					<TextInputField
						className="sui-input-md"
						label={__(
							'Enter your own custom separator',
							'wds'
						)}
						placeholder={__('Enter separator', 'wds')}
						value={options.custom_sep}
						disabled={loading}
						onChange={(e) => updateOption('custom_sep', e)}
					></TextInputField>
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

export default connect(mapStateToProps, mapDispatchToProps)(Separators);
