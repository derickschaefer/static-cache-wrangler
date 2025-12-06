# Static Cache Wrangler

> Transform your WordPress site into a fully self-contained static website that works completely offline.

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](LICENSE)

## What It Does

Static Cache Wrangler automatically creates static HTML versions of your WordPress pages as visitors browse your site. It downloads and localizes all assets (CSS, JS, images, fonts) so the exported site works without an internet connection.

**Perfect for:**
- Creating offline documentation
- Archiving WordPress sites
- Generating portable demos
- CDN-free deployments
- Client deliverables
- SEO-ready static exports with sitemaps

## Features

- **Zero-configuration** - Works out of the box
- **Automatic generation** - Creates static files on page visits
- **Asset localization** - Downloads CSS, JS, images, fonts to local directory
- **Clean HTML output** - Removes WordPress-specific meta tags
- **Relative paths** - All links rewritten for portability
- **Modern UI** - Clean, card-based admin interface
- **WP-CLI support** - Full command-line control
- **One-click export** - Download entire static site as ZIP
- **Sitemap generation** - File system-based sitemaps for SEO
- **Cache freshness** - Intelligent staleness detection reduces regeneration by 90%+
- **Developer hooks** - Extensible API for companion plugins
- **Performance profiling** - Optional developer tools for benchmarking
- **Multisite support** - Isolated storage per site with unique namespaces

## What's New in 2.1.1

**Cache Freshness System:**
- Metadata stamps in all generated HTML files tracking generation time and plugin version
- Automatic staleness detection on every page request
- TTL-based expiry with configurable cache lifetime (default 24 hours)
- **90%+ reduction** in unnecessary page regeneration
- Typical overhead: 1-2ms for staleness check, 0ms for fresh files
- Plugin version tracking for automatic regeneration after upgrades
- Configurable via `STCW_CACHE_TTL` constant

**Automatic Sitemap Generation:**
- ZIP exports now automatically generate fresh sitemap before packaging
- No need to manually run `wp scw sitemap` before `wp scw zip`
- Configurable deployment URL via `STCW_SITEMAP_URL` constant
- Seamless workflow: `wp scw zip` includes everything

**Configuration Options:**
```php
// In wp-config.php

// Set cache lifetime (default: 86400 = 24 hours)
define('STCW_CACHE_TTL', 86400);    // 24 hours (default)
define('STCW_CACHE_TTL', 3600);     // 1 hour (aggressive)
define('STCW_CACHE_TTL', 604800);   // 1 week (conservative)
define('STCW_CACHE_TTL', 0);        // Never expire (version check only)

// Set sitemap URL for deployment (default: uses site URL)
define('STCW_SITEMAP_URL', 'https://static.example.com');
define('STCW_SITEMAP_URL', 'https://cdn.mysite.com');
```

**Performance Impact:**
```
Before v2.1.1: Every page request regenerated HTML (~50-500ms)
After v2.1.1:  Only stale pages regenerate (~1-2ms check, skip if fresh)
Result:        90%+ reduction in CPU/memory consumption
```

## What's New in 2.1.0

**Static Sitemap Generation:**

Version 2.1.0 introduces file system-based sitemap generation that creates sitemaps from your actual cached files rather than the WordPress database.

**Why File System-Based?**

Traditional WordPress sitemap plugins (Yoast SEO, Rank Math) query the database dynamically. This works great for live sites, but fails for static exports because there's no PHP or database available.

Static Cache Wrangler scans your actual cached `index.html` files to build the sitemap, ensuring:

- ✅ **Perfect accuracy** - sitemap matches exported content exactly
- ✅ **True portability** - works without WordPress/PHP/database
- ✅ **SEO compliance** - search engines see what users see
- ✅ **Deploy anywhere** - S3, Netlify, GitHub Pages, any static host

**New WP-CLI Commands:**
```bash
wp scw sitemap                                    # Generate sitemap.xml
wp scw sitemap --target-url=https://cdn.example.com  # Specify deployment URL
wp scw sitemap-delete                             # Remove sitemap files
```

