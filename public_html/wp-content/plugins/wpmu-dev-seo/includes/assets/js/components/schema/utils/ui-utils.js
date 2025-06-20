import NoticeUtil from '../../../utils/notice-util';
import { escapeHTML } from '@wordpress/escape-html';

export function showNotice(message, type, dismiss = false) {
	NoticeUtil.showNotice(
		'wds-schema-types-notice',
		escapeHTML(message),
		type,
		dismiss
	);
}
