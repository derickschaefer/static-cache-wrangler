# Static Cache Generator

> Transform your WordPress site into a fully self-contained static website that works completely offline.

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](LICENSE)

## What It Does

Static Cache Generator automatically creates static HTML versions of your WordPress pages as visitors browse your site. It downloads and localizes all assets (CSS, JS, images, fonts) so the exported site works without an internet connection.

**Perfect for:**
- Creating offline documentation
- Archiving WordPress sites
- Generating portable demos
- CDN-free deployments
- Client deliverables

## Features

- **Zero-configuration** - Works out of the box
- **Automatic generation** - Creates static files on page visits
- **Asset localization** - Downloads CSS, JS, images, fonts to local directory
- **Relative paths** - All links rewritten for portability
- **Modern UI** - Clean, card-based admin interface
- **WP-CLI support** - Full command-line control
- **One-click export** - Download entire static site as ZIP

## Screenshots

```
┌─────────────────────────────────────────────────────────┐
│ Generation Status │ Assets  │ Total Size                │
│    ENABLED        │   152   │   4.2 MB                  │
│  23 static files  │ 3 pending                            │
└─────────────────────────────────────────────────────────┘

File System Locations
├── Static Files:  /wp-content/cache/_static/
├── Assets:        /wp-content/cache/_static/assets/
├── Writable:      ✓ Yes
└── Size:          4.2 MB
```

## Installation

### Via WordPress Admin

1. Download the plugin ZIP
2. Go to Plugins > Add New > Upload Plugin
3. Upload and activate

### Via WP-CLI

```bash
wp plugin install static-cache-generator --activate
```

### Manual Installation

```bash
cd wp-content/plugins
git clone https://github.com/yourusername/static-cache-generator.git
wp plugin activate static-cache-generator
```

## Quick Start

### GUI Method

1. Navigate to **Settings > Static Cache** in WordPress admin
2. Click **Enable Generation**
3. Browse your site normally - static files are created automatically
4. Click **Download ZIP** when ready

### CLI Method

```bash
# Enable generation
wp scg enable

# Check status
wp scg status

# Process pending assets
wp scg process

# Create ZIP archive
wp scg zip

# Clear all static files
wp scg clear
```

## How It Works

```
┌─────────────┐      ┌──────────────┐      ┌─────────────┐
│   Visitor   │─────>│  WordPress   │─────>│ Static File │
│ Views Page  │      │  Processes   │      │   Saved     │
└─────────────┘      └──────────────┘      └─────────────┘
                             │
                             ▼
                     ┌──────────────┐
                     │ Queue Assets │
                     │ CSS/JS/Images│
                     └──────────────┘
                             │
                             ▼
                     ┌──────────────┐
                     │   Download   │
                     │  & Localize  │
                     └──────────────┘
```

1. **Visitor loads page** → WordPress generates HTML
2. **Plugin captures output** → Rewrites all asset URLs to relative paths
3. **Assets queued** → CSS, JS, images, fonts added to download queue
4. **Background processing** → Assets downloaded and localized
5. **Static file saved** → Complete, portable HTML file created

## Architecture

```
static-cache-generator/
├── admin/
│   ├── class-scg-admin.php      # Settings page controller
│   ├── class-scg-admin-bar.php  # WordPress admin bar integration
│   └── views/
│       └── admin-page.php       # Main settings UI
├── cli/
│   └── class-scg-cli.php        # WP-CLI command definitions
├── includes/
│   ├── class-scg-core.php       # Core functionality & hooks
│   ├── class-scg-generator.php  # HTML generation & output buffering
│   ├── class-scg-asset-handler.php  # Asset downloading & processing
│   └── class-scg-url-helper.php # URL manipulation utilities
└── static-site.php              # Main plugin file
```

## WP-CLI Commands

### `wp scg enable`
Enable static site generation.

### `wp scg disable`
Disable static site generation.

### `wp scg status`
Display current status and statistics.

```bash
$ wp scg status
Static Generation: Enabled
Static Files: 23
Total Size: 4.2 MB
Pending Assets: 3
Downloaded Assets: 152
Static Directory: /var/www/html/wp-content/cache/_static/
Assets Directory: /var/www/html/wp-content/cache/_static/assets/
```

### `wp scg process`
Process all pending asset downloads immediately.

```bash
$ wp scg process
Processing pending assets...
Found 45 pending assets. Processing...
Downloading assets  100% [========================================] 0:00 / 0:12
Downloaded 45 assets successfully!
```

### `wp scg clear`
Remove all generated static files and assets.

```bash
$ wp scg clear
All static files cleared.
```

### `wp scg zip`
Create a ZIP archive of the complete static site.

**Options:**
- `--output=<path>` - Specify custom output path

