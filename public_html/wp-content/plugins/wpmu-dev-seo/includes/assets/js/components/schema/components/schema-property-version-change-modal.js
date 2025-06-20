import React from 'react';
import { __ } from '@wordpress/i18n';
import BoxSelectorModal from '../../box-selector-modal';
import { propertyHasAltVersions } from '../utils/property-utils';

export default class SchemaPropertyVersionChangeModal extends React.Component {
	static defaultProps = {
		id: '',
		parent: {},
		onClose: () => false,
		onChangePropertyVersion: () => false,
	};

	render() {
		const { onChangePropertyVersion, onClose } = this.props;
		const options = this.getAltVersionOptions();

		return (
			<BoxSelectorModal
				id="wds-change-property-type"
				title={__('Change Property Type', 'wds')}
				description={__(
					'Select one of the following types to switch.',
					'wds'
				)}
				actionButtonText={__('Change', 'wds')}
				actionButtonIcon="sui-icon-defer"
				onClose={onClose}
				onAction={(selectedVersion) =>
					onChangePropertyVersion(selectedVersion)
				}
				options={options}
				multiple={false}
			/>
		);
	}

	getAltVersionOptions() {
		const { parent } = this.props;
		const activeVersion = parent.activeVersion;

		if (!propertyHasAltVersions(parent)) {
			return false;
		}

		const versions = [];
		Object.keys(parent.properties).forEach((version) => {
			const altVersion = parent.properties[version];

			if (version !== activeVersion) {
				versions.push({
					id: version,
					label: altVersion.label,
				});
			}
		});

		return versions;
	}
}
