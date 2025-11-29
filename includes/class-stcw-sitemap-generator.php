<?php
/**
 * Sitemap generation for static cache
 *
 * Generates sitemap.xml and sitemap.xsl files based on cached static files.
 * Uses file system as source of truth rather than WordPress database to ensure
 * sitemap accurately reflects what's actually in the static export.
 *
 * @package StaticCacheWrangler
 * @since 2.1.0
 */

if (!defined('ABSPATH')) exit;

class STCW_Sitemap_Generator {
    
    /**
     * URL helper instance
     * @var STCW_URL_Helper
     */
    private $url_helper;
    
    /**
     * Base URL for the static site
     * @var string
     */
    private $base_url;
    
    /**
     * Constructor - initialize dependencies
     *
     * @param string $custom_url Optional custom URL for deployment target
     */
    public function __construct($custom_url = '') {
        $this->url_helper = new STCW_URL_Helper();
        
        // Use custom URL if provided, otherwise use WordPress site URL
        if (!empty($custom_url)) {
            $this->base_url = untrailingslashit($custom_url);
        } else {
            $this->base_url = $this->url_helper->site_url();
        }
    }
    
    /**
     * Generate sitemap.xml and sitemap.xsl from cached files
     *
     * Scans the static directory for index.html files and creates
     * a proper XML sitemap with accompanying XSL stylesheet.
     *
     * @return array Results with success status and file paths
     */
    public function generate() {
        $static_dir = STCW_Core::get_static_dir();
        
        // Verify directory exists
        if (!is_dir($static_dir)) {
            return [
                'success' => false,
                'message' => 'Static directory does not exist. Generate some static files first.',
                'files' => []
            ];
        }
        
        // Scan for URLs from cached files
        $urls = $this->scan_cached_files($static_dir);
        
        if (empty($urls)) {
            return [
                'success' => false,
                'message' => 'No static files found. Browse your site to generate cached pages.',
                'files' => []
            ];
        }
        
        // Generate sitemap.xml
        $sitemap_path = $static_dir . 'sitemap.xml';
        $sitemap_result = $this->create_sitemap_xml($urls, $sitemap_path);
        
        if (!$sitemap_result) {
            return [
                'success' => false,
                'message' => 'Failed to create sitemap.xml',
                'files' => []
            ];
        }
        
        // Generate sitemap.xsl
        $xsl_path = $static_dir . 'sitemap.xsl';
        $xsl_result = $this->create_sitemap_xsl($xsl_path);
        
        if (!$xsl_result) {
            stcw_log_debug('Warning: Failed to create sitemap.xsl (non-critical)');
        }
        
        return [
            'success' => true,
            'message' => sprintf('Successfully generated sitemap with %d URLs', count($urls)),
            'files' => array_filter([
                'sitemap' => $sitemap_path,
                'stylesheet' => $xsl_result ? $xsl_path : null
            ]),
            'url_count' => count($urls)
        ];
    }
    
    /**
     * Scan cached directory for HTML files and extract URLs
     *
     * Recursively walks the static directory to find all index.html files
     * and derives their public URLs and modification times.
     *
     * @param string $static_dir Path to static files directory
     * @return array Array of URL data with 'loc', 'lastmod', 'priority'
     */
    private function scan_cached_files($static_dir) {
        $urls = [];
        
        // Normalize static directory path for comparison
        $static_dir_normalized = trailingslashit($static_dir);
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($static_dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                // Only process index.html files (skip assets, other files)
                if (!$file->isFile() || $file->getFilename() !== 'index.html') {
                    continue;
                }
                
                $file_path = $file->getPathname();
                
                // Get relative path from static directory
                $relative_path = str_replace($static_dir_normalized, '', dirname($file_path) . '/');
                $relative_path = trim($relative_path, '/');
                
                // Skip assets directory and cache directory entries
                if (strpos($relative_path, 'assets') === 0) {
                    continue;
                }
                
                // Skip entries that contain full filesystem paths (shouldn't happen but safety check)
                if (strpos($relative_path, '/var/') !== false || strpos($relative_path, 'wp-content') !== false) {
                    continue;
                }
                
                // Construct the URL using base_url
                if (empty($relative_path)) {
                    // Root index.html = homepage
                    $url = trailingslashit($this->base_url);
                } else {
                    // Other pages - ensure we're using base_url
                    $url = trailingslashit($this->base_url) . trailingslashit($relative_path);
                }
                
                // Get file modification time
                $lastmod = date('Y-m-d\TH:i:s+00:00', $file->getMTime());
                
                // Calculate priority (homepage = 1.0, others based on depth)
                $depth = substr_count($relative_path, '/');
                $priority = $this->calculate_priority($depth, $relative_path);
                
                $urls[] = [
                    'loc' => $url,
                    'lastmod' => $lastmod,
                    'priority' => $priority,
                    'changefreq' => $this->determine_changefreq($relative_path)
                ];
            }
        } catch (Exception $e) {
            stcw_log_debug('Error scanning cached files: ' . $e->getMessage());
            return [];
        }
        
