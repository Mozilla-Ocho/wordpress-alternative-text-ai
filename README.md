# Smart Alt Text WordPress Plugin

Automatically generate alt text for your WordPress images using X.AI's (formerly Twitter) image understanding API.

## Features

- 🤖 Automatic alt text generation using X.AI
- 🔒 Secure API key storage with encryption
- 🖼️ Support for JPEG and PNG images
- 🔄 Bulk processing of existing images
- ⚡ Auto-generation for new uploads
- 📝 Customizable prefix and suffix text
- 🎯 Option to update image title, caption, and description

## Installation

### Production Use

1. Download the latest release zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the zip file and click "Install Now"
4. Click "Activate" to enable the plugin

### Development Setup

1. Clone the repository:
```bash
git clone https://github.com/yourusername/smart-alt-text.git
cd smart-alt-text
```

2. Install dependencies (if using Composer):
```bash
composer install
```

3. Create a zip file for testing:
```bash
zip -r smart-alt-text.zip . -x ".*" -x "__MACOSX" -x "*.git*"
```

## Configuration

### Getting an X.AI API Key

1. Visit [X.AI's platform](https://x.ai)
2. Sign up or log in to your account
3. Go to API section
4. Generate a new API key
5. Copy the API key for use in the plugin

### Plugin Settings

1. Go to WordPress Admin → Smart Alt Text → Settings

2. API Settings:
   - Enter your X.AI API key
   - The key will be securely encrypted in the database

3. Generation Settings:
   - **Auto Generation**: Enable to automatically generate alt text for new image uploads
   - **Update Fields**: Choose which fields to update:
     - Image title
     - Image caption
     - Image description
   - **Text Modifications**:
     - Prefix: Text to add before the generated alt text
     - Suffix: Text to add after the generated alt text

## Usage

### Manual Alt Text Generation

1. Go to Media Library
2. Click on an image
3. Click the "Analyze" button to generate alt text

### Bulk Processing

1. Go to Smart Alt Text → Images
2. Select images you want to process
3. Click "Generate Alt Text" to process multiple images

### Supported Image Types

- JPEG/JPG
- PNG

Note: Other formats like AVIF, WebP, GIF are not currently supported by the X.AI API.

## Development Notes

### File Structure

```
smart-alt-text/
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
├── smart-alt-text.php
└── README.md
```

### Building for Release

1. Update version number in:
   - `smart-alt-text.php`
   - `readme.txt` (if exists)

2. Create release zip:
```bash
zip -r smart-alt-text.zip . -x ".*" -x "__MACOSX" -x "*.git*" -x "node_modules/*" -x "tests/*"
```

## Security

- API keys are encrypted using AES-256-CBC before storage
- Nonce verification for all AJAX requests
- Capability checks for administrative actions
- Input sanitization and validation

## Support

For issues and feature requests, please [create an issue](https://github.com/yourusername/smart-alt-text/issues) on GitHub.

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details. 