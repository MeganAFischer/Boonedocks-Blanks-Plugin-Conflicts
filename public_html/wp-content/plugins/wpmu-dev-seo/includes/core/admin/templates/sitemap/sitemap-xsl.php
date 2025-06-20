<?php // phpcs:disable
$whitelabel = ! empty( $whitelabel );
$template   = empty( $template ) ? '' : $template;
if ( ! in_array( $template, array( 'sitemapIndexBody', 'sitemapBody', 'newsSitemapBody' ) ) ) {
	return;
}
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<xsl:stylesheet version="2.0"
                xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
                xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
                xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>

	<xsl:template name="sitemapHead" match="/">
		<title>Sitemap</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<style type="text/css">
			* {
				margin: 0;
				padding: 0;
				border: 0;
				font-size: 100%;
				vertical-align: baseline;
				box-sizing: border-box;
			}

			body {
				font-family: Arial, sans-serif;
				font-size: 12px;
				color: #333333;
				line-height: 20px;
			}

			table {
				border-collapse: collapse;
				margin-bottom: 1em;
				width: 100%;
				clear: both;
				position: relative;
				z-index: 2;
				line-height: 20px;
			}

			caption {
				text-align: left;
				margin-bottom: 30px;
				margin-top: 10px;
			}

			#content {
				width: 90%;
				margin: 0 auto;
				position: relative;
			}

			p {
				text-align: center;
				color: #333;
				font-size: 11px;
			}

			p a {
				color: #6655aa;
				font-weight: bold;
			}

			a {
				color: #17A8E3;
				text-decoration: none;
			}

			a:hover {
				text-decoration: underline;
			}

			td, th {
				text-align: left;
				font-size: 12px;
				padding: 10px 20px;
				white-space: nowrap;
			}

			td:first-child {
				white-space: inherit;
			}

			th {
				background-color: #F2F2F2;
				font-weight: bold;
			}

			tr.even td {
				background-color: #F8F8F8;
			}

			tr.even td:first-of-type,
			th:first-of-type {
				border-radius: 5px 0 0 5px;
			}

			tr.even td:last-of-type,
			th:last-of-type {
				border-radius: 0 5px 5px 0;
			}

			h1 {
				display: table;
				float: left;
				font-size: 16px;
				font-weight: bold;
				margin-top: 30px;
			}

			tbody tr {
				color: #666666;
			}

			.header {
				background: url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iOTYiIGhlaWdodD0iOTciIHZpZXdCb3g9IjAgMCA5NiA5NyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4NCiAgICA8cGF0aCBkPSJNMCA0OC41QzAgMzMuNTg5OSAwIDI2LjEzNDggMi40MzU4NSAyMC4yNTQxQzUuNjgzNjYgMTIuNDEzMiAxMS45MTMyIDYuMTgzNjYgMTkuNzU0MSAyLjkzNTg1QzI1LjYzNDggMC41IDMzLjA4OTkgMC41IDQ4IDAuNUM2Mi45MTAxIDAuNSA3MC4zNjUyIDAuNSA3Ni4yNDU5IDIuOTM1ODVDODQuMDg2OCA2LjE4MzY2IDkwLjMxNjMgMTIuNDEzMiA5My41NjQxIDIwLjI1NDFDOTYgMjYuMTM0OCA5NiAzMy41ODk5IDk2IDQ4LjVDOTYgNjMuNDEwMSA5NiA3MC44NjUyIDkzLjU2NDEgNzYuNzQ1OUM5MC4zMTYzIDg0LjU4NjggODQuMDg2OCA5MC44MTYzIDc2LjI0NTkgOTQuMDY0MUM3MC4zNjUyIDk2LjUgNjIuOTEwMSA5Ni41IDQ4IDk2LjVDMzMuMDg5OSA5Ni41IDI1LjYzNDggOTYuNSAxOS43NTQxIDk0LjA2NDFDMTEuOTEzMiA5MC44MTYzIDUuNjgzNjYgODQuNTg2OCAyLjQzNTg1IDc2Ljc0NTlDMCA3MC44NjUyIDAgNjMuNDEwMSAwIDQ4LjVaIiBmaWxsPSIjREUyNDBBIi8+DQogICAgPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzU4NDRfMTIxMCkiPg0KICAgICAgICA8cGF0aCBkPSJNNTEuMzI1IDUxLjgwMDFMMzYuMzE2NyA2Ni44MDg0TDM5LjY3NTUgNzAuMTY3Mkw1NC42ODM4IDU1LjE1ODlMNTEuMzI1IDUxLjgwMDFaIiBmaWxsPSIjRkY5NDk0Ii8+DQogICAgICAgIDxwYXRoIGQ9Ik01Mi45OTk3IDM2LjAwMDNWMzMuNTAwM0g0NS40OTk3VjM2LjAwMDNINTIuOTk5N1oiIGZpbGw9IndoaXRlIi8+DQogICAgICAgIDxwYXRoIGQ9Ik01NS40OTk3IDMzLjUwMDNINTIuOTk5N1Y0My41MDAzSDU1LjQ5OTdWMzMuNTAwM1oiIGZpbGw9IndoaXRlIi8+DQogICAgICAgIDxwYXRoIGQ9Ik00Ny45OTk3IDI4LjUwMDNDNTEuOTU1NCAyOC41MDAzIDU1LjgyMjIgMjkuNjczNCA1OS4xMTEyIDMxLjg3MUM2Mi40MDAyIDM0LjA2ODYgNjQuOTYzNiAzNy4xOTIxIDY2LjQ3NzQgNDAuODQ2N0M2Ny45OTExIDQ0LjUwMTIgNjguMzg3MSA0OC41MjI1IDY3LjYxNTQgNTIuNDAyMUM2Ni44NDM3IDU2LjI4MTggNjQuOTM4OSA1OS44NDU0IDYyLjE0MTkgNjIuNjQyNUM1OS4zNDQ4IDY1LjQzOTUgNTUuNzgxMiA2Ny4zNDQyIDUxLjkwMTYgNjguMTE2QzQ4LjAyMTkgNjguODg3NyA0NC4wMDA2IDY4LjQ5MTcgNDAuMzQ2MSA2Ni45NzhDMzYuNjkxNiA2NS40NjQyIDMzLjU2OCA2Mi45MDA4IDMxLjM3MDQgNTkuNjExOEMyOS4xNzI4IDU2LjMyMjggMjcuOTk5NyA1Mi40NTU5IDI3Ljk5OTcgNDguNTAwM0MyNy45OTk3IDQzLjE5NiAzMC4xMDY5IDM4LjEwODkgMzMuODU3NiAzNC4zNTgyQzM3LjYwODMgMzAuNjA3NCA0Mi42OTU0IDI4LjUwMDMgNDcuOTk5NyAyOC41MDAzWk00Ny45OTk3IDIzLjUwMDNDNDMuMDU1MiAyMy41MDAzIDM4LjIyMTcgMjQuOTY2NSAzNC4xMTA0IDI3LjcxMzZDMjkuOTk5MiAzMC40NjA2IDI2Ljc5NSAzNC4zNjUxIDI0LjkwMjggMzguOTMzMkMyMy4wMTA2IDQzLjUwMTQgMjIuNTE1NSA0OC41MjgxIDIzLjQ4MDEgNTMuMzc3NkMyNC40NDQ3IDU4LjIyNzIgMjYuODI1OCA2Mi42ODE2IDMwLjMyMjEgNjYuMTc3OUMzMy44MTg0IDY5LjY3NDMgMzguMjcyOSA3Mi4wNTUzIDQzLjEyMjQgNzMuMDJDNDcuOTcyIDczLjk4NDYgNTIuOTk4NyA3My40ODk0IDU3LjU2NjkgNzEuNTk3MkM2Mi4xMzUgNjkuNzA1MSA2Ni4wMzk1IDY2LjUwMDkgNjguNzg2NSA2Mi4zODk2QzcxLjUzMzUgNTguMjc4NCA3Mi45OTk3IDUzLjQ0NDkgNzIuOTk5NyA0OC41MDAzQzcyLjk5OTcgNDEuODY5OSA3MC4zNjU4IDM1LjUxMTEgNjUuNjc3NCAzMC44MjI3QzYwLjk4ODkgMjYuMTM0MyA1NC42MzAyIDIzLjUwMDMgNDcuOTk5NyAyMy41MDAzWiIgZmlsbD0id2hpdGUiLz4NCiAgICAgICAgPHBhdGggZD0iTTUyLjk5OTcgNTMuNTAwMlY1MS4wMDAySDQ1LjQ5OTdWNTMuNTAwMkg1Mi45OTk3WiIgZmlsbD0iI0ZGOTQ5NCIvPg0KICAgICAgICA8cGF0aCBkPSJNNTUuNDk5NyA1MS4wMDAySDUyLjk5OTdWNjEuMDAwMkg1NS40OTk3VjUxLjAwMDJaIiBmaWxsPSIjRkY5NDk0Ii8+DQogICAgICAgIDxwYXRoIGQ9Ik01MS4zMTA2IDM0LjMxMTZMMjYuMzE0MyA1OS4zMDc5TDI5LjY3MzEgNjIuNjY2N0w1NC42Njk0IDM3LjY3MDRMNTEuMzEwNiAzNC4zMTE2WiIgZmlsbD0id2hpdGUiLz4NCiAgICA8L2c+DQogICAgPGRlZnM+DQogICAgICAgIDxjbGlwUGF0aCBpZD0iY2xpcDBfNTg0NF8xMjEwIj4NCiAgICAgICAgICAgIDxyZWN0IHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgZmlsbD0id2hpdGUiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDIyLjk5OTkgMjMuNTAwMSkiLz4NCiAgICAgICAgPC9jbGlwUGF0aD4NCiAgICA8L2RlZnM+DQo8L3N2Zz4NCg==") no-repeat right center;
				background-size: 60px 80px;
				display: table;
				float: right;
				height: 80px;
				position: absolute;
				right: 15px;
				top: 30px;
				width: 190px;
				z-index: 3;
			}

			.header span {
				display: table-cell;
				padding-bottom: 10px;
				vertical-align: bottom;
				color: #888888;
				font-size: 10px;
			}

			.header span a {
				color: #333333;
			}

			.footer,
			.footer a,
			.footer a:hover,
			.footer a:active,
			.footer a:focus {
				color: #888888;
				font-size: 10px;
				margin-top: 30px;
			}

			.footer a {
				font-weight: bold;
			}

			@media all and (max-width: 700px) {
				td, th {
					padding: 10px;
				}
			}
		</style>
	</xsl:template>

	<xsl:template name="sitemapHeader" match="/">
		<div class="header">
			<span>Powered by
				<a target="_blank" href="https://wpmudev.com/project/smartcrawl-wordpress-seo/">SmartCrawl</a>
			</span>
		</div>
	</xsl:template>

	<xsl:template name="sitemapFooter" match="/">
		<p class="footer">
			This is an XML Sitemap, meant for consumption by search engines. For more info visit <a
				href="http://sitemaps.org">sitemaps.org</a>.
		</p>
	</xsl:template>

	<xsl:template name="newsSitemapBody" match="/">
		<div id="content">
			<xsl:call-template name="sitemapHeader"/>
			<h1>News Sitemap</h1>
			<table id="sitemap">
				<caption>This XML sitemap file contains
					<strong>
						<xsl:value-of select="count(sitemap:urlset/sitemap:url)"/>
					</strong>
					URLs.
				</caption>
				<thead>
				<tr>
					<th width="70%" valign="bottom">URL</th>
					<th width="20%" valign="bottom">Publication Title</th>
					<th width="10%" valign="bottom">Publication Date</th>
				</tr>
				</thead>
				<tbody>
				<xsl:for-each select="sitemap:urlset/sitemap:url">
					<xsl:variable name="css-class">
						<xsl:choose>
							<xsl:when test="position() mod 2 = 0">even</xsl:when>
							<xsl:otherwise>odd</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<tr class="{$css-class}">
						<xsl:variable name="item_url">
							<xsl:value-of select="sitemap:loc"/>
						</xsl:variable>
						<td>
							<a href="{$item_url}">
								<xsl:value-of select="sitemap:loc"/>
							</a>
						</td>
						<td style="white-space: break-spaces;">
							<a href="{$item_url}">
								<xsl:value-of select="news:news/news:title"/>
							</a>
						</td>
						<td>
							<xsl:value-of
								select="concat(substring(news:news/news:publication_date,0,11),concat(' ', substring(news:news/news:publication_date,12,5)))"/>
						</td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table>
			<xsl:call-template name="sitemapFooter"/>
		</div>
	</xsl:template>

	<xsl:template name="sitemapBody" match="/">
		<div id="content">
			<xsl:call-template name="sitemapHeader"/>
			<h1>Sitemap</h1>
			<table id="sitemap">
				<caption>This XML sitemap file contains
					<strong>
						<xsl:value-of select="count(sitemap:urlset/sitemap:url)"/>
					</strong>
					URLs.
				</caption>
				<thead>
				<tr>
					<th width="85%" valign="bottom">URL</th>
					<th width="5%" valign="bottom">Images</th>
					<th width="10%" valign="bottom">Last Modified</th>
				</tr>
				</thead>
				<tbody>
				<xsl:for-each select="sitemap:urlset/sitemap:url">
					<xsl:variable name="css-class">
						<xsl:choose>
							<xsl:when test="position() mod 2 = 0">even</xsl:when>
							<xsl:otherwise>odd</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<tr class="{$css-class}">
						<td>
							<xsl:variable name="item_url">
								<xsl:value-of select="sitemap:loc"/>
							</xsl:variable>
							<a href="{$item_url}">
								<xsl:value-of select="sitemap:loc"/>
							</a>
						</td>
						<td>
							<xsl:value-of select="count(image:image)"/>
						</td>
						<td>
							<xsl:value-of
								select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,5)))"/>
						</td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table>
			<xsl:call-template name="sitemapFooter"/>
		</div>
	</xsl:template>

	<xsl:template name="sitemapIndexBody" match="/">
		<div id="content">
			<xsl:call-template name="sitemapHeader"/>
			<h1>Sitemap Index</h1>
			<table id="sitemap">
				<caption>This XML sitemap index file contains
					<strong>
						<xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/>
					</strong>
					sitemaps.
				</caption>
				<thead>
				<tr>
					<th width="100%" valign="bottom">Sitemap</th>
				</tr>
				</thead>
				<tbody>
				<xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
					<xsl:variable name="css-class">
						<xsl:choose>
							<xsl:when test="position() mod 2 = 0">even</xsl:when>
							<xsl:otherwise>odd</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<tr class="{$css-class}">
						<td>
							<xsl:variable name="item_url">
								<xsl:value-of select="sitemap:loc"/>
							</xsl:variable>
							<a href="{$item_url}">
								<xsl:value-of select="sitemap:loc"/>
							</a>
						</td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table>
			<xsl:call-template name="sitemapFooter"/>
		</div>
	</xsl:template>

	<xsl:template match="/">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<xsl:call-template name="sitemapHead"/>

			<?php if ( $whitelabel ) : ?>
				<style>
					.header {
						display: none;
					}
				</style>
			<?php endif; ?>
		</head>
		<body>
		<xsl:call-template name="<?php echo esc_attr( $template ); ?>"/>
		</body>
		</html>
	</xsl:template>
</xsl:stylesheet>