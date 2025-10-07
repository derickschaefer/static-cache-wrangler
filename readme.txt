=== Static Cache Generator ===
Contributors: derickschaefer
Tags: static site, cache, offline, export, generator
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform your WordPress site into a fully self-contained static website that works completely offline.

== Description ==

Static Cache Generator automatically creates static HTML versions of your WordPress pages as visitors browse your site. It downloads and localizes all assets (CSS, JS, images, fonts) so the exported site works without an internet connection.

= Perfect for: =

* Creating offline documentation
* Archiving WordPress sites
* Generating portable demos
* CDN-free deployments
* Client deliverables

= Features =

* **Zero-configuration** - Works out of the box
* **Automatic generation** - Creates static files on page visits
* **Asset localization** - Downloads CSS, JS, images, fonts to local directory
* **Relative paths** - All links rewritten for portability
* **Modern UI** - Clean, card-based admin interface
* **WP-CLI support** - Full command-line control
* **One-click export** - Download entire static site as ZIP

= How It Works =

1. Visitor loads page - WordPress generates HTML
2. Plugin captures output - Rewrites all asset URLs to relative paths
3. Assets queued - CSS, JS, images, fonts added to download queue
4. Background processing - Assets downloaded and localized
5. Static file saved - Complete, portable HTML file created

= WP-CLI Commands =

* `wp scg enable` - Enable static site generation
* `wp scg disable` - Disable static site generation
* `wp scg status` - Display current status and statistics
* `wp scg process` - Process all pending asset downloads immediately
* `wp scg clear` - Remove all generated static files and assets
* `wp scg zip` - Create a ZIP archive of the complete static site

= Asset Handling =

The plugin automatically downloads and localizes:

* CSS files - Stylesheets with nested url() references processed
* JavaScript files - Scripts with hardcoded asset URLs rewritten
* Images - All formats (JPG, PNG, GIF, SVG, WebP, AVIF)
* Fonts - Web fonts (WOFF, WOFF2, TTF, EOT)
* Standard favicons - Referenced in link tags
* Responsive images - srcset attributes processed
* Background images - From inline styles

External CDN assets, third-party embeds, and dynamic AJAX content remain linked to their original sources.

= Disk Space Considerations =

The plugin stores static files in `wp-content/cache/_static/`. Typical disk usage:

* Small site (10-50 pages): 50-200 MB
* Medium site (100-500 pages): 200 MB - 1 GB
* Large site (1000+ pages): 1-5 GB
* Very large site (5000+ pages): 5-20 GB

The plugin does not enforce disk limits - disk space management is handled by your hosting environment. Monitor usage through the admin dashboard or WP-CLI.

**Video and Audio Files:** The plugin intentionally does not download video (MP4, WebM) or audio (MP3, WAV) files to conserve disk space. These remain on your WordPress server or external hosting.

== Installation ==

= Via WordPress Admin =

1. Download the plugin ZIP
2. Go to Plugins > Add New > Upload Plugin
3. Upload and activate
4. Navigate to Settings > Static Cache
5. Click "Enable Generation"

= Via WP-CLI =

`wp plugin install static-cache-generator --activate`

= Manual Installation =

1. Upload the plugin files to `/wp-content/plugins/static-cache-generator/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Settings > Static Cache to configure

== Frequently Asked Questions ==

= Does this work with any WordPress theme? =

Yes! The plugin works with any properly-coded WordPress theme. It captures the final HTML output regardless of the theme.

= Will my static site work offline? =

Yes, completely! All CSS, JavaScript, images, and fonts are downloaded locally. The only exceptions are external CDN resources and third-party embeds (YouTube, Twitter, etc.) which require an internet connection.

= How do I deploy the static site? =

After generating your static files, click "Download ZIP" in the admin dashboard. Extract the ZIP and upload the contents to any web server, S3 bucket, GitHub Pages, Netlify, or similar hosting.

= Can I use this for large sites? =

Yes, but monitor your disk space. Sites with thousands of pages and many images can use several gigabytes. The admin dashboard shows current disk usage.

= Does this replace caching plugins? =

No, this is not a performance caching plugin. It creates a completely separate static version of your site for offline use, archival, or deployment to static hosting.

= What about favicons? =

Standard favicons referenced in `<link>` tags are automatically downloaded. Dynamically generated favicons (from plugins) may need to be manually copied to the assets directory. See the documentation for details.

= How do I exclude certain pages? =

Use the `scg_should_generate` filter:

`add_filter('scg_should_generate', function($should, $url) {
    if (strpos($url, '/private/') !== false) {
        return false;
    }
    return $should;
}, 10, 2);`

= Does this work on multisite? =

The plugin is compatible with multisite installations. Each site in the network will have its own static files directory.

== Screenshots ==

1. Main admin dashboard showing generation status, assets, and disk usage
2. Real-time asset processing with progress bar
3. Admin bar integration for quick access from any page
4. Generated static site running completely offline

== Changelog ==

= 2.0.0 =
* Modern card-based admin UI
* Real-time asset processing with progress bar
* Directory size calculation and display
* WP-CLI zip command for creating archives
* File system location display in admin
* Improved error handling in asset downloads
* Better CSS/JS asset path rewriting
* Security enhancements: path traversal prevention, symlink protection, input sanitization
* Removed video/audio from download whitelist to conserve disk space
* Added disk usage monitoring and warnings
* Fixed admin page display issues
* Fixed directory size calculation
* Resolved namespace conflicts

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major update with security improvements, enhanced UI, and disk usage monitoring. Recommended for all users.

== Server Requirements ==

* PHP 7.4 or higher
* WordPress 5.0 or higher
* PHP ZipArchive extension (for ZIP export)
* curl or allow_url_fopen (for asset downloads)
* Write access to wp-content/cache/

== Use Cases ==

**Offline Documentation**
Generate a portable documentation site that works without internet access.

**Client Deliverables**
Export a complete static version for clients who don't need WordPress backend access.

**Archive Before Redesign**
Create a permanent snapshot of your site before major changes.

**CDN-Free Deployment**
Deploy to S3, Netlify, GitHub Pages, or any static hosting without WordPress dependencies.

**High-Availability Failover**
Rsync to a backup Nginx server for read-only failover during WordPress downtime.

== Support ==

For bug reports and feature requests, please visit our GitHub repository:
https://github.com/yourusername/static-cache-generator

For documentation and examples:
https://github.com/yourusername/static-cache-generator/wiki
