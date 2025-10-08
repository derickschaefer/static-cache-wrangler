=== Static Cache Generator ===
Contributors: derickschaefer
Tags: static site, cache, html, offline, performance
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform your WordPress site into a fully self-contained static website that works completely offline.

== Description ==

Static Cache Generator automatically creates static HTML versions of your WordPress pages as visitors browse your site. It downloads and localizes all assets (CSS, JS, images, fonts) so the exported site works without an internet connection.

**Perfect for:**

* Creating offline documentation
* Archiving WordPress sites
* Generating portable demos
* CDN-free deployments
* Client deliverables

**Key Features:**

* Zero-configuration - Works out of the box
* Automatic generation - Creates static files on page visits
* Asset localization - Downloads CSS, JS, images, fonts to local directory
* Relative paths - All links rewritten for portability
* Modern UI - Clean, card-based admin interface
* WP-CLI support - Full command-line control
* One-click export - Download entire static site as ZIP

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/static-cache-generator/` or install through WordPress plugin screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Settings > Static Cache to configure
4. Click "Enable Generation" to start creating static files
5. Browse your site normally - each page visit creates a static HTML file
6. Click "Download ZIP" when ready to export

== Frequently Asked Questions ==

= Does this work with any WordPress theme? =

Yes! The plugin captures the final HTML output from WordPress, so it works with any theme.

= Will it work with my page builder? =

Yes! Whether you use Elementor, Divi, Gutenberg, or any other page builder, the plugin captures the final rendered HTML.

= Can I use the static site on any web server? =

Absolutely! The static HTML files work on any web server - Apache, Nginx, or even opened directly in a browser without a server.

= Does it handle dynamic content? =

The plugin creates static snapshots of pages as they appear at the time of generation. Dynamic features like forms, comments, and real-time content won't work in the static version.

= How do I update the static site after making changes? =

Simply browse the updated pages while generation is enabled, or use the WP-CLI command `wp scg process` to regenerate all content.

== WP-CLI Commands ==

The plugin includes full WP-CLI support:

* `wp scg enable` - Enable static site generation
* `wp scg disable` - Disable static site generation
* `wp scg status` - Display current status and statistics
* `wp scg process` - Process all pending asset downloads
* `wp scg clear` - Remove all generated static files
* `wp scg zip` - Create a ZIP archive of the static site

== Screenshots ==

1. Modern card-based admin interface showing generation status
2. File system locations and directory information
3. Asset processing with real-time progress bar
4. WP-CLI commands reference card

== Changelog ==

= 2.0 =
* Complete rewrite with modern WordPress coding standards
* Added WP_Filesystem for all file operations
* Improved security with proper escaping and sanitization
* Added comprehensive error handling
* Modern card-based admin UI
* Real-time asset processing with progress indicator
* Better internationalization support
* Fixed all WordPress Plugin Check issues

= 1.0 =
* Initial release

== Upgrade Notice ==

= 2.0 =
Major update with improved security, WordPress coding standards compliance, and modern UI. Recommended for all users.

== Use Cases ==

**Offline Documentation**
Generate a complete static version of your documentation site that works without internet access.

**Client Deliverables**
Export a static version for clients who don't need WordPress, reducing hosting costs and complexity.

**Archive Before Redesign**
Create a complete snapshot before making major changes to your site.

**CDN-Free Deployment**
Deploy the static site to S3, Netlify, GitHub Pages, or any static hosting without WordPress dependencies.

**High Availability Failover**
Rsync static files to backup Nginx servers for read-only high availability during WordPress downtime.

== Technical Details ==

**Server Requirements:**
* PHP 7.4 or higher
* WordPress 5.0 or higher
* PHP ZipArchive extension (for ZIP export)
* Write access to wp-content/cache/

**How It Works:**
1. Visitor loads page → WordPress generates HTML
2. Plugin captures output → Rewrites all asset URLs to relative paths
3. Assets queued → CSS, JS, images, fonts added to download queue
4. Background processing → Assets downloaded and localized
5. Static file saved → Complete, portable HTML file created

**Performance:**
* Generation overhead: ~50-100ms per page
* Asset processing: Background queue, no user-facing delay
* Memory usage: ~2MB additional per request
* Disk I/O: Sequential writes, minimal impact

== Support ==

For bug reports and feature requests, please visit:
https://github.com/yourusername/static-cache-generator/issues

For documentation and guides, please visit:
https://github.com/yourusername/static-cache-generator/wiki
