# README Updates for Sitemap Feature (v2.1.0)

## Insert After "WP-CLI Commands" Section

### `wp scw sitemap`
Generate sitemap.xml and sitemap.xsl from cached static files.
```bash
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

## Insert in "Use Cases" Section

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

## Insert in "Features" Section

* **SEO sitemap generation** — Creates static sitemap.xml from cached files (v2.1.0+)
* **Browser-viewable sitemaps** — Includes XSL stylesheet for human-readable format
* **Smart priority calculation** — Automatic URL prioritization based on depth

## New "Sitemap Generation" Section (Insert Before "Performance")

## Sitemap Generation

Version 2.1.0 introduces file system-based sitemap generation for static exports.

**Key Features:**
- ✅ Generates from cached files (not database)
- ✅ Includes modification times from file metadata  
- ✅ Calculates priorities automatically (homepage = 1.0, deeper pages = 0.4)
- ✅ Assigns reasonable change frequencies
- ✅ Creates XSL stylesheet for browser viewing
- ✅ Fully portable with static export

**Quick Start:**
```bash
wp scw sitemap
```

**Viewing in browser:**  
Open `https://your-site.com/sitemap.xml` — the XSL stylesheet transforms it into a readable HTML table.

**Search engine submission:**  
Submit the sitemap to Google Search Console and Bing Webmaster Tools after deploying your static site.

**Complete documentation:**  
See [SITEMAP.md](docs/SITEMAP.md) for detailed usage, customization, and examples.

## Insert in "Roadmap" Section

- [x] Sitemap generation from cached files (v2.1.0)
- [ ] GUI interface for sitemap management
- [ ] Sitemap index support for 50,000+ URLs
- [ ] Image and video sitemap support

## Insert in "Developer Hooks" Section (v2.1.0+)

### Sitemap Customization
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

## Updated "Complete Workflow" Example

```bash
# 1. Enable generation
wp scw enable

# 2. Browse site (manually or with crawler)
wget --mirror --convert-links https://your-site.com/

# 3. Process assets
wp scw process

# 4. Generate sitemap (NEW in v2.1.0)
wp scw sitemap

# 5. Export everything
wp scw zip --output=/tmp/static-site.zip

# 6. Deploy
aws s3 sync /tmp/static-site/ s3://your-bucket/ --delete
```

## FAQ Additions

### Q: Why file system-based sitemap instead of database?

**A:** WordPress sitemap plugins (Yoast SEO, Rank Math) generate sitemaps dynamically from the database using PHP. When you export a static site, those sitemaps won't work because there's no database or PHP engine. Static Cache Wrangler creates a true static `sitemap.xml` file that works anywhere — on S3, Netlify, or even opened locally as files.

### Q: Can I use both WordPress sitemaps and static sitemaps?

**A:** Yes! During development, use your preferred WordPress sitemap plugin. When exporting the static version, run `wp scw sitemap` to generate a static sitemap specifically for the export. They don't conflict.

### Q: How do I customize sitemap priorities or change frequencies?

**A:** Use the `stcw_sitemap_changefreq` filter:
```php
add_filter('stcw_sitemap_changefreq', function($freq, $path) {
    if (strpos($path, '/products/') !== false) {
        return 'weekly';
    }
    return $freq;
}, 10, 2);
```

See [SITEMAP.md](docs/SITEMAP.md) for complete customization examples.

### Q: Does the sitemap get included in ZIP exports?

**A:** Yes! If you run `wp scw sitemap` before `wp scw zip`, the sitemap.xml and sitemap.xsl files will be included in the ZIP archive automatically.

## CHANGELOG Addition

### [2.1.0] - 2025-XX-XX

#### Added
- **NEW:** Static sitemap generation from cached files
- Added `wp scw sitemap` CLI command for sitemap.xml generation
- Added `wp scw sitemap-delete` CLI command to remove sitemap files
- Created XSL stylesheet for browser-viewable sitemaps
- Smart priority calculation based on URL depth (homepage = 1.0, deeper = 0.4)
- Automatic change frequency assignment (homepage = daily, pages = monthly)
- Added `stcw_sitemap_changefreq` filter hook for customization
- Comprehensive sitemap documentation in SITEMAP.md

#### Technical Details
- New `STCW_Sitemap_Generator` class in `/includes/class-stcw-sitemap-generator.php`
- Scans cached file system recursively for index.html files
- Generates sitemaps.org-compliant XML (max 50,000 URLs)
- Uses file modification times for accurate lastmod dates
- Multisite-compatible with isolated sitemaps per site
- Performance: ~50-100ms per 100 cached files, ~2MB memory for 1,000+ pages

#### Why File System-Based?
Unlike WordPress sitemap plugins that query the database, this generates sitemaps from actual static files, ensuring:
- Accuracy — sitemap matches exported content exactly
- Portability — works without WordPress/PHP/database
- SEO compliance — search engines see what users see
- Offline functionality — sitemap works in static exports
