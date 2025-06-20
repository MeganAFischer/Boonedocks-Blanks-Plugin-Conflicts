import React from 'react';
import { __ } from '@wordpress/i18n';
import update from 'immutability-helper';

export default class ImageUploads extends React.Component {
	static defaultProps = {
		name: '',
		isSingle: false,
		images: [],
	};

	constructor(props) {
		super(props);

		this.state = {
			images: this.props.images,
		};
	}

	handleAdd() {
		if (!this.frame) {
			this.frame = new wp.media({
				multiple: false,
				library: { type: 'image' },
			});

			this.frame.off('select').on('select', this.handleSelect.bind(this));
		}

		this.frame.open();
	}

	handleSelect() {
		const selection = this.frame.state().get('selection');

		if (!selection) {
			return false;
		}

		let id, url;

		selection.each(function (model) {
			id = model.get('id');
			url = model.get('url');
		});

		if (!id || !url) {
			return false;
		}

		if (!this.state.images.find((img) => img.id === id)) {
			this.setState({ images: [...this.state.images, { id, url }] });
		}
	}

	handleRemove(id) {
		const index = this.state.images.findIndex((img) => img.id === id);

		if (index !== -1) {
			this.setState({
				images: update(this.state.images, { $splice: [[index, 1]] }),
			});
		}
	}

	render() {
		const { name, isSingle } = this.props;
		const { images } = this.state;

		return (
			<div className="og-images">
				{(!isSingle || !images.length) && (
					<div
						className="add-action-wrapper sui-tooltip"
						data-tooltip={__(
							'Add featured image',
							'wds'
						)}
					>
						<a
							href="#"
							id={`${name}-images`}
							title={__('Add image', 'wds')}
							onClick={() => this.handleAdd()}
						>
							<span
								className="sui-icon-upload-cloud"
								aria-hidden="true"
							></span>
						</a>
					</div>
				)}

				{!!images.length &&
					images.map((img, index) => (
						<div key={index} className="og-image item">
							<img src={img.url} alt={`${name} ${index}`} />
							<input
								type="hidden"
								value={img.id}
								name={`${name}[images][]`}
							/>
							<a
								href="#"
								className="remove-action"
								onClick={() => this.handleRemove(img.id)}
							>
								<span
									className="sui-icon-close"
									aria-hidden="true"
								></span>
							</a>
						</div>
					))}
			</div>
		);
	}
}
