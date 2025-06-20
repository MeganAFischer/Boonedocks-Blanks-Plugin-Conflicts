import wp from 'wp';
import _ from '_';
import ConfigValues from './config-values';
import StringUtils from './string-utils';
import $ from 'jQuery';

class MacroReplacement {
	/**
	 * @param postObjectFetcher {PostObjectFetcher}
	 */
	constructor(postObjectFetcher) {
		this.fetcher = postObjectFetcher;
	}

	/**
	 * @param date {Date}
	 * @param format {string}
	 */
	format_date(date, format) {
		return wp.date.dateI18n(format, date);
	}

	/**
	 * @param {Post} post
	 */
	get_excerpt_or_trimmed_content(post) {
		return this.get_trimmed_excerpt(post.get_excerpt(), post.get_content());
	}

	get_trimmed_excerpt(excerpt, content) {
		let string = !!excerpt ? excerpt : content,
			metadesc_max_length = ConfigValues.get(
				'metadesc_max_length',
				'replacement'
			);

		// Strip all HTML tags
		string = StringUtils.strip_html(string);
		// Normalize whitespace
		string = StringUtils.normalize_whitespace(string);

		let shortcode_position = string.indexOf('[');
		if (
			shortcode_position !== -1 &&
			shortcode_position < metadesc_max_length
		) {
			// Remove shortcodes but keep the content
			string = StringUtils.remove_shortcodes(string);
		}
		// TODO: Encode any HTML entities like > and <

		return this.truncate_meta_description(string, metadesc_max_length);
	}

	truncate_meta_description(string, metadesc_max_length) {
		return StringUtils.truncate_string(string, metadesc_max_length);
	}

	/**
	 * @param text
	 * @param {Term} term
	 * @returns {Promise<unknown>}
	 */
	replace_term_macros(text, term) {
		let specific_replacements = {
			'%%id%%': term.get_id(),
			'%%term_title%%': term.get_title(),
			'%%term_description%%': term.get_description(),
		};

		if (term.get_taxonomy() === 'category') {
			specific_replacements['%%category%%'] = term.get_title();
			specific_replacements['%%category_description%%'] =
				term.get_description();
		} else if (term.get_taxonomy() === 'post_tag') {
			specific_replacements['%%tag%%'] = term.get_title();
			specific_replacements['%%tag_description%%'] =
				term.get_description();
		}

		return this.do_replace(text, {}, specific_replacements);
	}

	/**
	 * @param text
	 * @param {Post} post
	 */
	replace(text, post) {
		let fetcher = this.fetcher;
		let load_required = {
			'%%name%%': () => {
				return new Promise((resolve, reject) => {
					fetcher
						.fetch_author_data(post.get_author_id())
						.then((author) => {
							let name = author && author.name ? author.name : '',
								macro = { '%%name%%': name };
							resolve(macro);
						})
						.catch(reject);
				});
			},
			'%%category%%': (matches, field = 'name', first_only = false) => {
				return new Promise((resolve, reject) => {
					fetcher
						.fetch_category_data(post.get_category_ids())
						.then((categories) => {
							let category_names = this.get_term_macro_value(
									categories,
									field,
									first_only
								),
								macro = {};

							if (matches && matches.length) {
								macro[matches[0]] = category_names;
							}

							resolve(macro);
						})
						.catch(reject);
				});
			},
			'%%tag%%': (matches, field = 'name', first_only = false) => {
				return new Promise((resolve, reject) => {
					fetcher
						.fetch_tag_data(post.get_tag_ids())
						.then((tags) => {
							let tags_names = this.get_term_macro_value(
									tags,
									field,
									first_only
								),
								macro = {};

							if (matches && matches.length) {
								macro[matches[0]] = tags_names;
							}

							resolve(macro);
						})
						.catch(reject);
				});
			},
		};

		load_required['%%ct_(desc_){0,1}([a-zA-Z0-9!@#$^&*_+-=:;~]+)%%'] = (
			matches
		) => {
			let field = 'name';
			if (matches.length === 3 && matches[1] === 'desc_') {
				field = 'description';
			}

			let taxonomy = matches[2];
			if (taxonomy === 'category') {
				let category = load_required['%%category%%'];
				return category(matches, field, true);
			} else if (taxonomy === 'post_tag') {
				let tag = load_required['%%tag%%'];
				return tag(matches, field, true);
			}

			return new Promise((resolve, reject) => {
				fetcher
					.fetch_taxonomy_terms(
						taxonomy,
						post.get_taxonomy_terms(taxonomy)
					)
					.then((terms) => {
						let term_names = this.get_term_macro_value(
								terms,
								field,
								true
							),
							macro = {};

						if (matches && matches.length) {
							macro[matches[0]] = term_names;
						}

						resolve(macro);
					})
					.catch(reject);
			});
		};

		load_required['%%cf_([a-zA-Z0-9!@#$^&*_+-=:;~]+)%%'] = (matches) => {
			return new Promise((resolve, reject) => {
				if (matches && matches.length) {
					const macro = {};

					$('#postcustomstuff [id^="meta-"][id$="-key"]').each(
						(index, el) => {
							if (el.value === matches[1]) {
								macro[matches[0]] = $(el)
									.closest('td')
									.siblings('td')
									.find('[id^="meta-"][id$="-value"]')
									.val();
							}
						}
					);

					resolve(macro);
				} else {
					reject();
				}
			});
		};

		return this.do_replace(text, load_required, {
			'%%id%%': post.get_id(),
			'%%title%%': post.get_title(),
			'%%excerpt%%': this.get_excerpt_or_trimmed_content(post),
			'%%excerpt_only%%': post.get_excerpt(),
			'%%modified%%': this.format_date(
				post.get_modified(),
				'Y-m-d H:i:s'
			),
			'%%date%%': this.format_date(
				post.get_date(),
				this.get_date_format()
			),
			'%%userid%%': post.get_author_id(),
			'%%caption%%': post.get_excerpt(),
		});
	}

