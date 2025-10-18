=== Static Cache Generator ===
Contributors: derickschaefer
Tags: static site, html export, offline, wp-cli, performance
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform your WordPress site into a fully self-contained static website — fast, lightweight, and completely offline-ready.

== Description ==

**Static Cache Generator** is a *lazy-loading, low-resource static cache and export engine* that automatically creates self-contained HTML versions of your WordPress site.  

It’s perfect for anyone who wants to **preserve, distribute, or accelerate WordPress content** — whether you're archiving a client site, deploying to a CDN, or creating a portable offline version that just works anywhere.

Unlike traditional static site plugins that require full re-builds or database schema changes, **Static Cache Generator is zero-impact** —  
* It does not add custom database tables or modify your schema.*  
* All plugin options, cron jobs, and transients are automatically cleaned up upon uninstall.*  
* Your WordPress database remains exactly as it was before installation.*

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

* `wp scg enable` – Enable static generation  
* `wp scg disable` – Disable static generation  
* `wp scg status` – View current status and statistics  
* `wp scg process` – Process all pending assets  
* `wp scg clear` – Remove all generated static files  
* `wp scg zip` – Create a ZIP archive of the site  

---

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/static-cache-generator/` or install via Plugins > Add New.
2. Activate the plugin from the *Plugins* screen.
3. Navigate to **Settings → Static Cache** to enable generation.
4. Browse your site normally — pages are cached as they load.
5. Click **Download ZIP** to export the complete static version.

---

== Frequently Asked Questions ==

= Does it work with any theme or builder? =
Yes — Static Cache Generator captures the final rendered HTML, so it works with any theme, builder, or framework (Elementor®, Divi®, Gutenberg®, etc.).

= Does it use a lot of resources? =
No — it’s designed as a *lazy loader*, generating static pages only on demand with minimal memory and CPU impact.

= Does it modify my database? =
No — it never alters your WordPress database schema or adds tables.  
All plugin-related options, transients, and scheduled events are automatically removed upon uninstall.

= Can I use the exported site on any server? =
Absolutely. The output is plain HTML and assets — deploy it on any web server, CDN, or open it directly in a browser.

= Does it handle dynamic content? =
Dynamic features like forms, comments, or live feeds won’t function in the static version, but all rendered content and assets are preserved exactly.

= How do I update after making changes? =
Revisit the updated pages while generation is enabled, or run `wp scg process` to rebuild all static content.

---

== Screenshots ==

1. Card-based dashboard showing generation status and stats  
2. Admin bar actions and stats
3. Background asset queue with progress indicator 
4. Paused status with ability to resume 

---

== Changelog ==

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

= 2.0 =
Major update with enhanced stability, performance, and compliance. Recommended for all users.

---

== Use Cases ==

**Offline Documentation** — Generate static, portable documentation sites for offline or air-gapped environments.  
**Client Deliverables** — Deliver static versions that eliminate hosting complexity.  
**Failover Ready** — Rsync to a read-only Nginx® server for uninterrupted uptime.  
**CDN / Amazon S3® Deployment** — Publish static HTML to Amazon S3®, Cloudflare®, or Netlify® for instant global delivery.  
**Geo Load Balancing** — Serve from multiple regions with Cloudflare or Amazon Route53® for high performance.  
**Archival Snapshots** — Capture your site before major redesigns or migrations.

---

== Technical Details ==

**Requirements**
* PHP 7.4+
* WordPress 5.0+
* ZipArchive PHP extension
* Write access to `wp-content/cache/`

**Performance**
* Generation overhead: ~50–100 ms/page
* Memory: ~2 MB additional per request
* Asset downloads handled asynchronously

**Architecture**
* Visitor loads page → HTML captured
* Plugin rewrites URLs and queues assets
* Assets downloaded → stored locally
* Static file saved with relative links

**Database Impact**
* No custom tables or schema modifications
* Uses native WordPress options and transients
* All plugin data and scheduled tasks removed automatically on uninstall

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
[GitHub – Static Cache Generator](https://github.com/yourusername/static-cache-generator)  
[Documentation & Guides](https://github.com/yourusername/static-cache-generator/wiki)

---

**Interested in learning more about command-line interfaces and WP-CLI?**  
Check out [ModernCLI.dev](https://moderncli.dev) — a practical guide to mastering modern CLI workflows.
