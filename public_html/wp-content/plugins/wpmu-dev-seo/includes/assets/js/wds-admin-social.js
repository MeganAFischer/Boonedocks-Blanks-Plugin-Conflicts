(function ($) {
	window.Wds = window.Wds || {};

	function init() {
		window.Wds.hook_conditionals();
		window.Wds.hook_toggleables();
		window.Wds.media_item_selector($('#organization_logo'));
		window.Wds.vertical_tabs();

		$(document).on(
			'click',
			'#wds-deactivate-social-component',
			deactivateSocialComponent
		);
	}

	function deactivateSocialComponent(e) {
		$(this).addClass('disabled');

		e.preventDefault();
		e.stopPropagation();

		$.post(
			ajaxurl,
			{
				action: 'wds_change_social_status',
				_wds_nonce: _wds_social.nonce,
				status: '0',
			},
			function () {
				window.location.reload();
			}
		);
	}

	$(init);
})(jQuery);
