# CC SEO Enhancer

CC SEO Enhancer is a focused WordPress SEO helper plugin for metadata, Open Graph output, Schema.org JSON-LD, tracking snippets, author social links, and virtual `robots.txt` rules.

It is designed to work with WordPress core sitemap support. The plugin does not generate a separate `sitemap.xml` file.

## Features

- Outputs meta descriptions, canonical URLs, Open Graph tags, and Twitter Card tags
- Adds Schema.org JSON-LD for site, organization/person, breadcrumbs, posts, archives, and author pages
- Adds author social profile fields
- Supports Google Analytics, Google Tag Manager, Facebook Pixel, and webmaster verification tags
- Includes a cookie notice banner for disclosure
- Adds custom `Disallow` paths to WordPress virtual `robots.txt`
- Uses WordPress core `/wp-sitemap.xml` instead of generating a custom sitemap
- Translation ready with the `cc-seo-enhancer` text domain

## Installation

1. Upload the plugin folder `cc-seo-enhancer` to `/wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. Go to `Settings > CC SEO Enhancer`
4. Configure metadata, schema, tracking, and robots.txt options

## Notes

- The plugin does not create physical `robots.txt` or `sitemap.xml` files
- WordPress serves the virtual robots output at `/robots.txt`
- WordPress core serves the sitemap index at `/wp-sitemap.xml`
- Existing physical files at the site root can override WordPress virtual output depending on server configuration
- Tracking scripts are controlled by their own settings; the cookie notice is informational

## Changelog

### 1.0.0

- Initial release
- Add i18n structure and Traditional Chinese translation files
- Use WordPress core sitemap support
- Use WordPress virtual robots.txt output
