<?php
/**
 * Template: Native Dismissible Notice Script and Style.
 *
 * @package SmartCrawl
 */

?>

<style type="text/css">
	.wds-native-dismiss,
	.wds-inline-notice-link {
		vertical-align: -4px;
		font-size: 12px;
		margin-left: 8px;
		text-decoration: none;
	}

	.wds-native-dismiss {
		font-weight: 500;
	}

	.wds-native-dismissible-notice button.notice-dismiss[disabled] {
		cursor: wait;
	}

	.wds-native-dismissible-notice button.notice-dismiss[disabled]:before {
		color: #e7e7e7;
	}
</style>
<script type="application/javascript">
	jQuery(function ($) {
		$(document).on('click', '.wds-native-dismissible-notice .wds-native-dismiss,.wds-native-dismissible-notice .notice-dismiss', function (e) {
			e.preventDefault();
			var $notice = $(this).closest('.wds-native-dismissible-notice'),
				$dismiss_buttons = $('.wds-native-dismissible-notice .notice-dismiss'),
				message_key = $notice.data('messageKey');
			$notice.remove();

			$dismiss_buttons.prop('disabled', true);

			var queryStr = window.location.search,
			queryParams = queryStr.slice(1).split('&'),
			page = '';

			for(var i=0;i<queryParams.length; i++) {
				var pair = queryParams[i].split('=');
				var key = pair[0];

				if (key === 'page') {
					page = pair[1];
					break;
				}
			}

			$.post(
				ajaxurl,
				{
					action: 'wds_dismiss_message',
					message: message_key,
					page: page,
					_wds_nonce: '<?php echo esc_js( wp_create_nonce( 'wds-admin-nonce' ) ); ?>'
				},
				function () {
					$dismiss_buttons.prop('disabled', false);
				},
				'json'
			);
		});
	});
</script>