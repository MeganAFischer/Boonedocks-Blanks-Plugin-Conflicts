import ConfigValues from '../../es6/config-values';
import RequestUtil from '../../utils/request-util';

export default class CrawlRequest {
	static redirect(source, destination) {
		return this.post('smartcrawl_crawl_redirect', {
			source,
			destination,
		});
	}

	static changeIssueStatus(issueId, action) {
		return this.post(action, { issue_id: issueId });
	}

	static ignoreIssue(issueId) {
		return this.changeIssueStatus(issueId, 'smartcrawl_crawl_ignore');
	}

	static restoreIssue(issueId) {
		return this.changeIssueStatus(issueId, 'smartcrawl_crawl_restore');
	}

	static restoreAll() {
		return this.post('smartcrawl_crawl_restore_all');
	}

	static addToSitemap(path) {
		return this.post('smartcrawl_sitemap_add_extra', { path });
	}

	static post(action, data) {
		const nonce = ConfigValues.get('nonce', 'crawler');
		return RequestUtil.post(action, nonce, data);
	}
}
