# Static Cache Wrangler - Sitemap Feature Documentation

## Sitemap Generation

Version 2.1.0 introduces automatic sitemap generation based on your cached static files. Unlike traditional WordPress sitemap plugins that generate sitemaps dynamically from the database, Static Cache Wrangler creates a **static sitemap** that reflects exactly what's in your exported site.

### Why File System-Based?

The sitemap generator uses the **file system as the source of truth** rather than the WordPress database. This ensures:

✅ **Accuracy** - The sitemap contains only pages that actually exist in your static export  
✅ **Consistency** - No discrepancies between database content and cached files  
✅ **Portability** - The sitemap works in the static export without WordPress  
✅ **SEO Compliance** - Search engines see exactly what users see  

### What Gets Included

The sitemap automatically includes:

- All cached `index.html` files discovered recursively
- Last modification times from file metadata
- Calculated priorities based on URL depth (homepage = 1.0, deeper pages = 0.4)
- Reasonable change frequencies (homepage = daily, blog = weekly, pages = monthly)
- XSL stylesheet for human-readable browser viewing

### What Doesn't Get Included

The following are intentionally excluded:

- Assets (CSS, JS, images, fonts) - not indexable pages
- Non-HTML files (PDFs, downloads, etc.)
- WordPress admin URLs - not relevant to static site
- External URLs - only same-host pages included

## WP-CLI Commands

### Generate Sitemap

```bash
wp scw sitemap
```

**What it does:**
1. Scans the static directory for all `index.html` files
2. Calculates URLs, priorities, and change frequencies
3. Generates `sitemap.xml` in sitemaps.org format
4. Creates `sitemap.xsl` for browser viewing
5. Places both files in the static directory root

**Example output:**
```
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

### Delete Sitemap

```bash
wp scw sitemap-delete
```

Removes `sitemap.xml` and `sitemap.xsl` from the static directory. Useful when you want to regenerate the sitemap or clean up before export.

## Typical Workflow

### 1. Generate Static Site

```bash
# Enable generation
wp scw enable

# Browse your site or use a crawler to cache pages
# (Future versions will include auto-crawl functionality)

# Check status
wp scw status
```

### 2. Generate Sitemap

```bash
# Create sitemap from cached files
wp scw sitemap
```

### 3. Export Everything

```bash
# Create ZIP with sitemap included
wp scw zip --output=/tmp/mysite.zip
```

### 4. Deploy Static Site

Extract the ZIP and deploy to:
- Amazon S3® / CloudFront®
- Netlify®
- GitHub Pages®
- Any static hosting provider

The sitemap will be available at `/sitemap.xml` automatically.

## Sitemap Priority Calculation

The generator assigns priorities based on URL depth using standard SEO conventions:

| URL Type | Depth | Priority | Example |
|----------|-------|----------|---------|
| Homepage | 0 | 1.0 | `/` |
| Top-level | 1 | 0.8 | `/about/` |
| Second-level | 2 | 0.6 | `/products/widgets/` |
| Deeper | 3+ | 0.4 | `/blog/2025/01/post/` |

## Change Frequency Logic

Default change frequencies are assigned based on URL patterns:

- **Homepage**: `daily` - Frequently updated front page
- **Blog/News**: `weekly` - Content that changes regularly
- **Static Pages**: `monthly` - Relatively stable content

### Customizing Change Frequency

Developers can filter the change frequency per URL:

```php
add_filter('stcw_sitemap_changefreq', function($freq, $path) {
    // Set product pages to update weekly
    if (strpos($path, '/products/') !== false) {
        return 'weekly';
    }
    
    // Set legal pages to rarely change
    if (in_array($path, ['privacy', 'terms', 'legal'])) {
        return 'yearly';
    }
    
    return $freq;
}, 10, 2);
```

## XSL Stylesheet

The generated `sitemap.xsl` makes the sitemap human-readable when viewed in a browser. It includes:

- Clean, modern table layout
- Sortable columns (URL, Last Modified, Change Freq, Priority)
- Color-coded priorities (high = green, medium = orange, low = gray)
- Total URL count
- Responsive design

**Viewing the sitemap:**

Just open `https://your-site.com/sitemap.xml` in any modern browser. The XSL stylesheet will transform the raw XML into a readable HTML table.

## Search Engine Submission

### Google Search Console