	do_replace(text, dynamic_macros, specific_replacements) {
		let promises = [];
		Object.keys(dynamic_macros).forEach((macro) => {
			let callback = dynamic_macros[macro],
				all_matches = [...text.matchAll(macro)];

			if (all_matches && all_matches.length) {
				all_matches.forEach((match) => {
					if (match && match.length) {
						promises.push(callback(match));
					}
				});
			}
		});

		return new Promise((resolve, reject) => {
			Promise.all(promises)
				.then((loaded) => {
					loaded.push(specific_replacements);
					loaded.push(this.get_general_replacements());

					let replacements = loaded.reduce((result, current) => {
						return Object.assign(result, current);
					}, {});

					Object.keys(replacements).forEach((macro_key) => {
						text = text.replaceAll(
							macro_key,
							replacements[macro_key]
						);
					});

					// Strip out any remaining unrecognized macros
					let unrecognizedMacrosRegex = new RegExp(
						'%%[a-zA-Z0-9!@#$^&*_+-=:;~]+%%',
						'g'
					);
					text = text.replace(unrecognizedMacrosRegex, '');

					resolve(text);
				})
				.catch(reject);
		});
	}

	get_term_macro_value(terms, field, first_only) {
		if (!Array.isArray(terms)) {
			terms = [];
		}

		terms = _.sortBy(terms, (term) => {
			return term.name;
		});

		let values = terms.reduce(function (result, item) {
			if (item.hasOwnProperty(field)) {
				result.push(item[field]);
			}
			return result;
		}, []);

		return first_only && values.length ? values[0] : values.join(', ');
	}

	get_general_replacements() {
		let timeFormat = ConfigValues.get('time_format', 'replacement');

		return {
			'%%sep%%': this.get_global('sep'),
			'%%sitename%%': this.get_global('sitename'),
			'%%sitedesc%%': this.get_global('sitedesc'),
			'%%page%%': this.get_global('page'),
			'%%pagetotal%%': this.get_global('pagetotal'),
			'%%pagenumber%%': this.get_global('pagenumber'),
			'%%spell_pagenumber%%': this.get_global('spell_pagenumber'),
			'%%spell_pagetotal%%': this.get_global('spell_pagetotal'),
			'%%spell_page%%': this.get_global('spell_page'),
			'%%currenttime%%': this.format_date(new Date(), timeFormat),
			'%%currentdate%%': this.format_date(
				new Date(),
				this.get_date_format()
			),
			'%%currentmonth%%': this.format_date(new Date(), 'F'),
			'%%currentyear%%': this.format_date(new Date(), 'Y'),
		};
	}

	get_date_format() {
		return ConfigValues.get('date_format', 'replacement');
	}

	get_global(key) {
		return ConfigValues.get(['replacements', key], 'replacement');
	}
}

export default MacroReplacement;
