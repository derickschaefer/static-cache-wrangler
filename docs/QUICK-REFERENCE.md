# Static Cache Wrangler - Sitemap Quick Reference

## Commands

```bash
wp scw sitemap          # Generate sitemap.xml and sitemap.xsl
wp scw sitemap-delete   # Remove sitemap files
```

## Files Generated

```
/wp-content/cache/stcw_static/
├── sitemap.xml         # XML sitemap (sitemaps.org format)
└── sitemap.xsl         # XSL stylesheet (browser viewing)
```

## Quick Start

```bash
# 1. Generate static cache
wp scw enable
# Browse your site...

# 2. Create sitemap
wp scw sitemap

# 3. View in browser
open https://your-site.com/sitemap.xml
```

## Priority Values

| URL Type | Example | Priority |
|----------|---------|----------|
| Homepage | `/` | 1.0 |
| Top-level | `/about/` | 0.8 |
| Second-level | `/products/widgets/` | 0.6 |
| Deeper | `/blog/2025/01/post/` | 0.4 |

## Change Frequencies

| URL Pattern | Frequency |
|-------------|-----------|
| Homepage | daily |
| Blog/news | weekly |
| Static pages | monthly |

## Customization

```php
// Change frequency for specific URLs
add_filter('stcw_sitemap_changefreq', function($freq, $path) {
    if (strpos($path, '/products/') !== false) {
        return 'weekly';
    }
    return $freq;
}, 10, 2);
```

## Workflow

```bash
# Complete static site generation
wp scw enable               # Enable caching
# Browse site or crawl...
wp scw process             # Process assets
wp scw sitemap             # Generate sitemap
wp scw zip                 # Create ZIP export
# Deploy to S3/Netlify...
```

## View Sitemap

**In Browser:**
```
https://your-site.com/sitemap.xml
```
The XSL stylesheet automatically transforms it to a readable table.

**Raw XML:**
```bash
curl https://your-site.com/sitemap.xml
```

## Submit to Search Engines

**Google Search Console:**
1. https://search.google.com/search-console
2. Sitemaps → Add sitemap
3. Enter: `sitemap.xml`

**Bing Webmaster Tools:**
1. https://www.bing.com/webmasters
2. Sitemaps → Submit
3. Enter: `sitemap.xml`

## robots.txt

```
User-agent: *
Allow: /

Sitemap: https://your-site.com/sitemap.xml
```

## Multisite

Each site gets its own sitemap:

```bash
wp scw sitemap --url=site1.example.com
wp scw sitemap --url=site2.example.com
```

## Troubleshooting

**"No static files found"**
```bash
wp scw enable
# Browse site first to generate cache
```

**"Failed to create sitemap.xml"**
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/html/wp-content/cache/
sudo chmod -R 755 /var/www/html/wp-content/cache/
```

**Regenerate sitemap:**
```bash
wp scw sitemap-delete
wp scw sitemap
```

## Performance

| Site Size | Files | Time | Memory |
|-----------|-------|------|--------|
| Small | 50 | ~25ms | 1MB |
| Medium | 500 | ~200ms | 2MB |
| Large | 5,000 | ~2s | 8MB |

## XML Format

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
</urlset>
```

## What's Included

✅ All cached `index.html` files  
✅ Last modification times  
✅ Calculated priorities  
✅ Change frequencies  
✅ XSL stylesheet  

## What's Excluded

❌ Assets (CSS, JS, images)  
❌ Non-HTML files  
❌ WordPress admin URLs  
❌ External URLs  

## Key Benefits

✅ **Works without WordPress** - True static sitemap  
✅ **SEO compliant** - Follows sitemaps.org protocol  
✅ **Portable** - Deploy to S3, Netlify, anywhere  
✅ **Accurate** - Only includes actual cached files  
✅ **Browser viewable** - XSL makes XML readable  

## Version

Feature added in: **Static Cache Wrangler 2.1.0**

## Documentation

Full docs: `docs/SITEMAP.md`

## Support

- GitHub: https://github.com/derickschaefer/static-cache-wrangler
- Docs: https://moderncli.dev/code/static-cache-wrangler/
