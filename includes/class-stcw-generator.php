<?php
/**
 * HTML generation and output buffering
 *
 * Captures WordPress output, processes HTML, extracts assets,
 * and saves static versions of pages.
 *
 * @package StaticCacheWrangler
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class STCW_Generator {
    
    /**
     * Asset handler instance
     * @var STCW_Asset_Handler
     */
    private $asset_handler;
    
    /**
     * URL helper instance
     * @var STCW_URL_Helper
     */
    private $url_helper;
    
    /**
     * Constructor - initialize dependencies
     */
    public function __construct() {
        $this->asset_handler = new STCW_Asset_Handler();
        $this->url_helper = new STCW_URL_Helper();
    }
    
    /**
     * Remove WordPress-specific meta tags from wp_head()
     *
     * Prevents unnecessary WordPress metadata from appearing in static HTML.
     * Uses WordPress's native remove_action() for clean, performant removal.
     */
    private function remove_wordpress_meta_tags() {
        // Remove RSD (Really Simple Discovery) link for XML-RPC
        remove_action('wp_head', 'rsd_link');
        
        // Remove Windows Live Writer manifest link
        remove_action('wp_head', 'wlwmanifest_link');
        
        // Remove shortlink (uses query strings incompatible with static structure)
        remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
        
        // Remove WordPress generator meta tag
        remove_action('wp_head', 'wp_generator');
        
        // Remove REST API link tag
        remove_action('wp_head', 'rest_output_link_wp_head', 10, 0);
        
        // Remove oEmbed discovery links
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        
        // Remove REST API link from HTTP headers
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
        
        // Allow developers to remove additional WordPress actions
        do_action('stcw_remove_wp_head_tags');
    }
    
    /**
     * Start output buffering for current request
     *
     * Determines if page should be cached and starts capture
     */
    public function start_output() {
        // Don't generate static files if disabled
        if (!STCW_Core::is_enabled()) {
            return;
        }
        
        // Don't cache admin, logged-in users, special pages, or non-GET requests
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : 'GET';
        
        if (
            is_admin()
            || is_user_logged_in()
            || is_404()
            || is_search()
            || is_preview()
            || is_feed()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || is_trackback()
            || is_comment_feed()
            || $request_method !== 'GET'
        ) {
            return;
        }
        
        // Remove WordPress-specific meta tags before generating output
        // This prevents them from being added in the first place
        $this->remove_wordpress_meta_tags();
        
        // Start output buffering with callback
        ob_start([$this, 'save_output']);
    }
    
    /**
     * Save output buffer to static file
     *
     * Callback for ob_start() - processes and saves HTML
     *
     * @param string $output HTML output from WordPress
     * @return string Original output (unchanged for display)
     */
    public function save_output($output) {
        $static_file = $this->url_helper->get_static_file_path();
        $static_dir = dirname($static_file);
        
        // Create directory if it doesn't exist
        if (!is_dir($static_dir)) {
            wp_mkdir_p($static_dir);
        }

        $static_output = $output;
        
        // Process assets asynchronously if enabled
        if (STCW_ASYNC_ASSETS) {
            $static_output = $this->rewrite_asset_paths($static_output);
            $assets = $this->extract_asset_urls($output);
            $this->asset_handler->queue_asset_downloads($assets);
        }
        
        // Rewrite internal links to relative paths
        $static_output = $this->rewrite_links($static_output);
        
        // Add metadata and clean up WordPress-specific tags
        $static_output = $this->process_static_html($static_output);

        // Initialize WP_Filesystem
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // Save static file using WP_Filesystem
        if ($wp_filesystem) {
            $wp_filesystem->put_contents($static_file, $static_output, FS_CHMOD_FILE);
        } else {
            stcw_log_debug('Failed to initialize WP_Filesystem for saving static file');
        }

        // Return original output unchanged for browser display
        return $output;
    }
    
    /**
     * Extract asset URLs from HTML
     *
     * Finds CSS, JS, images, fonts, and other assets to download.
     * Now includes processing of inline <style> blocks for background images.
     *
     * @param string $html HTML content
     * @return array Array of asset URLs
     */
    private function extract_asset_urls($html) {
        $assets = [];
        
        // CSS files - match various link tag patterns
        preg_match_all('#<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\'][^>]*>#i', $html, $css1);
        preg_match_all('#<link[^>]+href=["\']([^"\']+\.css[^"\']*)["\'][^>]+rel=["\']stylesheet["\'][^>]*>#i', $html, $css2);
        preg_match_all('#<link[^>]+href=["\']([^"\']+\.css[^"\']*)["\'][^>]*>#i', $html, $css3);
        $assets = array_merge($assets, $css1[1], $css2[1], $css3[1]);
        
        // JavaScript files
        preg_match_all('#<script[^>]+src=["\']([^"\']+\.js[^"\']*)["\'][^>]*>#i', $html, $js_matches);
        $assets = array_merge($assets, $js_matches[1]);
        
        // Images
        preg_match_all('#<img[^>]+src=["\']([^"\']+)["\'][^>]*>#i', $html, $img_matches);
        $assets = array_merge($assets, $img_matches[1]);
        
        // Srcsets (responsive images)
        preg_match_all('#srcset=["\']([^"\']+)["\']#i', $html, $srcset_matches);
        foreach ($srcset_matches[1] as $srcset) {
            preg_match_all('#(https?://[^\s,]+|/[^\s,]+)#i', $srcset, $urls);
            $assets = array_merge($assets, $urls[1]);
        }
        
        // Icons (favicons, apple-touch-icons, etc.)
        preg_match_all('#<link[^>]+href=["\']([^"\']+\.(ico|png|svg|gif|jpg|jpeg|webp))["\'][^>]*>#i', $html, $icon_matches);
        $assets = array_merge($assets, $icon_matches[1]);
        
        // Background images in inline style attributes
        preg_match_all('#style=["\'][^"\']*background(?:-image)?:\s*url\(["\']?([^"\')]+)["\']?\)[^"\']*["\']#i', $html, $bg_matches);
        $assets = array_merge($assets, $bg_matches[1]);
        
        // Background images in <style> blocks (inline CSS)
        // This catches dynamically generated CSS from themes/plugins that reference uploads
        preg_match_all('#<style[^>]*>(.*?)</style>#is', $html, $style_blocks);
        if (!empty($style_blocks[1])) {
            foreach ($style_blocks[1] as $css_content) {
                // Extract all url() references from CSS
                preg_match_all('#url\s*\(\s*["\']?([^"\')]+)["\']?\s*\)#i', $css_content, $css_urls);
                if (!empty($css_urls[1])) {
                    $assets = array_merge($assets, $css_urls[1]);
                }
            }
        }
        
        // Filter to only same-host assets
        $filtered = [];
        foreach ($assets as $url) {
            $url = html_entity_decode(trim($url));
            if (empty($url)) continue;
            
            $abs_url = $this->url_helper->absolute_url($url);
            
            if ($this->url_helper->is_same_host($abs_url)) {
                $filtered[] = $abs_url;
            }
        }
        
        return array_unique($filtered);
    }
    
    /**
     * Rewrite asset paths to relative local paths
     *
     * Changes absolute URLs to relative paths pointing to assets directory.
     * Now includes rewriting of inline <style> blocks.
     *
     * @param string $html HTML content
     * @return string HTML with rewritten asset paths
     */
    private function rewrite_asset_paths($html) {
        $depth = $this->url_helper->get_current_depth();
        $assets_path = str_repeat('../', $depth) . 'assets/';
        
        // Rewrite <link> tags (CSS and icons)
        $html = preg_replace_callback(
            '#<link([^>]*?)href=["\']([^"\']+)["\']([^>]*)>#i',
            function($m) use ($assets_path) {
                $href = html_entity_decode($m[2]);
                $rel = '';
                if (preg_match('/\brel=["\']([^"\']+)["\']/i', $m[1] . ' ' . $m[3], $rm)) {
                    $rel = strtolower($rm[1]);
                }
                $is_css = preg_match('/\.css(\?|$)/i', $href);
                $is_icon = preg_match('/\.(?:ico|png|svg|gif|jpg|jpeg|webp|avif)(\?|$)/i', $href)
                            || strpos($rel, 'icon') !== false;
                
                if (($is_css || $is_icon) && $this->url_helper->is_same_host($href)) {
                    $filename = $this->url_helper->filename_from_url($href);
                    return '<link' . $m[1] . 'href="' . $assets_path . esc_attr($filename) . '"' . $m[3] . '>';
                }
                return $m[0];
            },
            $html
        );

        // Rewrite <script> tags
        $html = preg_replace_callback(
            '#<script([^>]*?)src=["\']([^"\']+\.js[^"\']*)["\']([^>]*)></script>#i',
            function($m) use ($assets_path) {
                $src = html_entity_decode($m[2]);
                if ($this->url_helper->is_same_host($src)) {
                    $filename = $this->url_helper->filename_from_url($src);
                    return '<script' . $m[1] . 'src="' . $assets_path . esc_attr($filename) . '"' . $m[3] . '></script>';
                }
                return $m[0];
            },
            $html
        );

        // Rewrite <img> tags (including srcset)
        $html = preg_replace_callback(
            '#<img([^>]*?)src=["\']([^"\']+)["\']([^>]*)>#i',
            function($m) use ($assets_path) {
                $src = html_entity_decode($m[2]);
                if ($this->url_helper->is_same_host($src)) {
                    $filename = $this->url_helper->filename_from_url($src);
                    $new = '<img' . $m[1] . 'src="' . $assets_path . esc_attr($filename) . '"' . $m[3] . '>';
                    
                    // Handle srcset attribute
                    if (preg_match('/\ssrcset=["\']([^"\']+)["\']/i', $m[0], $sm)) {
                        $srcset = $sm[1];
                        $new_srcset = preg_replace_callback(
                            '/\s*([^,\s]+)\s+([0-9]+[wx])?/i',
                            function($mm) use ($assets_path) {
                                $u = $mm[1];
                                if ($this->url_helper->is_same_host($u)) {
                                    $fn = $this->url_helper->filename_from_url($u);
                                    return ' ' . $assets_path . $fn . (isset($mm[2]) ? ' ' . $mm[2] : '');
                                }
                                return $mm[0];
                            },
                            $srcset
                        );
                        $new = preg_replace('/\ssrcset=["\'][^"\']+["\']/', ' srcset="' . trim($new_srcset) . '"', $new);
                    }
                    return $new;
                }
                return $m[0];
            },
            $html
        );

        // Rewrite video/source tags
        $html = preg_replace_callback(
            '#<(source|video)([^>]*?)(src|poster)=["\']([^"\']+)["\']([^>]*)>#i',
            function($m) use ($assets_path) {
                $url = html_entity_decode($m[4]);
                if ($this->url_helper->is_same_host($url)) {
                    $fn = $this->url_helper->filename_from_url($url);
                    return '<' . $m[1] . $m[2] . $m[3] . '="' . $assets_path . esc_attr($fn) . '"' . $m[5] . '>';
                }
                return $m[0];
            },
            $html
        );

        // Rewrite meta tags (og:image, twitter:image)
        $html = preg_replace_callback(
            '#<meta([^>]+)(property|name)=[\'"](og:image|twitter:image)[\'"]([^>]+)content=[\'"]([^\'"]+)[\'"]([^>]*)>#i',
            function($m) use ($assets_path) {
                $url = html_entity_decode($m[5]);
                if ($this->url_helper->is_same_host($url)) {
                    $fn = $this->url_helper->filename_from_url($url);
                    return '<meta' . $m[1] . $m[2] . '="' . $m[3] . '"' . $m[4] . 'content="' . $assets_path . esc_attr($fn) . '"' . $m[6] . '>';
                }
                return $m[0];
            },
            $html
        );

        // Rewrite inline <style> blocks for background-image urls
        // This handles dynamically generated CSS from themes/plugins
        $html = preg_replace_callback(
            '#<style([^>]*)>(.*?)</style>#is',
            function($m) use ($assets_path) {
                $style_attrs = $m[1];
                $css_content = $m[2];
                
                // Rewrite url() references in CSS content
                $css_content = preg_replace_callback(
                    '#url\s*\(\s*["\']?([^"\')]+)["\']?\s*\)#i',
                    function($url_match) use ($assets_path) {
                        $url = trim($url_match[1], " \t\n\r\0\x0B'\"");
                        
                        // Skip data URIs and empty URLs
                        if (empty($url) || stripos($url, 'data:') === 0) {
                            return $url_match[0];
                        }
                        
                        // Convert to absolute URL for checking
                        $abs_url = $this->url_helper->absolute_url($url);
                        
                        // Only rewrite same-host URLs
                        if ($this->url_helper->is_same_host($abs_url)) {
                            $filename = $this->url_helper->filename_from_url($abs_url);
                            return 'url(' . $assets_path . $filename . ')';
                        }
                        
                        return $url_match[0];
                    },
                    $css_content
                );
                
                return '<style' . $style_attrs . '>' . $css_content . '</style>';
            },
            $html
        );

        return $html;
    }
    
    /**
     * Rewrite internal links to relative static file paths
     *
     * Converts WordPress URLs to relative HTML file references
     *
     * @param string $html HTML content
     * @return string HTML with rewritten links
     */
    private function rewrite_links($html) {
        $current_depth = $this->url_helper->get_current_depth();
        $depth_prefix = str_repeat('../', $current_depth);
        
        return preg_replace_callback(
            '#<a([^>]+)href=["\']([^"\']+)["\']#i',
            function($m) use ($depth_prefix) {
                $href = html_entity_decode($m[2]);

                // Skip special links
                if (
                    stripos($href, 'mailto:') === 0 ||
                    stripos($href, 'tel:') === 0 ||
                    stripos($href, 'javascript:') === 0 ||
                    stripos($href, '#') === 0 ||
                    !$this->url_helper->is_same_host($href)
                ) {
                    return $m[0];
                }

                $abs = $this->url_helper->absolute_url($href);
                $parsed = wp_parse_url($abs);

                $path = $parsed['path'] ?? '/';
                $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                $frag  = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

                // Root or homepage
                if ($path === '/' || $path === '') {
                    $new_href = $depth_prefix . 'index.html';
                } else {
                    $relative = ltrim($path, '/');
                    if (substr($relative, -1) !== '/') {
                        $relative .= '/';
                    }
                    $new_href = $depth_prefix . $relative . 'index.html';
                }

                return '<a' . $m[1] . 'href="' . $new_href . $query . $frag . '"';
            },
            $html
        );
    }
    
    /**
     * Process HTML for static output
     *
     * Adds metadata, removes WordPress-specific tags as safety net.
     * Primary removal happens via remove_action() in remove_wordpress_meta_tags(),
     * but this provides a regex-based safety net for any tags that slip through.
     *
     * @param string $html HTML content
     * @return string Processed HTML
     */
    private function process_static_html($html) {
        $timestamp = current_time('Y-m-d H:i:s');
        $comment = "\n<!-- Static version generated: $timestamp -->\n";
        $comment .= "<!-- To use offline: Extract ZIP and open index.html in browser -->\n";
        
        // Safety net: Remove WordPress-specific tags via regex
        // These should already be removed by remove_wordpress_meta_tags(),
        // but this catches any edge cases or plugin-added tags
        
        // Remove REST API and alternate links
        $html = preg_replace('#<link[^>]+rel=["\'](?:https://api\.w\.org/|alternate)["\'][^>]*>#i', '', $html);
        
        // Remove RSD (Really Simple Discovery) links
        $html = preg_replace('#<link[^>]+rel=["\']EditURI["\'][^>]*>#i', '', $html);
        
        // Remove Windows Live Writer manifest links
        $html = preg_replace('#<link[^>]+rel=["\']wlwmanifest["\'][^>]*>#i', '', $html);
        
        // Remove shortlink tags
        $html = preg_replace('#<link[^>]+rel=["\']shortlink["\'][^>]*>#i', '', $html);
        
        // Remove WordPress generator meta tags
        $html = preg_replace('#<meta[^>]+name=["\']generator["\'][^>]*>#i', '', $html);
        
        // Remove emoji-related scripts
        $html = preg_replace('#<script[^>]*>.*?wp-emoji-release\.min\.js.*?</script>#is', '', $html);
        
        // Remove WordPress-specific script attributes (optional - for cleaner HTML)
        $html = preg_replace('/\s+data-wp-strategy=["\'][^"\']*["\']/', '', $html);
        
        // Allow developers to add custom HTML processing
        $html = apply_filters('stcw_process_static_html', $html);
        
        // Add comment before closing body tag
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $comment . '</body>', $html);
        } else {
            $html .= $comment;
        }
        
        return $html;
    }
}
