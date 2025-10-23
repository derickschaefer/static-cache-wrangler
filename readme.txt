=== Static Cache Wrangler ===
Contributors: derickschaefer
Tags: static site, html export, offline, wp-cli, performance
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.4
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

---

== Screenshots ==

1. Card-based dashboard showing generation status and stats  
2. Admin bar actions and stats
3. Background asset queue with progress indicator 
4. Paused status with ability to resume 

---

== Changelog ==

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

= 2.0.4 =
WordPress.org compliance update. BREAKING CHANGE: Clear and regenerate static files after upgrading. All internal prefixes changed AGAIN to meet WordPress requirements.  Also, a complete name change from US trademark protected generic name to trademarkable unique name to meet WordPress compliance.   Features 2.0.3 and 2.0.4 feature ZERO technical nor functional improvements but have massive, code, compsobility, and featuring breaking changes to meet WordPress.Org compliance.

= 2.0.3 =
WordPress.org compliance update. BREAKING CHANGE: Clear and regenerate static files after upgrading. All internal prefixes changed to meet WordPress requirements.

= 2.0 =
Major update with enhanced stability, performance, and compliance. Recommended for all users.

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

== Trademark Recognition and Legal Disclaimer ==

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
ModernCLI.Dev is owned by Derick Schaefer.

This plugin has not been tested by any of the services, platforms, software projects nor their respective owners.  
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