**Sitemap Features:**
- Generates sitemaps.org compliant XML sitemap
- Creates XSL stylesheet for browser viewing
- Calculates priorities automatically (homepage = 1.0, deeper pages = 0.4)
- Assigns smart change frequencies (homepage = daily, pages = monthly)
- Includes last modification times from file metadata
- Developer filter hook: `stcw_sitemap_changefreq`
- Multisite compatible with isolated sitemaps per site
- Fast performance: ~50-100ms per 100 cached files

## What's New in 2.0.7

**Major Compatibility Enhancement Release:**

Version 2.0.7 delivers extensive improvements to Kadence Blocks support and significantly enhances compatibility with all Gutenberg block plugins that rely on dynamically printed JavaScript and CSS.

**Full Kadence Blocks Compatibility:**
- Global front-end scripts and CSS now correctly captured
- Complete functionality for JS-dependent components (accordions, buttons, icons, Lottie animations, progress bars, etc.)

**Enhanced Block Suite Support:**
- Reliable detection and export of dynamic assets from Spectra, Stackable, GenerateBlocks, CoBlocks, Otter, and other major block libraries
- Improved dynamic script capture logic for conditionally loaded assets
- Better preservation of interactive behavior (tooltips, animations, scroll effects)

## Screenshots
```
┌─────────────────────────────────────────────────────────┐
│ Generation Status │ Assets  │ Total Size                │
│    ENABLED        │   152   │   4.2 MB                  │
│  23 static files  │ 3 pending                            │
└─────────────────────────────────────────────────────────┘

File System Locations
├── Static Files:  /wp-content/cache/stcw_static/
├── Assets:        /wp-content/cache/stcw_static/assets/
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
wp plugin install static-cache-wrangler --activate
```

### Manual Installation
```bash
cd wp-content/plugins
git clone https://github.com/derickschaefer/static-cache-wrangler.git
wp plugin activate static-cache-wrangler
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
wp scw enable

# Check status
wp scw status

# Process pending assets
wp scw process

# Generate sitemap (NEW in 2.1.0)
wp scw sitemap

# Create ZIP archive (automatically includes sitemap in 2.1.1)
wp scw zip

# Clear all static files
wp scw clear
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
6. **Cache freshness check** → File marked with metadata (timestamp + plugin version)
7. **Future requests** → Skip regeneration if file is still fresh (< 24 hours old)

## Architecture
```
static-cache-wrangler/
├── admin/
│   ├── class-stcw-admin.php           # Settings page controller
│   ├── class-stcw-admin-bar.php       # WordPress admin bar integration
│   ├── css/
│   │   └── admin-style.css            # Admin dashboard styles
│   ├── js/
│   │   ├── admin-script.js            # Admin dashboard JavaScript
│   │   └── stcw-admin-bar-handler.js  # Admin bar handler
│   └── views/
│       └── admin-page.php             # Main settings UI
├── cli/
│   └── class-stcw-cli.php             # WP-CLI command definitions
├── includes/
│   ├── class-stcw-core.php            # Core functionality & hooks
│   ├── class-stcw-generator.php       # HTML generation & output buffering
│   ├── class-stcw-asset-handler.php   # Asset downloading & processing
│   ├── class-stcw-url-helper.php      # URL manipulation utilities
│   ├── class-stcw-sitemap-generator.php # Sitemap generation (v2.1.0)
│   ├── stcw-logger.php                # Debug logging utility
│   └── js/
│       └── auto-process.js            # Background asset processing
├── tools/
│   └── performance-profiler.txt       # Developer profiling guide
└── static-site.php                    # Main plugin file
```

## WP-CLI Commands

### `wp scw enable`
Enable static site generation.

### `wp scw disable`
Disable static site generation.

### `wp scw status`
Display current status and statistics.
```bash
$ wp scw status
Static Generation: Enabled
Static Files: 23
Total Size: 4.2 MB
Pending Assets: 3
Downloaded Assets: 152
Static Directory: /var/www/html/wp-content/cache/stcw_static/
Assets Directory: /var/www/html/wp-content/cache/stcw_static/assets/
```

### `wp scw process`
Process all pending asset downloads immediately.
```bash
$ wp scw process
Processing pending assets...
Found 45 pending assets. Processing...
Downloading assets  100% [========================================] 0:00 / 0:12
Downloaded 45 assets successfully!
```

### `wp scw clear`
Remove all generated static files and assets.
```bash
$ wp scw clear
All static files cleared.
```

### `wp scw zip`
Create a ZIP archive of the complete static site.

**Options:**
- `--output=<path>` - Specify custom output path
```bash
# Default location (automatically includes sitemap in v2.1.1)
$ wp scw zip
Generating fresh sitemap for ZIP export
Sitemap generated successfully: 23 URLs
ZIP created: /wp-content/cache/static-site-2025-12-06-14-30-00.zip (4.2 MB)

