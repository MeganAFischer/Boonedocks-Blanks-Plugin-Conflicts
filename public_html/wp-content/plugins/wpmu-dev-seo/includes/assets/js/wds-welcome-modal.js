(function ($, undefined) {
	window.Wds = window.Wds || {};

	$(init);

	/**
	 * Initialze modal functionality.
	 */
	function init() {
		if ($('#wds-welcome-modal').length) {
			Wds.open_dialog('wds-welcome-modal');
		}

		// On close.
		$(document).on('click', '#wds-welcome-modal-close-button', closeModal);

		// On save.
		$(document).on('click', '#wds-welcome-modal-get-started', saveModal);
	}

	/**
	 * Close the modal using ajax.
	 *
	 * @param {Object} e Event.
	 */
	function closeModal(e) {
		e.preventDefault();
		e.stopPropagation();

		$.post(
			ajaxurl,
			{
				action: 'wds-close-welcome-modal',
				_wds_nonce: _wds_welcome.nonce,
			},
			function (response) {
				if (response.success) {
					Wds.close_dialog();
				}
			}
		);
	}

	/**
	 * Save status of usage tracking using Ajax.
	 *
	 * @param {Event} e Event.
	 */
	function saveModal(e) {
		e.preventDefault();
		e.stopPropagation();

		$(e.target).addClass('sui-button-onload');

		$.post(
			ajaxurl,
			{
				action: 'wds_save_welcome_modal',
				_wds_nonce: _wds_welcome.nonce,
			},
			function (response) {
				if (response.success) {
					Wds.close_dialog();
				}
			}
		);
	}
})(jQuery);
