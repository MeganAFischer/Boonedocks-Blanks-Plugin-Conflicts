import React from 'react';

export default class UpsellNotice extends React.Component {
	static defaultProps = {
		image: '',
		alt: '',
		dismissible: false,
		onDismiss: () => false,
		message: '',
		button: false,
	};

	render() {
		const { image, alt, dismissible, onDismiss, message, button } =
			this.props;

		return (
			<div className="sui-upsell-notice">
				{!!image && (
					<div
						className="sui-upsell-notice__image"
						aria-hidden="true"
					>
						<img
							className="sui-image sui-upsell-image"
							src={image}
							alt={alt}
						/>
					</div>
				)}

				<div className="sui-upsell-notice__content">
					<div className="sui-notice sui-notice-purple">
						<div className="sui-notice-content">
							<div className="sui-notice-message">
								<span
									className="sui-notice-icon sui-icon-info sui-md"
									aria-hidden="true"
								></span>

								{dismissible && (
									<span
										className="wds-mascot-bubble-dismiss"
										onClick={onDismiss}
									>
										<span
											className="sui-icon-check"
											aria-hidden="true"
										/>
									</span>
								)}

								<p>{message}</p>

								{button && <p>{button}</p>}
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	}
}