# Custom location
$ wp scw zip --output=/tmp/mysite.zip
ZIP created: /tmp/mysite.zip (4.2 MB)
```

### `wp scw sitemap`
Generate sitemap.xml and sitemap.xsl from cached static files.

**Options:**
- `--target-url=<url>` - Specify deployment URL for sitemap
```bash
# Use WordPress site URL (default)
$ wp scw sitemap
Generating sitemap from cached files...
Success: Successfully generated sitemap with 23 URLs

Files created:
  Sitemap: /var/www/html/wp-content/cache/stcw_static/sitemap.xml
  Stylesheet: /var/www/html/wp-content/cache/stcw_static/sitemap.xsl

Next steps:
  1. View sitemap in browser: https://example.com/sitemap.xml
  2. Submit to search engines (if deploying live)
  3. Include in ZIP export: wp scw zip

# Specify deployment URL
$ wp scw sitemap --target-url=https://static.example.com
Using deployment URL: https://static.example.com
Success: Successfully generated sitemap with 23 URLs
```

**What it does:**
- Scans cached `index.html` files to build URL list
- Uses file system as source of truth (not WordPress database)
- Generates sitemaps.org compliant XML sitemap
- Creates XSL stylesheet for browser viewing
- Calculates priorities based on URL depth
- Assigns change frequencies based on URL patterns

**Why file system-based?**  
Unlike WordPress sitemap plugins that query the database, Static Cache Wrangler generates sitemaps from actual cached files. This ensures the sitemap accurately reflects what's in your static export and works without WordPress.

### `wp scw sitemap-delete`
Remove sitemap files from static directory.
```bash
$ wp scw sitemap-delete
Success: Sitemap files deleted.
```

## Configuration

The plugin works with sensible defaults, but you can customize behavior:

### Constants (in `wp-config.php`)

**Cache Freshness (v2.1.1):**
```php
// Set cache lifetime in seconds (default: 86400 = 24 hours)
define('STCW_CACHE_TTL', 86400);    // 24 hours (recommended for production)
define('STCW_CACHE_TTL', 3600);     // 1 hour (good for development)
define('STCW_CACHE_TTL', 604800);   // 1 week (conservative for stable sites)
define('STCW_CACHE_TTL', 0);        // Never expire based on time (version check only)
```

**Sitemap Deployment URL (v2.1.1):**
```php
// Set target URL for sitemap generation (default: uses WordPress site URL)
define('STCW_SITEMAP_URL', 'https://static.example.com');
define('STCW_SITEMAP_URL', 'https://cdn.mysite.com');
```

**Storage Locations:**
```php
// Change static files location
define('STCW_STATIC_DIR', WP_CONTENT_DIR . '/my-static-files/');

// Change assets location
define('STCW_ASSETS_DIR', WP_CONTENT_DIR . '/my-assets/');

// Disable async asset processing (process immediately)
define('STCW_ASYNC_ASSETS', false);
```

### Developer Hooks (v2.0.5+)
```php
// Remove additional WordPress head tags
add_action('stcw_remove_wp_head_tags', function() {
    remove_action('wp_head', 'your_custom_action');
});