        // Sort by priority (descending) then by URL (ascending)
        usort($urls, function($a, $b) {
            $priority_cmp = $b['priority'] <=> $a['priority'];
            if ($priority_cmp !== 0) {
                return $priority_cmp;
            }
            return strcmp($a['loc'], $b['loc']);
        });
        
        return $urls;
    }
    
    /**
     * Calculate priority for URL based on depth and path
     *
     * Uses standard sitemap priority conventions:
     * - Homepage: 1.0
     * - Top-level pages: 0.8
     * - Second-level pages: 0.6
     * - Deeper pages: 0.4
     *
     * @param int $depth URL depth (number of slashes)
     * @param string $path Relative path
     * @return string Priority value (0.0 - 1.0)
     */
    private function calculate_priority($depth, $path) {
        // Homepage gets highest priority
        if (empty($path) || $path === '/') {
            return '1.0';
        }
        
        // Top-level pages (e.g., /about/)
        if ($depth === 0) {
            return '0.8';
        }
        
        // Second-level pages (e.g., /products/widgets/)
        if ($depth === 1) {
            return '0.6';
        }
        
        // Deeper pages
        return '0.4';
    }
    
    /**
     * Determine change frequency based on path
     *
     * Provides reasonable defaults based on common WordPress structures.
     * Can be filtered via 'stcw_sitemap_changefreq' hook.
     *
     * @param string $path Relative path
     * @return string Change frequency (daily, weekly, monthly, yearly)
     */
    private function determine_changefreq($path) {
        // Homepage typically changes frequently
        if (empty($path) || $path === '/') {
            $freq = 'daily';
        }
        // Blog posts/archives might change weekly
        elseif (preg_match('#/(blog|news|posts|archives?)/#i', $path)) {
            $freq = 'weekly';
        }
        // Most other pages change monthly
        else {
            $freq = 'monthly';
        }
        
        /**
         * Filter the change frequency for a URL
         *
         * @param string $freq Change frequency
         * @param string $path Relative path
         */
        return apply_filters('stcw_sitemap_changefreq', $freq, $path);
    }
    
    /**
     * Create sitemap.xml file
     *
     * Generates properly formatted XML sitemap conforming to sitemaps.org protocol.
     * Includes XSL stylesheet reference for browser viewing.
     *
     * @param array $urls Array of URL data
     * @param string $output_path Full path to save sitemap.xml
     * @return bool True on success, false on failure
     */
    private function create_sitemap_xml($urls, $output_path) {
        // Start XML document
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?xml-stylesheet type="text/xsl" href="sitemap.xsl"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Add each URL
        foreach ($urls as $url_data) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . esc_url($url_data['loc']) . "</loc>\n";
            $xml .= "    <lastmod>" . esc_xml($url_data['lastmod']) . "</lastmod>\n";
            $xml .= "    <changefreq>" . esc_xml($url_data['changefreq']) . "</changefreq>\n";
            $xml .= "    <priority>" . esc_xml($url_data['priority']) . "</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= "</urlset>\n";
        
        // Initialize WP_Filesystem
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        // If WP_Filesystem method is not 'direct', re-initialize with direct method
        if ($wp_filesystem && !($wp_filesystem instanceof WP_Filesystem_Direct)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
            $wp_filesystem = new WP_Filesystem_Direct(null);
        }
        
        // Define FS_CHMOD_FILE if not already defined (WP-CLI context)
        if (!defined('FS_CHMOD_FILE')) {
            define('FS_CHMOD_FILE', 0644);
        }
        
        // Save using WP_Filesystem
        if ($wp_filesystem) {
            $result = $wp_filesystem->put_contents($output_path, $xml, FS_CHMOD_FILE);
            if ($result) {
                stcw_log_debug('Sitemap generated: ' . $output_path);
                return true;
            }
        }
        
        stcw_log_debug('Failed to write sitemap.xml');
        return false;
    }
    
    /**
     * Create sitemap.xsl stylesheet
     *
     * Generates an XSL stylesheet that makes the sitemap human-readable
     * when viewed in a web browser.
     *
     * @param string $output_path Full path to save sitemap.xsl
     * @return bool True on success, false on failure
     */
    private function create_sitemap_xsl($output_path) {
        // Modern, clean XSL stylesheet for sitemap visualization
        $xsl = '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" 
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
  
  <xsl:output method="html" encoding="UTF-8" indent="yes"/>
  
  <xsl:template match="/">
    <html>
      <head>
        <title>XML Sitemap</title>
        <style>
          body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f5f5f5;
            color: #333;
          }
          h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 30px;
          }
          .info {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          }
          table {
            width: 100%;
            background: #fff;
            border-collapse: collapse;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
          }
          th {
            background: #3498db;
            color: #fff;
            text-align: left;
            padding: 12px;
            font-weight: 600;
          }
          td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
          }
          tr:hover {
            background: #f8f9fa;
          }
          a {
            color: #3498db;
            text-decoration: none;
          }
          a:hover {
            text-decoration: underline;
          }
          .priority-high { color: #27ae60; font-weight: bold; }
          .priority-medium { color: #f39c12; }
          .priority-low { color: #7f8c8d; }
        </style>
      </head>
      <body>
        <h1>XML Sitemap</h1>
        
        <div class="info">
          <p><strong>Number of URLs:</strong> <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></p>
          <p>This is a static sitemap generated by Static Cache Wrangler.</p>
        </div>
        
        <table>
          <thead>
            <tr>
              <th style="width: 50%;">URL</th>
              <th style="width: 20%;">Last Modified</th>
              <th style="width: 15%;">Change Frequency</th>
              <th style="width: 15%;">Priority</th>
            </tr>
          </thead>
          <tbody>
            <xsl:for-each select="sitemap:urlset/sitemap:url">
              <tr>
                <td>
                  <a>
                    <xsl:attribute name="href">
                      <xsl:value-of select="sitemap:loc"/>
                    </xsl:attribute>
                    <xsl:value-of select="sitemap:loc"/>
                  </a>
                </td>
                <td>
                  <xsl:value-of select="substring(sitemap:lastmod, 1, 10)"/>
                </td>
                <td>
                  <xsl:value-of select="sitemap:changefreq"/>
                </td>
                <td>
                  <xsl:attribute name="class">
                    <xsl:choose>
                      <xsl:when test="sitemap:priority &gt; 0.7">priority-high</xsl:when>
                      <xsl:when test="sitemap:priority &gt; 0.4">priority-medium</xsl:when>
                      <xsl:otherwise>priority-low</xsl:otherwise>
                    </xsl:choose>
                  </xsl:attribute>
                  <xsl:value-of select="sitemap:priority"/>
                </td>
              </tr>
            </xsl:for-each>
          </tbody>
        </table>
      </body>
    </html>
  </xsl:template>
  
</xsl:stylesheet>';
        
        // Initialize WP_Filesystem
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        // If WP_Filesystem method is not 'direct', re-initialize with direct method
        if ($wp_filesystem && !($wp_filesystem instanceof WP_Filesystem_Direct)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
            $wp_filesystem = new WP_Filesystem_Direct(null);
        }
        
        // Define FS_CHMOD_FILE if not already defined (WP-CLI context)
        if (!defined('FS_CHMOD_FILE')) {
            define('FS_CHMOD_FILE', 0644);
        }
        
        // Save using WP_Filesystem
        if ($wp_filesystem) {
            $result = $wp_filesystem->put_contents($output_path, $xsl, FS_CHMOD_FILE);
            if ($result) {
                stcw_log_debug('Sitemap XSL generated: ' . $output_path);
                return true;
            }
        }
        
        stcw_log_debug('Failed to write sitemap.xsl');
        return false;
    }
    
    /**
     * Delete sitemap files
     *
     * Removes sitemap.xml and sitemap.xsl from static directory.
     * Used during clear operations or sitemap regeneration.
     *
     * @return bool True if files were deleted, false otherwise
     */
    public function delete_sitemap() {
        $static_dir = STCW_Core::get_static_dir();
        $sitemap_path = $static_dir . 'sitemap.xml';
        $xsl_path = $static_dir . 'sitemap.xsl';
        
        // Initialize WP_Filesystem with direct method
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        // Force direct method if needed
        if ($wp_filesystem && !($wp_filesystem instanceof WP_Filesystem_Direct)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
            $wp_filesystem = new WP_Filesystem_Direct(null);
        }
        
        $deleted = false;
        
        if ($wp_filesystem) {
            if ($wp_filesystem->exists($sitemap_path)) {
                $wp_filesystem->delete($sitemap_path);
                $deleted = true;
                stcw_log_debug('Deleted sitemap.xml');
            }
            
            if ($wp_filesystem->exists($xsl_path)) {
                $wp_filesystem->delete($xsl_path);
                $deleted = true;
                stcw_log_debug('Deleted sitemap.xsl');
            }
        }
        
        return $deleted;
    }
}
