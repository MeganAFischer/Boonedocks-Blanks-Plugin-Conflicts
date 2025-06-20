import React from 'react';

export default class ProgressBar extends React.Component {
	static defaultProps = {
		progress: '',
		stateMessage: '',
	};

	render() {
		const progress = Math.ceil(this.props.progress);
		const progressPercentage = progress + '%';
		return (
			<React.Fragment>
				<div className="sui-progress-block">
					<div className="sui-progress">
						<span className="sui-progress-icon" aria-hidden="true">
							<span className="sui-icon-loader sui-loading" />
						</span>

						<div className="sui-progress-text">
							{progressPercentage}
						</div>

						<div className="sui-progress-bar">
							<span
								style={{
									transition:
										progress === 0
											? false
											: 'transform 0.4s linear 0s',
									transformOrigin: 'left center',
									transform: `translateX(${progress - 100}%)`,
								}}
							/>
						</div>
					</div>
				</div>
				<div className="sui-progress-state">
					{this.props.stateMessage}
				</div>
			</React.Fragment>
		);
	}
}