// Modify HTML before saving to file
add_filter('stcw_process_static_html', function($html) {
    // Add custom footer, remove tracking scripts, etc.
    return $html;
});

// Exclude specific URLs from generation
add_filter('stcw_should_generate', function($should_generate, $url) {
    if (strpos($url, '/private/') !== false) {
        return false;
    }
    return $should_generate;
}, 10, 2);
```

### Performance Profiling Hooks (v2.0.6+)
```php
// Enable profiling (requires Performance Profiler MU plugin)
define('STCW_PROFILING_ENABLED', true);

// Hook into file save events
add_action('stcw_before_file_save', function($static_file) {
    // Start timer or log file path
    error_log('Saving: ' . $static_file);
});

add_action('stcw_after_file_save', function($success, $static_file) {
    // Log completion
    error_log('Saved: ' . $static_file . ' - Success: ' . ($success ? 'Yes' : 'No'));
}, 10, 2);

// Monitor asset downloads
add_filter('stcw_before_asset_download', function($url) {
    error_log('Downloading: ' . $url);
    return $url;
});

add_filter('stcw_after_asset_download', function($dest, $url) {
    error_log('Downloaded: ' . $url . ' to ' . $dest);
    return $dest;
}, 10, 2);

// Track async batch performance
add_action('stcw_before_asset_batch', function() {
    error_log('Starting asset batch...');
});

add_action('stcw_after_asset_batch', function($processed, $failed) {
    error_log("Batch complete: $processed processed, $failed failed");
}, 10, 2);
```

### Sitemap Customization (v2.1.0+)
```php
// Customize change frequency per URL
add_filter('stcw_sitemap_changefreq', function($freq, $path) {
    if (strpos($path, '/products/') !== false) {
        return 'weekly';
    }
    if (strpos($path, '/legal/') !== false) {
        return 'yearly';
    }
    return $freq;
}, 10, 2);
```

See the [Developer Examples](https://github.com/derickschaefer/static-cache-wrangler/wiki/Developer-Examples) for more hook usage patterns.

## Cache Freshness System

Version 2.1.1 introduces intelligent cache validation that reduces unnecessary page regeneration by over 90% in typical production environments.

### How It Works

Every generated HTML file includes a metadata stamp:
```html
<!-- StaticCacheWrangler: generated=2025-12-04T15:30:00Z; plugin=2.1.1 -->
```

On every page request, the plugin checks if the existing cached file is still fresh. A file is considered **stale** if:

1. **No metadata found** (pre-v2.1.1 file)
2. **Plugin version changed** (metadata shows older version)
3. **Age exceeds TTL** (file older than configured cache lifetime)

Fresh files skip regeneration entirely, returning the existing cached version.

### Configuration Examples

**Development (1-hour TTL):**
```php
define('STCW_CACHE_TTL', 3600);  // See changes within an hour
```

**Staging (6-hour TTL):**
```php
define('STCW_CACHE_TTL', 21600);  // Balance freshness vs. performance
```

**Production (24-hour TTL - default):**
```php
// No configuration needed - 24 hours is sensible default
```

**High-availability failover (1-week TTL):**
```php
define('STCW_CACHE_TTL', 604800);  // Very stable, rarely regenerate
```

**Archive/preservation (never expire):**
```php
define('STCW_CACHE_TTL', 0);  // Only regenerate on plugin upgrades
```

### Performance Characteristics

Typical production behavior over 24-hour cycle:
- **Hours 0-23:** "Cache fresh, skipping regeneration" (0ms overhead)
- **Hour 24:** "TTL exceeded, marking stale" → Regeneration (~50-500ms)
- **After plugin upgrade:** Immediate regeneration on next request
- **Result:** ~90% reduction in unnecessary regeneration

**Real-World Example from Production Logs:**
```
[04-Dec-2025 11:21:57 UTC] TTL exceeded, marking stale: index.html (age: 121938s, ttl: 86400s)
[04-Dec-2025 11:21:57 UTC] Cache stale, regenerating: index.html
[04-Dec-2025 11:21:57 UTC] Successfully saved: index.html

