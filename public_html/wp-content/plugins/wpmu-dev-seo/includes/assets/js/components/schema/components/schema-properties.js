import React from 'react';
import SchemaPropertyContainer from './schema-property';

export default class SchemaProperties extends React.Component {
	static defaultProps = {
		typeId: '',
		properties: {},
	};

	render() {
		const { typeId } = this.props;

		return (
			<React.Fragment>
				{Object.keys(this.props.properties).map((propertyKey) => {
					const property = this.props.properties[propertyKey];
					return (
						<SchemaPropertyContainer
							typeId={typeId}
							key={'schema-property-' + property.id}
							id={property.id}
						/>
					);
				})}
			</React.Fragment>
		);
	}
}
