export default class FormattingUtil {
	/**
	 * Sanitizes an HTML classname to ensure it only contains valid characters.
	 *
	 * Strips the string down to A-Z,a-z,0-9,_,-. If this results in an empty
	 * string then it will return the alternative value supplied.
	 *
	 * This is a migration of sanitize_html_class method of WP PHP.
	 *
	 * @since 3.8.0
	 *
	 * @param {string} className The classname to be sanitized.
	 *
	 * @return {string} The sanitized value.
	 */
	static sanitizeHtmlClass(className) {
		// Strip out any percent-encoded characters.
		const sanitized = className.replace(
			/|%[a-fA-F0-9][a-fA-F0-9]|/g,
			'',
			className
		);

		// Limit to A-Z, a-z, 0-9, '_', '-'.
		return sanitized.replace(/[^A-Za-z0-9_-]/g, '');
	}
}