[04-Dec-2025 11:22:29 UTC] Cache fresh, skipping regeneration: index.html
[04-Dec-2025 11:24:11 UTC] Cache fresh, skipping regeneration: index.html
[04-Dec-2025 11:45:06 UTC] Cache fresh, skipping regeneration: index.html
... (50+ cache hits before next regeneration)
```

## Sitemap Generation

Version 2.1.0 introduces file system-based sitemap generation for static exports.

### Key Features

- ✅ Generates from cached files (not database)
- ✅ Includes modification times from file metadata  
- ✅ Calculates priorities automatically (homepage = 1.0, deeper pages = 0.4)
- ✅ Assigns reasonable change frequencies
- ✅ Creates XSL stylesheet for browser viewing
- ✅ Fully portable with static export

### Quick Start
```bash
# Generate sitemap
wp scw sitemap

# View in browser
open https://your-site.com/sitemap.xml
```

The XSL stylesheet transforms the sitemap into a readable HTML table with color-coded priorities and sortable columns.

### Search Engine Submission

After deploying static site with sitemap:

**Google Search Console:**
1. https://search.google.com/search-console
2. Select property → Sitemaps
3. Add: `https://your-site.com/sitemap.xml`
4. Submit

**Bing Webmaster Tools:**
1. https://www.bing.com/webmasters
2. Select site → Sitemaps
3. Submit: `https://your-site.com/sitemap.xml`

**robots.txt reference:**
```
User-agent: *
Allow: /

Sitemap: https://your-site.com/sitemap.xml
```

### Complete Documentation

See [SITEMAP.md](docs/SITEMAP.md) for detailed usage, customization, and examples.

## Performance Profiling

Version 2.0.6 introduces optional performance profiling capabilities through developer hooks. These hooks enable the **Static Cache Wrangler Performance Profiler** MU plugin, which provides detailed benchmarking and analysis via WP-CLI.

