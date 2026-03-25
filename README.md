# Production Image Redirector

A simple WordPress plugin that redirects image URLs under `wp-content/uploads/` on the current site to a production site URL. This is particularly useful for local or staging environments where you want to use production media without copying the entire uploads directory.

## Features

- **Easy configuration**: Admin screen for the production base URL and an on/off toggle
- **HTTP Basic Authentication**: Optional username and password when the production site is behind htpasswd-style protection
- **Coverage**:
  - Attachment URLs, `wp_get_attachment_image_*`, and `wp_calculate_image_srcset`
  - Post content (`the_content`)
  - Classic and block widget content (`widget_text`, `widget_block_content`)
  - Inline `srcset` and `background-image` URLs in HTML (uploads paths only)
- **Developer hooks** (see below) to adjust behavior without forking
- **Coding standards**: [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards) via PHPCS (Composer)

## Installation

1. Upload the `production-image-redirector` folder to `/wp-content/plugins/`
2. Activate the plugin under **Plugins**
3. Go to **Settings → Image Redirector** to configure

## Configuration

1. Open **Settings → Image Redirector**
2. Set **Production Site URL** (e.g. `https://yoursite.com`)
3. Enable **Enable Image Redirect**
4. If the production site uses **HTTP Basic Authentication**, set **HTTP Authentication Username** and **HTTP Authentication Password** (optional). Leaving the password field blank when saving keeps the stored password.
5. Save changes

### Security note (HTTP Basic Auth)

When both username and password are set, credentials are embedded in image URLs (`https://user:pass@host/...`) so the browser can load protected assets. Treat this as suitable for **local/staging** or low-risk setups. Credentials can be exposed via referrers, server logs, browser history, and anyone who can view page source or DevTools. Do not use high-value production secrets you would not put in a URL.

## How it works

Only URLs that appear to point at the uploads directory (including a custom `UPLOADS` path) are rewritten. Other assets are left unchanged.

Example:

- Local: `http://localhost/wp-content/uploads/2024/01/image.jpg`
- Rewritten: `https://yoursite.com/wp-content/uploads/2024/01/image.jpg`

## Developer hooks

All filters receive the current plugin settings array (except where noted).

### `production_image_redirector_should_redirect`

- **Type**: filter
- **Arguments**: `(bool $allow, string $context, array $settings)`
- **Context** examples: `attachment_url`, `attachment_image_src`, `attachment_image_attributes`, `wp_calculate_image_srcset`, `the_content`
- **Use**: Disable redirection for specific requests (e.g. REST, cron) or contexts.

```php
add_filter(
	'production_image_redirector_should_redirect',
	function ( $allow, $context, $settings ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		return $allow;
	},
	10,
	3
);
```

### `production_image_redirector_redirect_url`

- **Type**: filter
- **Arguments**: `(string $new_url, string $original, array $settings)`
- **Use**: Adjust the final URL (signing, alternate host, stripping auth, etc.).

## Plugin structure

```
production-image-redirector/
├── production-image-redirector.php
├── uninstall.php
├── composer.json
├── phpcs.xml.dist
├── includes/
│   ├── constants.php
│   ├── class-admin.php
│   ├── class-url-redirector.php
│   ├── class-activator.php
│   └── index.php
├── languages/
│   └── index.php
└── README.md
```

## Requirements

- WordPress **5.8** or higher (block widget filter and general compatibility)
- PHP **7.4** or higher

## Contributing / code style

```bash
composer install
composer lint   # runs phpcs per composer.json "lint" script
```

Auto-fix where possible:

```bash
composer lint:fix
```

## Support

1. Production URL is valid (typically `https://…`)
2. Plugin is activated and **Enable Image Redirect** is checked
3. The image path lives under uploads (see “How it works”)
4. Production host is reachable (and Basic Auth credentials are correct if used)

## License

GPL v2 or later — see [License URI](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

## Changelog

### Version 1.1.0

- HTTP Basic Authentication options for protected production sites
- `wp_calculate_image_srcset` support; fix `srcset` rewriting when the attribute appears after `src`
- Block widget support via `widget_block_content`
- Safer quoted `url("...")` values for inline CSS background images
- Filters: `production_image_redirector_should_redirect`, `production_image_redirector_redirect_url`
- `uninstall.php` with multisite option cleanup via `get_sites()`
- Shared constants (`PRODUCTION_IMAGE_REDIRECTOR_*`); plugin headers (`Requires at least`, `Requires PHP`, `License URI`)
- WordPress Coding Standards tooling (Composer + PHPCS); `wp_parse_url` usage; request-level option cache in redirector
- Removed unnecessary `flush_rewrite_rules` on activate/deactivate

### Version 1.0.0

- Initial release: admin settings, attachment and content redirects, `srcset` / background regex handling
