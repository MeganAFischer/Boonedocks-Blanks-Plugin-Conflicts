import React from 'react';
import { __ } from '@wordpress/i18n';
import SettingsRow from '../../../components/settings-row';
import Toggle from '../../../components/toggle';
import TextInputField from '../../../components/form-fields/text-input-field';
import ImageUploadsField from '../../../components/form-fields/image-uploads-field';

export default class SocialItem extends React.Component {
	static defaultProps = {
		type: 'opengraph',
		label: '',
		description: '',
		titlePlaceholder: '',
		titleValue: '',
		descPlaceholder: '',
		descValue: '',
		disabled: false,
		images: [],
		isSingle: false,
	};

	render() {
		const {
			type,
			label,
			description,
			titlePlaceholder,
			titleValue,
			descPlaceholder,
			descValue,
			disabled,
			images,
			isSingle,
		} = this.props;

		return (
			<SettingsRow label={label} description={description}>
				<Toggle
					id={`wds-${type}-disabled`}
					name={`wds-${type}[disabled]`}
					label={__('Enable for this post', 'wds')}
					checked={disabled}
					inverted={true}
				>
					<TextInputField
						id={`wds-${type}-title`}
						name={`wds-${type}[title]`}
						label={__('Title', 'wds')}
						placeholder={titlePlaceholder}
						value={titleValue}
					></TextInputField>
					<TextInputField
						id={`wds-${type}-description`}
						name={`wds-${type}[description]`}
						label={__('Description', 'wds')}
						placeholder={descPlaceholder}
						value={descValue}
					></TextInputField>
					<ImageUploadsField
						id={`wds-${type}-images`}
						label={
							isSingle
								? __('Featured Image', 'wds')
								: __('Featured Images', 'wds')
						}
						name={`wds-${type}`}
						isSingle={isSingle}
						images={images}
					></ImageUploadsField>
				</Toggle>
			</SettingsRow>
		);
	}
}
