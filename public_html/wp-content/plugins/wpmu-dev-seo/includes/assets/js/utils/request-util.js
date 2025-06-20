import $ from 'jQuery';
import ajaxUrl from 'ajaxUrl';

export default class RequestUtil {
	static post(action, nonce, data = {}) {
		return new Promise(function (resolve, reject) {
			const request = Object.assign(
				{},
				{
					action,
					_wds_nonce: nonce,
				},
				data
			);

			$.post(ajaxUrl, request)
				.done((response) => {
					if (response.success) {
						resolve(response?.data);
					} else {
						reject(response?.data?.message);
					}
				})
				.fail(() => reject());
		});
	}

	static uploadFile(action, nonce, file) {
		const formData = new FormData();

		formData.append('file', file);
		formData.append('action', action);
		formData.append('_wds_nonce', nonce);

		return new Promise((resolve, reject) => {
			$.ajax({
				url: ajaxUrl,
				cache: false,
				contentType: false,
				processData: false,
				type: 'post',
				data: formData,
			})
				.done((response) => {
					if (response.success) {
						resolve(response?.data);
					} else {
						reject(response?.data?.message);
					}
				})
				.fail(() => reject());
		});
	}
}
