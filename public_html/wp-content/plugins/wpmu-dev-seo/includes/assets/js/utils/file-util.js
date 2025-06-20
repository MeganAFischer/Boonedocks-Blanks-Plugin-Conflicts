export default class FileUtil {
	static triggerFileDownload(content, fileName) {
		const a = document.createElement('a'),
			blob = new Blob([content], { type: 'application/json' }),
			url = window.URL.createObjectURL(blob);

		a.href = url;
		a.download = fileName;
		a.click();
		window.URL.revokeObjectURL(url);
	}
}