1. Log in to [Google Search Console](https://search.google.com/search-console)
2. Select your property
3. Go to Sitemaps → Add a new sitemap
4. Enter: `https://your-site.com/sitemap.xml`
5. Submit

### Bing Webmaster Tools

1. Log in to [Bing Webmaster Tools](https://www.bing.com/webmasters)
2. Select your site
3. Go to Sitemaps → Submit Sitemap
4. Enter: `https://your-site.com/sitemap.xml`
5. Submit

### robots.txt Reference

Add this to your `robots.txt` file:

```
User-agent: *
Allow: /

Sitemap: https://your-site.com/sitemap.xml
```

## Multisite Support

Each site in a multisite network gets its own sitemap:

```
wp-content/cache/stcw_static/
├── site-1/
│   ├── sitemap.xml
│   ├── sitemap.xsl
│   └── index.html
├── site-2/
│   ├── sitemap.xml
│   ├── sitemap.xsl
│   └── index.html
```

Generate sitemaps for specific sites:

```bash
wp scw sitemap --url=site2.example.com
```

## Technical Details

### File Format

The sitemap follows the [sitemaps.org XML format](https://www.sitemaps.org/protocol.html):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="sitemap.xsl"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://example.com/</loc>
    <lastmod>2025-01-15T10:30:00+00:00</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <!-- Additional URLs -->
</urlset>
```

### Performance

- **Scan time**: ~50-100ms per 100 cached files
- **Memory**: ~2MB for sites with 1,000+ pages
- **File size**: ~1KB per 10 URLs in sitemap.xml

### Limitations

- Maximum 50,000 URLs per sitemap (sitemaps.org limit)
- For larger sites, future versions will support sitemap index files
- Only scans for `index.html` files (standard static site structure)

## Troubleshooting

### "No static files found"

**Problem**: The command reports no static files even though you've generated some.

**Solution**: 
```bash
# Verify files exist
wp scw status

# Check directory permissions
ls -la /var/www/html/wp-content/cache/stcw_static/
```

### "Failed to create sitemap.xml"

**Problem**: File write permissions issue.

**Solution**:
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/html/wp-content/cache/
sudo chmod -R 755 /var/www/html/wp-content/cache/
```

### Empty sitemap.xml

**Problem**: Sitemap generated but contains no URLs.

**Solution**: Check that your static files use the standard `index.html` naming convention. Other file structures are not currently supported.

### Sitemap not updating after changes

**Problem**: Made changes to site but sitemap still shows old data.

**Solution**: Regenerate the sitemap:
```bash
wp scw sitemap-delete
wp scw sitemap
```

## Roadmap

Future enhancements planned:

- [ ] Sitemap index file support for sites with 50,000+ URLs
- [ ] Image sitemap generation
- [ ] Video sitemap generation  
- [ ] Automatic sitemap regeneration on file changes
- [ ] GUI interface for sitemap generation (currently CLI-only)
- [ ] Multilingual sitemap support (hreflang)
- [ ] Sitemap ping functionality for search engines

## Developer Hooks

### Filter: stcw_sitemap_changefreq

Customize the change frequency for specific URLs.

**Parameters:**
- `$freq` (string) - Default change frequency
- `$path` (string) - Relative path of the URL

**Example:**
```php
add_filter('stcw_sitemap_changefreq', function($freq, $path) {
    if (strpos($path, '/products/') !== false) {
        return 'weekly';
    }
    return $freq;
}, 10, 2);
```

### Future Hooks (Coming Soon)

- `stcw_sitemap_priority` - Filter URL priority
- `stcw_sitemap_exclude_url` - Exclude specific URLs from sitemap
- `stcw_sitemap_additional_tags` - Add custom XML tags to sitemap entries

## FAQ

**Q: Why not use existing WordPress sitemap plugins?**  
A: WordPress sitemap plugins (like Yoast SEO, Rank Math) generate sitemaps dynamically from the database. When you export a static site, those sitemaps won't work because there's no database or PHP. Static Cache Wrangler creates a true static sitemap that works anywhere.

**Q: Can I use this with XML Sitemap plugins?**  
A: Yes, during development on your WordPress site. But when you export the static version, you'll want to use `wp scw sitemap` to generate a static sitemap for the export.

**Q: Does this affect my live WordPress site's sitemap?**  
A: No. The static sitemap is separate and only lives in the static export directory. Your WordPress site's dynamic sitemap (from plugins) remains unchanged.

**Q: What if I have multiple languages?**  
A: Currently, each language should have its own cached directory. Future versions will support hreflang annotations for multilingual sites.

**Q: Can I customize the XSL stylesheet?**  
A: Yes! The XSL file is plain text. You can modify `sitemap.xsl` directly after generation. Future versions will include hooks for custom stylesheets.

## Examples

### Complete Static Site Generation

```bash
# 1. Enable generation
wp scw enable

# 2. Browse your entire site (manually or with crawler)
# Example with wget:
wget --mirror --convert-links --adjust-extension \
     --page-requisites --no-parent \
     --reject-regex "(wp-admin|wp-login)" \
     https://your-site.com/

# 3. Process pending assets
wp scw process

# 4. Generate sitemap
wp scw sitemap

# 5. Create final ZIP
wp scw zip --output=/tmp/static-site.zip

# 6. Deploy to S3 (example)
aws s3 sync /tmp/static-site/ s3://your-bucket/ --delete
```

### Automated Daily Sitemap Updates

```bash
#!/bin/bash
# /usr/local/bin/update-static-sitemap.sh

cd /var/www/html

# Regenerate sitemap from cached files
wp scw sitemap-delete --allow-root
wp scw sitemap --allow-root

echo "Sitemap updated: $(date)"
```

Add to crontab:
```
0 2 * * * /usr/local/bin/update-static-sitemap.sh
```

## Support

For issues or questions about sitemap generation:

- [GitHub Issues](https://github.com/derickschaefer/static-cache-wrangler/issues)
- [Documentation](https://moderncli.dev/code/static-cache-wrangler/)
