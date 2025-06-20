import { __ } from '@wordpress/i18n';
import merge from 'lodash-es/merge';
import DateTime from './date-time';
import Email from './email';
import ImageObject from './image-object';
import ImageURL from './image-url';
import Phone from './phone';
import TextFull from './text-full';
import URL from './url';
import Array from './array';
import Number from './number';
import Duration from './duration';

function getAllValuesForSource(source) {
	let merged = {};
	const types = [
		DateTime,
		Email,
		ImageObject,
		ImageURL,
		Phone,
		TextFull,
		URL,
		Array,
		Number,
		Duration,
	];
	types.forEach((type) => {
		const sources = type.sources;
		if (sources.hasOwnProperty(source)) {
			merged = merge(merged, sources[source].values);
		}
	});
	return merged;
}

const Dynamic = {
	id: 'Dynamic',
	sources: {
		author: {
			label: __('Post Author', 'wds'),
			values: getAllValuesForSource('author'),
		},
		post_data: {
			label: __('Post Data', 'wds'),
			values: getAllValuesForSource('post_data'),
		},
		schema_settings: {
			label: __('Schema Settings', 'wds'),
			values: getAllValuesForSource('schema_settings'),
		},
		seo_meta: {
			label: __('SEO Meta', 'wds'),
			values: getAllValuesForSource('seo_meta'),
		},
		site_settings: {
			label: __('Site Settings', 'wds'),
			values: getAllValuesForSource('site_settings'),
		},
		post_meta: {
			label: __('Post Meta', 'wds'),
		},
		custom_text: {
			label: __('Custom Text', 'wds'),
		},
		datetime: {
			label: __('Custom Date', 'wds'),
		},
		image: {
			label: __('Custom Image', 'wds'),
		},
		image_url: {
			label: __('Custom Image URL', 'wds'),
		},
		number: {
			label: __('Custom Number', 'wds'),
		},
		duration: {
			label: __('Custom Duration', 'wds'),
		},
	},
};
export default Dynamic;
