=== WordPress Alternative Text AI ===
Contributors: Mozilla-Ocho
Tags: accessibility, images, alt text, ai, seo
Requires at least: 5.0
Tested up to: 6.7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that uses X.AI's vision API to automatically generate descriptive alt text for images.

== Description ==

WordPress Alternative Text AI helps improve your website's accessibility by automatically generating descriptive alt text for your images using X.AI's advanced vision API.

== Features ==

* Automatic alt text generation for new image uploads
* Bulk processing for existing images
* Manual alt text editing with auto-save
* Support for image title, caption, and description updates
* Usage statistics and tracking
* Secure API key management

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wordpress-alternative-text-ai`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure your X.AI API key in the plugin settings

== Usage ==

### Manual Alt Text Generation

1. Go to Media Library
2. Click on an image
3. Click the "Analyze" button to generate alt text

### Bulk Processing

1. Go to Alternative Text AI → Images
2. Select images you want to process
3. Click "Generate Alt Text" to process multiple images

### Supported Image Types

- JPEG/JPG
- PNG

Note: Other formats like AVIF, WebP, GIF are not currently supported by the X.AI API.

== Development ==

### File Structure

```
wordpress-alternative-text-ai/
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-admin.php
│   ├── class-image-analyzer.php
│   ├── class-activator.php
│   └── class-deactivator.php
├── templates/
│   ├── settings-page.php
│   ├── bulk-page.php
│   └── stats-page.php
├── wordpress-alternative-text-ai.php
└── README.md
```

### Building for Release

1. Update version number in:
   - `wordpress-alternative-text-ai.php`
   - `readme.md`

2. Create release zip:
```bash
zip -r wordpress-alternative-text-ai.zip . -x ".*" -x "__MACOSX" -x "*.git*" -x "node_modules/*" -x "tests/*"
```

== Security ==

- API keys are stored securely in WordPress database
- All API requests are made server-side
- Input is sanitized and validated
- AJAX requests are nonce-protected
- Database queries are prepared statements

== Support ==

For issues and feature requests, please create an issue on GitHub.

== License ==

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.
