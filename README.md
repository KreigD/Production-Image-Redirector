# Production Image Redirector

A simple WordPress plugin that redirects all image URLs on your current site to point to a production site URL. This is particularly useful for local/test environments where you want to use production images without downloading the entire uploads folder.

## Features

- **Easy Configuration**: Simple admin interface to set your production site URL
- **Toggle On/Off**: Enable or disable image redirection with a single checkbox
- **Comprehensive Coverage**: Redirects images from:
  - WordPress attachment URLs
  - Post content images
  - Widget images
  - Theme and plugin images
  - Background images in CSS
  - Responsive images (srcset)
- **Smart URL Handling**: Automatically handles relative URLs, absolute URLs, and local URLs
- **Well Organized Code**: Clean, maintainable code structure with separated concerns

## Installation

1. Upload the `production-image-redirector` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Image Redirector to configure the plugin

## Configuration

1. Navigate to **Settings > Image Redirector** in your WordPress admin
2. Enter your production site URL (e.g., `https://yoursite.com`)
3. Check the "Enable Image Redirect" checkbox
4. Click "Save Changes"

## How It Works

The plugin intercepts various WordPress functions and filters that handle image URLs and redirects them to your production site. For example:

- Local URL: `http://localhost/wp-content/uploads/2024/01/image.jpg`
- Becomes: `https://yoursite.com/wp-content/uploads/2024/01/image.jpg`

## Supported Image Types

- **WordPress Attachments**: Images uploaded through the media library
- **Content Images**: Images embedded in posts and pages
- **Widget Images**: Images used in widgets
- **Theme Images**: Images used by your theme
- **Background Images**: CSS background images
- **Responsive Images**: Images with srcset attributes for different screen sizes

## Use Cases

- **Local Development**: Use production images while developing locally
- **Staging Sites**: Test sites that need to show production images
- **Demo Sites**: Sites that need to display production content
- **Performance**: Avoid downloading large uploads folders

## Example

If your production site is `https://example.com` and you have an image at `/wp-content/uploads/2024/01/hero-image.jpg`, the plugin will redirect it to `https://example.com/wp-content/uploads/2024/01/hero-image.jpg`.

## Plugin Structure

The plugin is organized into a clean, maintainable structure:

```
production-image-redirector/
├── production-image-redirector.php    # Main plugin file
├── includes/
│   ├── class-admin.php                # Admin interface and settings
│   ├── class-url-redirector.php       # URL redirection logic
│   ├── class-activator.php            # Activation/deactivation hooks
│   └── index.php                      # Security file
├── languages/                         # Translation files
│   └── index.php                      # Security file
└── README.md                          # This file
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Support

This plugin is designed to be simple and lightweight. If you encounter any issues, please check:

1. That your production URL is correctly formatted (include `https://`)
2. That the plugin is activated
3. That the "Enable Image Redirect" checkbox is checked
4. That your production site is accessible

## License

GPL v2 or later

## Changelog

### Version 1.0.0
- Initial release
- Basic image URL redirection functionality
- Admin settings page
- Support for various image types and contexts
- Organized code structure with separated concerns