```bash
# Default location
$ wp scg zip
ZIP created: /wp-content/cache/static-site-2025-01-15-14-30-00.zip (4.2 MB)

# Custom location
$ wp scg zip --output=/tmp/mysite.zip
ZIP created: /tmp/mysite.zip (4.2 MB)
```

## Configuration

The plugin works with sensible defaults, but you can customize behavior:

### Constants (in `wp-config.php`)

```php
// Change static files location
define('SCG_STATIC_DIR', WP_CONTENT_DIR . '/my-static-files/');

// Change assets location
define('SCG_ASSETS_DIR', WP_CONTENT_DIR . '/my-assets/');

// Disable async asset processing (process immediately)
define('SCG_ASYNC_ASSETS', false);
```
## Asset Handling

### What Gets Downloaded

The plugin automatically downloads and localizes:

- ✅ **CSS files** - Stylesheets with nested `url()` references processed
- ✅ **JavaScript files** - Scripts with hardcoded asset URLs rewritten
- ✅ **Images** - All formats (JPG, PNG, GIF, SVG, WebP, AVIF)
- ✅ **Fonts** - Web fonts (WOFF, WOFF2, TTF, EOT)
- ✅ **Standard favicons** - Referenced in `<link>` tags
- ✅ **Responsive images** - srcset attributes processed
- ✅ **Background images** - From inline styles

### Favicon Handling

The plugin automatically downloads and localizes standard favicons referenced in `<link>` tags.

**Supported:**
- Standard favicon links: `<link rel="icon" href="/favicon.ico">`
- PNG/SVG icons: `<link rel="icon" href="/icon.png">`
- Apple touch icons: `<link rel="apple-touch-icon" href="/apple-icon.png">`
- Shortcut icons: `<link rel="shortcut icon" href="/favicon.ico">`

**Not Automatically Supported:**
- Dynamically generated favicons (via plugins like RealFaviconGenerator)
- Progressive Web App manifests (`manifest.json` with icon arrays)
- Favicon sets generated at runtime

**Manual Fix for Dynamic/Complex Favicons:**

If you use a plugin that generates favicons dynamically:

1. Generate your static site normally
2. Locate your favicon files (usually in `/wp-content/uploads/` or theme directory)
3. Manually copy them to `/wp-content/cache/_static/assets/`
4. If needed, update the HTML in your static files to reference the correct paths

Example:
```bash
# Copy dynamically generated favicons
cp /wp-content/uploads/fbrfg/* /wp-content/cache/_static/assets/

# Re-create ZIP with updated favicons
wp scg zip
```

**Simple Solution:** For maximum compatibility, place a standard `favicon.ico` file in your WordPress root directory. Browsers will request it automatically even without a `<link>` tag.

### What Doesn't Get Downloaded

- ❌ External CDN assets (Google Fonts, jQuery CDN, etc.) - Links preserved as-is
- ❌ Third-party embeds (YouTube, Twitter, etc.) - Require internet connection
- ❌ Dynamic content loaded via AJAX/REST API
- ❌ Assets from different domains (cross-origin)


### Filters

```php
// Exclude specific URLs from generation
add_filter('scg_should_generate', function($should_generate, $url) {
    if (strpos($url, '/private/') !== false) {
        return false;
    }
    return $should_generate;
}, 10, 2);

// Modify HTML before saving
add_filter('scg_before_save_html', function($html) {
    // Add custom footer
    return str_replace('</body>', '<footer>Generated: ' . date('Y-m-d') . '</footer></body>', $html);
});
```

## Use Cases

### 1. Offline Documentation

```bash
wp scg enable
# Browse all documentation pages
wp scg process
wp scg zip --output=/docs/offline-docs.zip
```

### 2. Client Deliverables

Export a complete static version for clients who don't need WordPress:

```bash
wp scg enable
# Browse site
wp scg zip
# Deliver ZIP file
```

### 3. Archive Before Redesign

```bash
wp scg enable
# Crawl entire site with wget or similar
wp scg process
wp scg zip --output=/backups/pre-redesign.zip
wp scg disable
```

### 4. CDN-Free Deployment

Deploy the static site to any web server without WordPress dependencies:

```bash
wp scg enable
wp scg process
wp scg zip
# Extract and upload to S3, Netlify, GitHub Pages, etc.
```

## Server Requirements

- **PHP:** 7.4 or higher
- **WordPress:** 5.0 or higher
- **PHP Extensions:** 
  - `ZipArchive` (for ZIP export)
  - `curl` or `allow_url_fopen` (for asset downloads)
- **Disk Space:** Varies based on site size (static files = ~1.5x site size)
- **Permissions:** Write access to `wp-content/cache/`

### Ubuntu/Nginx Setup

```bash
# Install PHP ZIP extension
sudo apt-get install php-zip

# Set proper permissions
sudo chown -R www-data:www-data /var/www/html/wp-content/cache
sudo chmod -R 755 /var/www/html/wp-content/cache

# Verify
wp scg status
```

