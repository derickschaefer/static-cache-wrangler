=== Static Cache Wrangler ===
Contributors: derickschaefer
Tags: static site, html export, offline, wp-cli, performance
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform your WordPress site into a fully self-contained static website — fast, lightweight, and completely offline-ready.

== Description ==

**Static Cache Wrangler** is a *lazy-loading, low-resource static cache and export engine* that automatically creates self-contained HTML versions of your WordPress site.  

It's perfect for anyone who wants to **preserve, distribute, or accelerate WordPress content** — whether you're archiving a client site, deploying to a CDN, or creating a portable offline version that just works anywhere.

Unlike traditional static site plugins that require full re-builds or database schema changes, **Static Cache Wrangler is zero-impact** —  
* It does not add custom database tables or modify your schema.
* All plugin options, cron jobs, and transients are automatically cleaned up upon uninstall.
* Your WordPress database remains exactly as it was before installation.
* An optional performance profiler allows developers and system admins to get a granular view of resources and performace related to this plugin.

**Perfect for:**
* Creating fully offline or portable copies of WordPress sites
* Rsyncing to read-only Nginx failover servers for high availability
* Publishing WordPress content to Amazon S3, Netlify, or static CDNs
* Geo-distributing cached static copies for fast global reads
* Archiving, demos, and secure client deliverables

---

### How It Works

1. Enable static site generation using the toggle in the sidebar.  
2. As users browse your site normally, each page visit creates a static HTML file.  
3. Assets (CSS, JS, images, fonts) are automatically downloaded and localized.  
4. Processing happens in the background and can be paused anytime.  
5. Download the complete static site as a ZIP file.  
6. Extract and open `index.html` in any browser — it works completely offline.

---

### Key Features

**What's New in 2.0.6:**

= 2.0.6 =

Version 2.0.6 is a **WordPress.org compliance release**.  
This update ensures full compatibility with the latest repository validation standards introduced in 2025, requiring prefixed template variables and enhanced namespace isolation.  

Although originally planned as a non-functional compliance update, we took the opportunity to introduce something valuable for developers — the foundation for advanced **performance profiling**.

**Compliance & Compatibility**

* **Prefix standardization** – All template variables, global references, and filters now use the `stcw_` prefix, satisfying the latest WordPress.org code scanning rules.  
* **Improved code validation** – Passes 100% of the new “Check Plugin” API scan results.  
* **Updated inline documentation** – Improved code clarity and compliance annotations.  

**Developer Enhancements**

New developer hooks were introduced to support the forthcoming **Static Cache Wrangler Performance Profiler**, an optional MU plugin for benchmarking and advanced diagnostics via WP-CLI.

The following hooks are now available for use:

* `stcw_before_file_save` — Fires immediately before a static file is written to disk.  
  - **Parameters:** `$static_file` *(string)* — Path to the file being saved.  
  - **Use Case:** Start timers, collect memory data, or monitor disk operations before file write.

* `stcw_after_file_save` — Fires after a static file is successfully written.  
  - **Parameters:** `$success` *(bool)* — True on success.  
    `$static_file` *(string)* — The saved file path.  
  - **Use Case:** Log, validate, or post-process generated static files.

* `stcw_before_asset_download` — Allows inspection or modification of asset URLs before download.  
  - **Parameters:** `$url` *(string)* — The original asset URL.  
  - **Returns:** Filtered URL string.

* `stcw_after_asset_download` — Executes after each asset download completes.  
  - **Parameters:** `$dest` *(string)* — Destination path.  
    `$url` *(string)* — Original URL.  
  - **Returns:** Filtered destination path (optional).

* `stcw_before_asset_batch` — Fires before an asynchronous asset download batch begins.  
  - **Use Case:** Initialize timers or resource tracking for async performance measurement.

* `stcw_after_asset_batch` — Fires after each asynchronous asset batch completes.  
  - **Parameters:** `$processed` *(int)* — Number of assets processed successfully.  
    `$failed` *(int)* — Number of failed downloads.  
  - **Use Case:** Record async batch performance, memory usage, or queue diagnostics.

**New Developer Tools Directory**

A new `/tools/` directory has been added to the plugin to organize optional developer resources.  
It currently includes:

* `performance-profiler.txt` — A detailed guide describing how to install and use the optional **Performance Profiler** MU plugin for advanced benchmarking.

Developers can download the latest version of the profiler from:  
[https://moderncli.dev/code/static-cache-wrangler/performance-profiler/](https://moderncli.dev/code/static-cache-wrangler/performance-profiler/)

**Summary**

* Compliance with 2025 WordPress.org plugin repository standards.  
* New developer hooks for performance profiling and async instrumentation.  
* Added `/tools/performance-profiler.txt` developer documentation.  
* No behavioral or UI changes for end users.  
* Zero database impact — profiling features remain opt-in and inactive by default.

**What's New in 2.0.5:**

Version 2.0.5 introduces enhanced static HTML output with comprehensive WordPress meta tag removal. Your generated static files are now 3.1% smaller, generate 2.3% faster, and contain zero WordPress-specific metadata. This makes them truly portable, more secure (no version exposure), and perfect for offline use or deployment to any platform.

Two new developer hooks (`stcw_remove_wp_head_tags` and `stcw_process_static_html`) enable companion plugins and custom integrations, opening up possibilities for agencies, developers, and SaaS providers to extend the plugin without modifying core code.

* **Lazy-load static generation** — creates static pages only when visited, minimizing CPU and memory usage.
* **Automatic asset localization** — CSS, JS, images, and fonts are downloaded and referenced locally.
* **Relative path rewriting** — ensures portability for offline or CDN hosting.
* **Zero-configuration** — works instantly after activation.
* **Zero database impact** — does not create or modify any database tables.
* **Clean uninstall** — all options, transients, and cron events are removed automatically.
* **Background processing** — non-blocking, low-impact generation.
* **One-click export** — download a ZIP of the entire static site.
* **Modern UI** — intuitive, card-based dashboard.
* **WP-CLI Support** — manage generation, status, cleanup, and export directly from the terminal.
* **Clean HTML Output** - Removes 7+ WordPress meta tags for portable, framework-agnostic static files
* **Developer Hooks** - Two extensibility hooks for companion plugins and custom integrations
* **Enhanced Security** - WordPress version and internal metadata hidden from static exports
* **Optimized Performance** - 3.1% smaller files and 2.3% faster generation than previous versions

---

### WP-CLI Commands

Full control without the dashboard:

* `wp scw enable` – Enable static generation  
* `wp scw disable` – Disable static generation  
* `wp scw status` – View current status and statistics  
* `wp scw process` – Process all pending assets  
* `wp scw clear` – Remove all generated static files  
* `wp scw zip` – Create a ZIP archive of the site  

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

= 2.0.6 =
* **Release Code Name - Moving Goal Post**
* Change all standard template variables to include the prefix stcw_ for WordPress.Org WordPress.NamingConventions.PrefixAllGlobals compliance.

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
