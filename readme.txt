=== Static Cache Wrangler ===
Contributors: derickschaefer
Tags: static site, static site generator, html export, static site export, cache
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Export your WordPress site as a static HTML website — fast, secure, and offline-ready with WP-CLI support.

== Description ==

***Static Cache Wrangler*** is a lightweight, low‑resource *static site generator* and *HTML export engine* for WordPress. It automatically creates ***self‑contained, URL‑agnostic static pages*** of your site — perfect for anyone who needs to ***preserve, distribute, or accelerate WordPress content***.

Originally developed as a command‑line tool for ***WP‑CLI***, it has evolved into composable, zero‑impact tooling that supports administrators, developers, and site owners alike. Whether you're archiving a client project, deploying to a CDN, or creating a portable offline version that runs anywhere, Static Cache Wrangler delivers fast, CDN‑ready HTML output without complex setup.

Technically, the plugin uses an *asynchronous, lazy‑loading build mechanism* that generates static pages as visitors browse your site. Over time, your entire site is exported as lightweight static HTML.  
Free companion plugin [STCW Coverage Assistant](https://wordpress.org/plugins/stcw-coverage-assistant/) adds real‑time build monitoring and manually triggered full‑site generation for any uncached pages.

Unlike traditional static‑site plugins that require full crawls or database schema changes, ***Static Cache Wrangler is zero‑impact***:
* Does **not** add custom database tables or modify your schema  
* Automatically cleans up all plugin options, cron jobs, and transients upon uninstall  
* Keeps your WordPress database completely untouched  
* Runs all caching processes **asynchronously** in the background  
* Offers an optional **performance profiler** for granular resource and execution insights

***Perfect for:***
* Creating fully offline or portable copies of WordPress sites  
* Rsyncing to read‑only Nginx failover servers for high availability  
* Publishing WordPress content to Amazon S3®, Netlify®, or other static CDNs  
* Geo‑distributing static HTML for ultra‑fast global reads  
* Archiving, demos, and secure client deliverables  
* Small to mid‑sized sites (≈ 100 pages +) — upcoming assistant plugin expands this to 1 K pages  
* Multisite administrators needing scalable static‑cache exports  

A demo site created using this plugin can be found at [Cache Wrangler Demo Site](https://static.cachewrangler.com/) which is a static version of this [WordPress site](https://cacherangler.com/)

***Static Cache Wrangler*** turns WordPress into a fast, secure, and portable static‑site generator — with **no database changes**, **no vendor lock‑in**, and **no maintenance overhead**.

**Detailed Testing and Profiling:**
* Triggering of page rendering requires 1-2MB of PHP memory for the duration of the process.
* Average processing duration is less than 500 ms (meaning memory consumption is quickly released)
* Average asset (JS, CSS, image) batch processing is less than 100 ms
* WordPress Core and theme rendering is generally 20-25 MB; SCW usage keeps total memory below 30 MB
* CLI commands are even more efficient

**Funding Model**
* This plugin and all companion plugins will remain 100% free (true WordPress style)
* Want to make a donation? Consider purchasing a copy of the author's book on command-line interfaces for yourself or as a gift.

[Modern CLI Book](https://moderncli.dev)

---

### How It Works

1. Enable static site generation using the toggle in the sidebar.  
2. As users browse your site normally, each page visit creates a static HTML file.  
3. Assets (CSS, JS, images, fonts) are automatically are queued and asyncronously downloaded and localized.  
4. Processing happens in the background and can be paused anytime.  
5. Use free companion plugin to monitor and accelerate cache creation [STCW Coverage Assistant](https://wordpress.org/plugins/stcw-coverage-assistant/)
5. Download the complete static site as a ZIP file.  
6. Extract and open `index.html` in any browser — it works completely offline.

---

### Key Features

**What's New in 2.1.0:**

= 2.1.0 =

Version 2.1.0 introduces **static sitemap generation** - a file system-based approach that creates sitemaps from your actual cached files rather than the WordPress database.

This update enables true static sitemaps that work in exported sites without WordPress, PHP, or a database connection - perfect for deployments to S3, Netlify, GitHub Pages, or any static hosting platform.

**New WP-CLI Commands**

* `wp scw sitemap` - Generate sitemap.xml from cached static files
* `wp scw sitemap-delete` - Remove sitemap files

**Why File System-Based?**

Traditional WordPress sitemap plugins (Yoast SEO, Rank Math) query the database dynamically. This works great for live sites, but fails for static exports because there's no PHP or database available.

Static Cache Wrangler scans your actual cached index.html files to build the sitemap, ensuring:

* **Perfect accuracy** - sitemap matches exported content exactly
* **True portability** - works without WordPress/PHP/database
* **SEO compliance** - search engines see what users see
* **Deploy anywhere** - S3, Netlify, GitHub Pages, any static host

**Sitemap Features**

* Generates sitemaps.org compliant XML sitemap
* Creates XSL stylesheet for browser viewing
* Calculates priorities automatically (homepage = 1.0, deeper pages = 0.4)
* Assigns smart change frequencies (homepage = daily, pages = monthly)
* Includes last modification times from file metadata
* Developer filter hook: `stcw_sitemap_changefreq`
* Multisite compatible with isolated sitemaps per site
* Fast performance: ~50-100ms per 100 cached files

**Typical Workflow**

```
wp scw enable          # Enable generation
# Browse your site...
wp scw process        # Process assets
wp scw sitemap        # Generate sitemap (NEW!)
wp scw zip            # Export with sitemap included
# Deploy to S3/Netlify...
```

**View in Browser**

Open https://your-site.com/sitemap.xml - the XSL stylesheet transforms it into a readable HTML table with color-coded priorities and sortable columns.

**Technical Implementation**

* New STCW_Sitemap_Generator class scans cached directory recursively
* Uses WordPress Filesystem API for all file operations
* Proper output escaping and sanitization throughout
* No database queries - pure file system operations
* Multisite compatible with isolated sitemaps per site
* Performance optimized: ~50-100ms per 100 files, ~2MB memory for 1,000+ pages

**GUI Coming Soon**

This release is CLI-only to validate the approach and gather user feedback. A visual interface in WordPress admin is planned for version 2.2.0.

**Compatibility**

* WordPress 6.8.3
* PHP 7.4, 8.0, 8.1, 8.2, 8.3
* Multisite compatible
* WP-CLI required for sitemap generation

**What's New in 2.0.7:**

= 2.0.7 =

Version 2.0.7 is a **major compatibility enhancement release**, focused on improving support for Kadence Blocks and all Gutenberg block plugins that rely on dynamic JavaScript and CSS enqueues.

This update improves the accuracy of static exports by capturing **all front-end assets that WordPress prints dynamically**—including those output by `wp_print_scripts()` and `wp_print_footer_scripts()`—ensuring that interactive block functionality is preserved outside of WordPress.

**Compatibility Improvements**

* **Full Kadence Blocks compatibility** – Global front-end scripts and CSS are now correctly captured, enabling buttons, accordions, icons, Lottie animations, progress bars, and other JS-driven components to function in static exports.
* **Enhanced Gutenberg block support** – STCW now reliably detects and exports front-end assets from *all* major block suites (Spectra, Stackable, GenerateBlocks, CoBlocks, Otter, etc.).
* **Improved script capture logic** – Dynamically enqueued scripts are now collected before theme rendering, eliminating missing-asset issues for blocks that load JS only when present on a page.
* **Better preservation of front-end behavior** – Assets required for tooltips, animations, scroll effects, responsive layouts, and block initialization are now exported consistently.
* **Resolved blank-page edge cases** – Updated capture timing ensures no interference with WordPress's script printing lifecycle, preventing rendering conflicts.

**Technical Enhancements**

* Added an early script-queue flush to collect output from:
  * `wp_print_head_scripts()`
  * `wp_print_scripts()`
  * `wp_print_footer_scripts()`
* Captured script output is appended to the main buffer **before** asset extraction, ensuring regex-based scanners detect dynamically enqueued JS/CSS.
* Improved compatibility with theme and plugin lifecycle order by isolating script capture from template rendering.
* Enhanced support for plugins that insert block assets conditionally based on block presence.
* Increased reliability of static exports for mixed-content layouts and advanced interactive block patterns.

---

### WP-CLI Commands

Full control without the dashboard:

* `wp scw enable` – Enable static generation  
* `wp scw disable` – Disable static generation  
* `wp scw status` – View current status and statistics  
* `wp scw process` – Process all pending assets  
* `wp scw clear` – Remove all generated static files  
* `wp scw zip` – Create a ZIP archive of the site  
* `wp scw sitemap` – Generate sitemap.xml from cached files
* `wp scw sitemap --target-url=<url>` – Generate sitemap for deployment URL
* `wp scw sitemap-delete` – Remove sitemap files

---

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/static-cache-wrangler/` or install via Plugins > Add New.
2. Activate the plugin from the *Plugins* screen.
3. Navigate to **Settings → Static Cache** to enable generation.
4. Browse your site normally — pages are cached as they load.
5. Click **Download ZIP** to export the complete static version.

---

== Frequently Asked Questions ==

= Does it work with any theme or builder? =
Yes — Static Cache Wrangler captures the final rendered HTML, so it works with any theme, builder, or framework (Elementor®, Divi®, Gutenberg®, etc.).

= Does it use a lot of resources? =
No — it's designed as a *lazy loader*, generating static pages only on demand with minimal memory and CPU impact.

= Does it modify my database? =
No — it never alters your WordPress database schema or adds tables.  
All plugin-related options, transients, and scheduled events are automatically removed upon uninstall.

= Can I use the exported site on any server? =
Absolutely. The output is plain HTML and assets — deploy it on any web server, CDN, or open it directly in a browser.

= Does it handle dynamic content? =
Dynamic features like forms, comments, or live feeds won't function in the static version, but all rendered content and assets are preserved exactly.

= How do I update after making changes? =
Revisit the updated pages while generation is enabled, or run `wp scw process` to rebuild all static content.

= Can I customize the configuration? =

Yes! You can customize file locations and behavior using constants in `wp-config.php`:

**Change static files location:**
`define('STCW_STATIC_DIR', WP_CONTENT_DIR . '/my-static-files/');`

**Change assets location:**
`define('STCW_ASSETS_DIR', WP_CONTENT_DIR . '/my-assets/');`

**Disable async asset processing (process immediately):**
`define('STCW_ASYNC_ASSETS', false);`

= What if I need advanced customization? =

You can use WordPress filters to customize behavior:

**Exclude specific URLs from generation:**
`add_filter('stcw_should_generate', function($should, $url) {
    if (strpos($url, '/private/') !== false) {
        return false;
    }
    return $should;
}, 10, 2);`

**Modify HTML before saving:**
`add_filter('stcw_before_save_html', function($html) {
    return str_replace('</body>', '<footer>Generated: ' . date('Y-m-d') . '</footer></body>', $html);
});`

= What WordPress meta tags are removed in version 2.0.5? =

Version 2.0.5 removes the following WordPress-specific meta tags from static HTML:
* RSD (Really Simple Discovery) links for XML-RPC
* Windows Live Writer manifest links
* WordPress shortlink tags
* Generator meta tags (WordPress version)
* REST API discovery links
* oEmbed discovery links
* REST API HTTP headers
* `data-wp-strategy` attributes on script tags

These tags serve no purpose in static sites and removing them improves portability and security.

= How do I use the new developer hooks? =

**Remove additional WordPress tags:**
`
add_action('stcw_remove_wp_head_tags', function() {
    remove_action('wp_head', 'your_custom_action');
});
`

**Modify HTML before saving:**
`
add_filter('stcw_process_static_html', function($html) {
    // Add custom footer, remove tracking, etc.
    return $html;
});
`

See the plugin documentation for 15+ complete examples.

= Do I need to regenerate my static files after upgrading? =

No, but it's recommended. Existing static files will continue to work, but regenerating will give you the cleanest output with all WordPress meta tags removed:

`wp scw clear`
`wp scw enable`

Then browse your site to regenerate pages.

= Can I create companion plugins for Static Cache Wrangler? =

Yes! Version 2.0.5 introduces two hooks specifically designed for companion plugins:
* `stcw_remove_wp_head_tags` - Remove additional WordPress tags
* `stcw_process_static_html` - Modify final HTML output

These enable agencies, developers, and SaaS providers to build specialized extensions without modifying core plugin code.

= Will this work with my SEO plugin? =

Yes! Version 2.0.5 is fully compatible with:
* Yoast SEO
* Rank Math
* All in One SEO Pack
* SEOPress
* The SEO Framework

The meta tag removal only affects WordPress core tags, not SEO plugin meta tags that contain important information.

---

== Screenshots ==

1. Card-based dashboard showing generation status and stats  
2. Admin bar actions and stats
3. Background asset queue with progress indicator 
4. Paused status with ability to resume 

---

== Changelog ==

### = 2.1.0 =
= 2.1.0 =
* **NEW:** Static sitemap generation from cached files (CLI-only)
* **NEW:** `wp scw sitemap` command generates sitemap.xml and sitemap.xsl
* **NEW:** `wp scw sitemap-delete` command removes sitemap files
* **NEW:** File system-based approach - scans cached files instead of database
* **NEW:** Automatic priority calculation based on URL depth
* **NEW:** Smart change frequency assignment (homepage = daily, pages = monthly)
* **NEW:** XSL stylesheet for browser-viewable sitemaps
* **NEW:** Developer hook: `stcw_sitemap_changefreq` filter for customization
* **IMPROVED:** Sitemaps work in static exports without WordPress/PHP/database
* **IMPROVED:** Perfect accuracy - sitemap reflects actual exported content
* **IMPROVED:** Multisite compatible with isolated sitemaps per site
* **PERFORMANCE:** ~50-100ms scan time per 100 cached files, ~2MB memory for 1,000+ pages
* **COMPATIBLE:** WordPress 6.8.3, PHP 7.4-8.3
* **NOTE:** GUI interface planned for v2.2.0 (currently CLI-only)

**Why File System-Based?**
Traditional WordPress sitemap plugins (Yoast SEO, Rank Math) query the database dynamically. This works great for live sites, but fails for static exports because there's no PHP or database available. Static Cache Wrangler scans your actual cached index.html files to build the sitemap, ensuring perfect accuracy and true portability.

**Sitemap Features**
* Generates sitemaps.org compliant XML sitemap
* Creates XSL stylesheet for browser viewing
* Calculates priorities automatically (homepage = 1.0, deeper pages = 0.4)
* Assigns smart change frequencies based on URL patterns
* Includes last modification times from file metadata
* Multisite compatible with isolated sitemaps per site
* Fast performance: ~50-100ms per 100 cached files

**New WP-CLI Commands**
* `wp scw sitemap` - Generate sitemap from cached files
* `wp scw sitemap-delete` - Remove sitemap files

**Typical Workflow**
```
wp scw enable          # Enable generation
wp scw process        # Process assets
wp scw sitemap        # Generate sitemap (NEW!)
wp scw zip            # Export with sitemap included
```

View your sitemap at https://your-site.com/sitemap.xml - the XSL stylesheet transforms it into a readable HTML table.

**Technical Implementation**
* New STCW_Sitemap_Generator class scans cached directory recursively
* Uses WordPress Filesystem API for all file operations
* Proper output escaping and sanitization throughout
* Developer filter hook for customization
* No database queries - pure file system operations
* GUI interface planned for version 2.2.0

### = 2.0.7 =
* **Major Compatibility Enhancement Release**
* Version 2.0.7 delivers extensive improvements to **Kadence Blocks support** and significantly enhances compatibility with **all Gutenberg block plugins** that rely on dynamically printed JavaScript and CSS.
* Ensures **accurate static exports** by capturing all front-end assets output by WordPress during rendering—including assets printed via `wp_print_scripts()` and `wp_print_footer_scripts()`—preserving full block interactivity outside of WordPress.

**Compatibility Improvements**
* **Full Kadence Blocks compatibility** – Global front-end scripts and styles are now correctly captured, enabling complete functionality for JS-dependent components (accordions, buttons, icons, Lottie animations, progress bars, etc.).
* **Enhanced Gutenberg block suite support** – Reliable detection and export of dynamic assets from Spectra, Stackable, GenerateBlocks, CoBlocks, Otter, and other major block libraries.
* **Improved dynamic script capture logic** – Scripts enqueued conditionally based on block presence are now collected earlier, preventing missing-asset issues.
* **Better preservation of interactive behavior** – Tooltips, animations, scroll effects, responsive scripting, and block initialization logic now export more consistently.
* **Resolved rare blank-page rendering issues** caused by timing conflicts in WordPress’s script-printing lifecycle.

**Technical Enhancements**
* Implemented an early script-queue flush to capture output from:
  * `wp_print_head_scripts()`
  * `wp_print_scripts()`
  * `wp_print_footer_scripts()`
* Captured script output is now injected into the main buffer **before** asset parsing, ensuring regex scanners detect all JS/CSS printed during rendering.
* Improved isolation of script-capture routines from theme templates to avoid lifecycle interference.
* Better support for plugins that enqueue front-end assets only when specific blocks are present.
* Increased reliability for mixed-content layouts and advanced interactive block patterns.

**Compatibility**
* Tested with WordPress 6.8.3 and PHP 7.4–8.3
* Fully backward compatible with 2.0.6

**Migration Notes**
* No configuration changes required.
* Recommended: Regenerate static exports after update to ensure full compatibility with updated asset-capture behavior.

= 2.0.6 =
* **WordPress.org Compliance Release – “Moving Goal Post”**
* Version 2.0.6 is a **WordPress.org compliance update** ensuring full compatibility with the latest repository validation standards introduced in 2025
* Implemented **prefix standardization** – all template variables, global references, and filters now use the `stcw_` prefix for WordPress.NamingConventions.PrefixAllGlobals compliance
* Achieved **100% pass** on the updated “Check Plugin” API validation scans
* Enhanced **namespace isolation** and improved code safety for plugin interoperability
* **Improved inline documentation** for better code clarity and compliance traceability
* **Developer Enhancements**
* Introduced new developer hooks to support the forthcoming **Static Cache Wrangler Performance Profiler** MU plugin available here: [Performance Profiler](https://moderncli.dev/code/static-cache-wrangler/performance-profiler/)
* Added foundations for advanced **performance profiling and benchmarking** via WP‑CLI integration
* Enhanced developer experience with cleaner structure for extending cache behavior
* **Improvements**
* Non‑functional update focused on long‑term maintainability and ecosystem compliance
* Verified adherence to current WordPress.org repository and coding‑standards checks
* Refined internal structure to support future diagnostic and profiling modules
* **Compatibility**
* Tested with WordPress 6.8.3 and PHP 7.4 – 8.3
* Fully backward compatible with 2.0.5
* **Migration Notes**
* No functional changes to caching logic
* Recommended: Regenerate static files after update for clean metadata

= 2.0.5 =
* **Enhanced Static HTML Output**
* Implemented hybrid WordPress meta tag removal using native `remove_action()` with regex safety net
* Removed 7+ WordPress-specific meta tags from static output (RSD, wlwmanifest, shortlinks, generator, REST API, oEmbed discovery)
* Stripped `data-wp-strategy` attributes from script tags for cleaner HTML
* Reduced HTML file size by 3.1% and generation time by 2.3%
* Improved security by hiding WordPress version information from static exports
* **New Developer Hooks**
* Added `stcw_remove_wp_head_tags` action hook - remove additional WordPress head tags before generation
* Added `stcw_process_static_html` filter hook - modify HTML output before saving to file
* Added `STCW_Generator::remove_wordpress_meta_tags()` method for centralized tag removal
* **Improvements**
* Enhanced code documentation with comprehensive PHPDoc comments
* Better extensibility for companion plugins and theme integration
* Follows WordPress coding standards and best practices
* More maintainable code with clear separation of concerns
* **Compatibility**
* Tested with WordPress 6.8.3
* Compatible with PHP 7.4, 8.0, 8.1, 8.2, 8.3
* Works with all major themes, page builders, and SEO plugins
* **Migration Notes**
* No breaking changes - fully backward compatible with 2.0.4
* Recommended: Clear and regenerate static files for cleanest output (`wp scw clear && wp scw enable`)
* Optional: Review new hooks for customization opportunities

= 2.0.4 =
* **Major WordPress.org Compliance & Refactor Release**  
  This update brings the plugin fully in line with current WordPress.org Plugin Directory requirements and coding standards. Nearly every internal file, reference, and namespace was audited, renamed, or rewritten for long-term maintainability and compliance.
* **Plugin slug, text domain, and directory renamed** from `static-cache-generator` → `static-cache-wrangler` to meet naming and trademark guidelines.
* **All file, class, and function prefixes** updated from `STCG` → `STCW` for consistent 4-character namespace compliance.
* **Text domain and translation calls** unified across all PHP files for proper i18n validation.
* **Folder structure and includes** modernized for autoloading consistency and WP.org scanning compatibility.
* **Admin interface and view templates** refactored for cleaner markup and translation readiness.
* **CLI namespace and command base** confirmed as `scw` (formerly `scg`) with backward compatibility removed for clarity.
* **All GitHub and asset references** updated to reflect the new canonical project name and repository.
* **Packaging and distribution scripts** updated for compliance (clean `.zip` exports excluding dev files).
* **BREAKING CHANGE:** Sites upgrading from earlier builds must **deactivate the old “Static Cache Generator” plugin, delete it, and install/activate “Static Cache Wrangler.”**
  Data and generated files can be safely regenerated once activated.

= 2.0.3 =
* **WordPress.org Compliance Update**
* Changed all PHP prefixes from SCG_ to STCW_ (4+ character requirement)
* Properly enqueued all scripts and styles (removed inline code)
* Extracted CSS to admin/css/admin-style.css
* Extracted JavaScript to admin/js/admin-script.js and includes/js/auto-process.js
* WP-CLI commands unchanged for user convenience (still `wp scw`)
* **BREAKING CHANGE:** Requires clearing and regenerating static files after update
* All option names changed (scw_enabled → stcw_enabled, etc.)
* All WordPress hooks changed (scw_process_assets → stcw_process_assets, etc.)
* See full migration guide on GitHub for technical details

= 2.0.2 =
* Enhanced stability and performance
* Improved CLI feedback and logging
* Refined asset handling and path rewriting
* Clean uninstall now clears all options, transients, and cron events
* Minor UI and accessibility improvements

= 2.0 =
* Complete rewrite with modern coding standards
* WP_Filesystem support for all operations
* Stronger security and sanitization
* Modern admin UI with real-time asset tracking
* Full WP-CLI integration
* Fixed Plugin Check and PHPCS compliance

= 1.0 =
* Initial release

---

== Upgrade Notice ==

= 2.0.6 =
Change all standard template variables to include the prefix stcw_ for WordPress.Org WordPress.NamingConventions.PrefixAllGlobals compliance.

= 2.0.5 =
Enhanced multisite support with isolated directories per site. Storage moved to wp-content/cache/stcw_static/ with unique namespace. Fully backward compatible for new installs. WordPress.org compliant.

= 2.0.4 =
Major compliance update. Plugin renamed to Static Cache Wrangler. Clear old plugin before installing. All prefixes changed from SCG to STCW. No data migration needed as plugin not yet published.

= 2.0.3 =
WordPress.org compliance. All prefixes changed to STCW (4+ characters). Clear and regenerate static files after update. WP-CLI commands unchanged.

= 2.0 =
Major rewrite with modern standards, WP_Filesystem support, and enhanced security. Recommended for all users.

---

== Advanced Configuration ==

### Server Requirements

* **PHP:** 7.4 or higher
* **WordPress:** 5.0 or higher
* **PHP Extensions:** ZipArchive (for ZIP export), curl or allow_url_fopen (for asset downloads)
* **Disk Space:** Varies based on site size (static files = ~1.5x site size)
* **Permissions:** Write access to `wp-content/cache/`

### Performance Characteristics

* **Generation overhead:** ~50–100 ms/page
* **Memory:** ~2 MB additional per request
* **Asset downloads:** Handled asynchronously in background

### Disk Space Considerations

**Typical Usage:**
* Small site (10-50 pages): 50-200 MB
* Medium site (100-500 pages): 200 MB - 1 GB  
* Large site (1000+ pages): 1-5 GB

Monitor disk usage via Settings → Static Cache or `wp scw status`.

To reduce disk usage, exclude large pages:
`add_filter('stcw_should_generate', function($should, $url) {
    if (strpos($url, '/gallery/') !== false) {
        return false;
    }
    return $should;
}, 10, 2);`

### What Gets Downloaded

✅ CSS files, JavaScript files, Images (all formats), Fonts, Favicons, Responsive images (srcset)

❌ External CDN assets (preserved as-is), Third-party embeds (YouTube, Twitter), Video/audio files (intentionally excluded to save space)

---

== Use Cases ==

**Offline Documentation** — Generate static, portable documentation sites for offline or air-gapped environments.  
**Client Deliverables** — Deliver static versions that eliminate hosting complexity.  
**Failover Ready** — Rsync to a read-only Nginx® server for uninterrupted uptime.  
**CDN / Amazon S3® Deployment** — Publish static HTML to Amazon S3®, Cloudflare®, or Netlify® for instant global delivery.  
**Geo Load Balancing** — Serve from multiple regions with Cloudflare or Amazon Route53® for high performance.  
**Archival Snapshots** — Capture your site before major redesigns or migrations.

---

## == Trademark Recognition and Legal Disclaimer ==

All product names, logos, and brands referenced in this plugin and its documentation are property of their respective owners.

**Static Cache Wrangler** (also historically referred to as **Static Cache Generator**) must always be referenced using all three words — **“Static Cache Wrangler.”**  

The prior name **Static Cache Generator** is considered a *legacy name* and is no longer in use, as it did not meet [WordPress.org plugin naming standards](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#1-plugins-must-have-unique-names) due to its generic nature. That name was originally selected in good faith to avoid any potential confusion with existing trademarks or brand names.  

Prior to renaming, **U.S. and international trademark databases** were reviewed by **independent intellectual property counsel**, and **no conflicts or registered marks** were identified for the phrases **“Static Cache Generator,” “Cache Generator,”** or **“Static Cache.”**  
Counsel further cited legal precedent confirming that generic terms cannot be trademarked, including:  
- *USPTO v. Booking.com B.V.*, 591 U.S. ___ (2020)  
- *In re Hotels.com, L.P.*, 573 F.3d 1300 (Fed. Cir. 2009)  
- *Kellogg Co. v. National Biscuit Co.*, 305 U.S. 111 (1938)  

The current and accepted plugin name **Static Cache Wrangler**:  
- Does **not** imply, refer to, or associate with the standalone trademark **“Wrangler”** of **Wrangler Apparel**.  
- Does **not** imply, refer to, or associate with the standalone trademark **“Cache”** (U.S. Reg. No. **6094619**, registered July 7, 2020).  
- Does **not** imply, refer to, or associate with any trademarks involving the standalone term **“Static.”**  
- Has **no connection** with **Automattic Inc.**, its employees, or any internal job titles (e.g., “Wrangler”) used within Automattic. The name **Static Cache Wrangler** does not suggest endorsement, employment, or automation of work performed by Automattic personnel.  

This clarification is provided in accordance with **U.S. trademark law**, **WordPress.org plugin repository policies**, and general principles of fair use and naming transparency.  
*(U.S. Reg. No. 6094619 reference: “CACHE,” Registered July 7, 2020; Status: LIVE/REGISTRATION/Issued and Active — TSDR, generated 2025-10-23 08:17:17 EDT.)*

---

### Third-Party Trademark Notices

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
ModernCLI.Dev is owned by Derick Schaefer.  

This plugin has not been tested by any of the services, platforms, software projects, nor their respective owners.  
These names and services are referenced solely as examples of where static cache files might be repurposed, used, uploaded, stored, or transmitted.  

This plugin is an independent open-source project and is **not endorsed by, affiliated with, or sponsored by** any of the companies or open-source projects mentioned herein.  

---

== Support ==

For issues, requests, and documentation, visit:  
[GitHub – Static Cache Wrangler](https://github.com/derickschaefer/static-cache-wrangler)
[Documentation & Guides](https://moderncli.dev/code/static-cache-wrangler/)

---

**Interested in learning more about command-line interfaces and WP-CLI?**  
Check out [ModernCLI.dev](https://moderncli.dev) — a practical guide to mastering modern CLI workflows.