**Installation:**
1. Download the profiler from [moderncli.dev/code/static-cache-wrangler/performance-profiler/](https://moderncli.dev/code/static-cache-wrangler/performance-profiler/)
2. Place in `/wp-content/mu-plugins/stcw-performance-profiler.php`
3. Enable in `wp-config.php`: `define('STCW_PROFILING_ENABLED', true);`

**WP-CLI Commands:**
```bash
# View performance statistics
wp stcw profiler stats

# View recent profiling logs
wp stcw profiler logs

# Export profiling data to CSV
wp stcw profiler export --output=/tmp/stcw-data.csv

# Clear profiling data
wp stcw profiler clear
```

**What Gets Profiled:**
- Page generation time and memory usage
- File I/O operations
- Asset download performance
- Async batch processing metrics

See `/tools/performance-profiler.txt` for complete documentation.

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
3. Manually copy them to `/wp-content/cache/stcw_static/assets/`
4. If needed, update the HTML in your static files to reference the correct paths

Example:
```bash
# Copy dynamically generated favicons
cp /wp-content/uploads/fbrfg/* /wp-content/cache/stcw_static/assets/

# Re-create ZIP with updated favicons
wp scw zip
```

**Simple Solution:** For maximum compatibility, place a standard `favicon.ico` file in your WordPress root directory. Browsers will request it automatically even without a `<link>` tag.

### What Doesn't Get Downloaded

- ❌ External CDN assets (Google Fonts, jQuery CDN, etc.) - Links preserved as-is
- ❌ Third-party embeds (YouTube®, Twitter®, etc.) - Require internet connection
- ❌ Dynamic content loaded via AJAX/REST API
- ❌ Assets from different domains (cross-origin)

## Use Cases

### 1. Offline Documentation
```bash
wp scw enable
# Browse all documentation pages
wp scw process
wp scw sitemap
wp scw zip --output=/docs/offline-docs.zip
```

### 2. Client Deliverables

Export a complete static version for clients who don't need WordPress:
```bash
wp scw enable
# Browse site
wp scw sitemap
wp scw zip
# Deliver ZIP file
```

### 3. Archive Before Redesign
```bash
wp scw enable
# Crawl entire site with wget or similar
wp scw process
wp scw sitemap
wp scw zip --output=/backups/pre-redesign.zip
wp scw disable
```

### 4. CDN-Free Deployment

Deploy the static site to any web server without WordPress dependencies:
```bash
wp scw enable
wp scw process
wp scw sitemap
wp scw zip
# Extract and upload to Amazon S3®, Netlify®, GitHub Pages®, etc.
```

### 5. SEO-Ready Static Exports

Generate complete static sites with proper sitemap for search engines:
```bash
wp scw enable
# Browse site or crawl with tool
wp scw process
wp scw sitemap          # Generate sitemap
wp scw zip
# Deploy to CDN with sitemap.xml included
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
wp scw status
```

## Troubleshooting

### Static files not generating

**Check if generation is enabled:**
```bash
wp scw status
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
wp scw process
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
sudo systemctl restart php8.3-fpm nginx
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
cp /wp-content/uploads/fbrfg/* /wp-content/cache/stcw_static/assets/
```

**Use a simple favicon.ico:**
```bash
# Place in WordPress root - works without <link> tag
cp favicon.ico /var/www/html/favicon.ico
```

### Cache not expiring properly

**Check TTL configuration:**
```bash
# View current configuration
wp eval 'echo STCW_CACHE_TTL;'
```

**Force regeneration:**
```bash
# Clear all files and regenerate
wp scw clear
wp scw enable
# Browse site to regenerate
```

**Check debug logs:**
```bash
# Look for staleness check messages
tail -f /var/www/html/wp-content/debug.log | grep "Cache fresh\|Cache stale"
```

## Performance

- **Generation overhead:** ~50-100ms per page (buffering + file write)
- **Cache staleness check:** ~1-2ms per request (reads first 512 bytes only)
- **Cache hit behavior:** 0ms overhead (skips regeneration entirely)
- **Asset processing:** Background queue, no user-facing delay
- **Memory usage:** ~2MB additional per request
- **Disk I/O:** Sequential writes, minimal impact
- **Typical cache hit rate:** 90%+ in production (v2.1.1+)

## Disk Space Considerations

The plugin stores static files in `wp-content/cache/stcw_static/`. Disk usage varies based on your site:

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
- WP-CLI: `wp scw status` (detailed breakdown)
- Your hosting control panel

**To reduce disk usage:**
```bash
# Clear all static files
wp scw clear

# Or manually delete old files
rm -rf /var/www/html/wp-content/cache/stcw_static/*
```

**Exclude large pages from generation:**
```php
add_filter('stcw_should_generate', function($should, $url) {
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
   0 2 * * * /usr/bin/wp scw process --path=/var/www/html
```

2. **Disable on high-traffic pages:**
```php
   add_filter('stcw_should_generate', function($should, $url) {
       return !is_front_page() && $should;
   }, 10, 2);
```

3. **Clear old static files regularly:**
```bash
   # Weekly cleanup
   0 3 * * 0 /usr/bin/wp scw clear --path=/var/www/html
```

**Best practices:**
- Optimize images before generating static site (use image optimization plugins)
- Clear old static files before regenerating
- Monitor disk usage regularly
- Consider hosting environment with adequate disk space for your needs

**Video and Audio Files:**  
The plugin intentionally does not download video (MP4, WebM) or audio (MP3, WAV) files. These should remain on your WordPress server or external hosting (YouTube, CDN). The static HTML will link to these resources.

## Multisite Support

Version 2.0.6 includes full multisite support with isolated storage per site:

**Storage Structure:**
```
wp-content/cache/stcw_static/
├── site-1/        # Main site (blog_id 1)
│   ├── index.html
│   ├── sitemap.xml
│   └── assets/
├── site-2/        # Blog ID 2
│   ├── index.html
│   ├── sitemap.xml
│   └── assets/
└── site-3/        # Blog ID 3
    ├── index.html
    ├── sitemap.xml
    └── assets/
```

**Each site maintains:**
- Separate static file directories
- Independent asset storage
- Isolated plugin options
- Site-specific WP-CLI commands
- Independent sitemaps

**Using WP-CLI with Multisite:**
```bash
# Operate on specific site
wp scw enable --url=site2.example.com

# Or use --url parameter
wp scw status --url=site2.example.com

# Generate sitemap for specific site
wp scw sitemap --url=site2.example.com

# Process all sites (loop through)
for site in $(wp site list --field=url); do
    wp scw enable --url=$site
done
```

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup
```bash
git clone https://github.com/derickschaefer/static-cache-wrangler.git
cd static-cache-wrangler
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

## Trademark Recognition and Legal Disclaimer

All product names, logos, and brands referenced in this plugin and its documentation are property of their respective owners.

NGINX® is a registered trademark of F5, Inc.  
Amazon S3® and Route 53™ are trademarks of Amazon Technologies, Inc.  
Netlify® is a registered trademark of Netlify, Inc.  
Cloudflare® and Cloudflare Pages™ are trademarks of Cloudflare, Inc.  
WP-CLI® and Gutenberg® are trademarks of the WordPress Foundation.  
Elementor® is a registered trademark of Elementor Ltd.  
Divi® is a registered trademark of Elegant Themes, Inc.  
GitHub® is a registered trademark of GitHub, Inc.  
YouTube® is a registered trademark of Google LLC.  
Twitter® is a registered trademark of X Corp.
ModernCLI.Dev is owned by Derick Schaefer

This plugin has not been tested with any of the services, platforms, software projects, nor their respective owners.
These names and services are referenced solely as examples of where static cache files might be repurposed, used, uploaded, stored, or transmitted.

This plugin is an independent open-source project and is **not endorsed by, affiliated with, or sponsored by** any of the companies or open-source projects mentioned herein.

## Author

**Derick Schaefer**

- GitHub: [@derickschaefer](https://github.com/derickschaefer)
- Website: [ModernCLI.dev](https://moderncli.dev)

## Support

- **Issues:** [GitHub Issues](https://github.com/derickschaefer/static-cache-wrangler/issues)
- **Documentation:** [GitHub Wiki](https://github.com/derickschaefer/static-cache-wrangler/wiki)
- **Discussions:** [GitHub Discussions](https://github.com/derickschaefer/static-cache-wrangler/discussions)
- **Sitemap Documentation:** [SITEMAP.md](docs/SITEMAP.md)

## Roadmap

- [ ] Automatic sitemap crawling
- [ ] Multi-language support
- [ ] Incremental generation (only changed pages)
- [ ] URL include/exclude patterns with wildcards
- [ ] Built-in search functionality for static sites
- [ ] Companion plugin marketplace
- [ ] Enhanced performance profiling dashboard
- [x] Static sitemap generation (✅ v2.1.0)
- [x] Cache freshness system (✅ v2.1.1)
- [ ] GUI interface for sitemap management (planned v2.2.0)
- [ ] Sitemap index support for 50,000+ URLs (planned v2.3.0)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for complete version history.

### Recent Releases

**[2.1.1] - 2025-12-04**
- Cache freshness system with metadata stamps
- Automatic staleness detection (90%+ reduction in regeneration)
- TTL-based expiry configuration
- Automatic sitemap generation in ZIP exports
- STCW_SITEMAP_URL constant for deployment URLs

**[2.1.0] - 2025-12-01**
- Static sitemap generation from cached files
- File system-based approach (not database)
- XSL stylesheet for browser viewing
- WP-CLI commands: `sitemap` and `sitemap-delete`
- Developer hook: `stcw_sitemap_changefreq`

**[2.0.7] - 2025-11-07**
- Full Kadence Blocks compatibility
- Enhanced Gutenberg block suite support
- Improved dynamic script capture
- Better interactive component preservation

**[2.0.6] - 2025-11-11**
- WordPress.org compliance (prefixed variables)
- Performance profiling hooks
- Developer features for companion plugins

**[2.0.5] - 2025-10-25**
- Enhanced static HTML output
- WordPress meta tag removal
- Developer hooks for extensibility