## Troubleshooting

### Static files not generating

**Check if generation is enabled:**
```bash
wp scg status
```

**Verify directory permissions:**
```bash
ls -la /var/www/html/wp-content/cache/
```

**Enable WordPress debugging:**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Assets not downloading

**Process manually:**
```bash
wp scg process
```

**Check error logs:**
```bash
tail -f /var/www/html/wp-content/debug.log
```

**Verify external URL access:**
```bash
curl -I https://your-site.com/wp-content/themes/your-theme/style.css
```

### ZIP creation fails

**Check ZipArchive availability:**
```bash
php -m | grep zip
```

**Install if missing:**
```bash
sudo apt-get install php-zip
sudo systemctl restart php7.4-fpm nginx
```

### Favicons missing or broken

**Check if favicon is in HTML:**
```bash
# View page source and look for <link rel="icon">
curl https://your-site.com | grep favicon
```

**Manually copy favicon files:**
```bash
# If using a favicon generator plugin
cp /wp-content/uploads/fbrfg/* /wp-content/cache/_static/assets/
```

**Use a simple favicon.ico:**
```bash
# Place in WordPress root - works without <link> tag
cp favicon.ico /var/www/html/favicon.ico
```

## Performance

- **Generation overhead:** ~50-100ms per page (buffering + file write)
- **Asset processing:** Background queue, no user-facing delay
- **Memory usage:** ~2MB additional per request
- **Disk I/O:** Sequential writes, minimal impact

## Disk Space Considerations

The plugin stores static files in `wp-content/cache/_static/`. Disk usage varies based on your site:

**Typical Disk Usage:**
- **Small site** (10-50 pages): 50-200 MB
- **Medium site** (100-500 pages): 200 MB - 1 GB  
- **Large site** (1000+ pages): 1-5 GB
- **Very large site** (5000+ pages): 5-20 GB

**What Uses Space:**
- HTML files: Minimal (typically 10-50 KB per page)
- CSS/JS files: 100 KB - 5 MB total
- Images: Largest contributor (depends on image optimization)
- Fonts: 100 KB - 2 MB per font family

**The plugin does not enforce disk limits** - disk space management is handled by your hosting environment. 

**Monitor disk usage through:**
- WordPress admin dashboard: **Settings > Static Cache** (shows total size)
- WP-CLI: `wp scg status` (detailed breakdown)
- Your hosting control panel

**To reduce disk usage:**
```bash
# Clear all static files
wp scg clear

# Or manually delete old files
rm -rf /var/www/html/wp-content/cache/_static/*
```

**Exclude large pages from generation:**
```php
add_filter('scg_should_generate', function($should, $url) {
    // Don't generate static files for media-heavy pages
    if (strpos($url, '/gallery/') !== false) {
        return false;
    }
    return $should;
}, 10, 2);
```

### Optimization Tips

1. **Process assets during off-peak hours:**
   ```bash
   # Add to crontab
   0 2 * * * /usr/bin/wp scg process --path=/var/www/html
   ```

2. **Disable on high-traffic pages:**
   ```php
   add_filter('scg_should_generate', function($should, $url) {
       return !is_front_page() && $should;
   }, 10, 2);
   ```

3. **Clear old static files regularly:**
   ```bash
   # Weekly cleanup
   0 3 * * 0 /usr/bin/wp scg clear --path=/var/www/html
   ```
**Best practices:**
- Optimize images before generating static site (use image optimization plugins)
- Clear old static files before regenerating
- Monitor disk usage regularly
- Consider hosting environment with adequate disk space for your needs

**Video and Audio Files:**  
The plugin intentionally does not download video (MP4, WebM) or audio (MP3, WAV) files. These should remain on your WordPress server or external hosting (YouTube, Vimeo, CDN). The static HTML will link to these resources.


## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
git clone https://github.com/yourusername/static-cache-generator.git
cd static-cache-generator
composer install  # If you add Composer dependencies later
```

### Code Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Comment all classes and methods
- Use type hints where possible
- Write descriptive commit messages

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2025 Derick Schaefer

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## Author

**Derick Schaefer**

- GitHub: [@derickschaefer](https://github.com/derickschaefer)
- Website: [ModernCLI.dev](https://moderncli.dev)

## Support

- **Issues:** [GitHub Issues](https://github.com/yourusername/static-cache-generator/issues)
- **Documentation:** [Wiki](https://github.com/yourusername/static-cache-generator/wiki)
- **Discussions:** [GitHub Discussions](https://github.com/yourusername/static-cache-generator/discussions)

## Roadmap

- [ ] Automatic sitemap crawling
- [ ] Multi-language support
- [ ] Incremental generation (only changed pages)
- [ ] URL include and exclude support

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

