<?php
/**
 * HTML generation and output buffering
 * 
 * IMPROVEMENTS IN THIS VERSION:
 * - Added try-catch blocks around all file operations
 * - Comprehensive documentation for complex regex patterns
 * - Better error context in log messages
 * - Graceful handling of edge cases
 * 
 * @package StaticCacheGenerator
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class SCG_Generator {
    
    private $asset_handler;
    private $url_helper;
    
    public function __construct() {
        $this->asset_handler = new SCG_Asset_Handler();
        $this->url_helper = new SCG_URL_Helper();
    }
    
    /**
     * Start output buffering for current request
     * 
     * Hooks into 'wp' action (after WordPress is fully loaded but before
     * template rendering). Uses output buffering to capture HTML and process
     * it before sending to browser.
     * 
     * Excludes:
     * - Admin requests
     * - Logged-in users (to avoid caching personalized content)
     * - Special WordPress pages (search, 404, feeds, etc.)
     * - Non-GET requests
     * 
     * @return void
     */
    public function start_output() {
        if (!SCG_Core::is_enabled()) {
            return;
        }
        
        // Don't cache these request types
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
            || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET'
        ) {
            return;
        }
        
        // Start buffering with our callback
        ob_start([$this, 'save_output']);
    }
    
    /**
     * Output buffer callback - processes and saves HTML
     * 
     * Called automatically by PHP when output buffer ends (page fully rendered).
     * This is where the magic happens:
     * 1. Extract all asset URLs from HTML
     * 2. Queue assets for download
     * 3. Rewrite asset paths to local references
     * 4. Rewrite internal links to static HTML paths
     * 5. Save processed HTML to file
     * 6. Return original HTML to browser (user sees normal WordPress)
     * 
     * @param string $output The complete rendered HTML page
     * @return string Original output (unchanged for browser)
     */
    public function save_output($output) {
        try {
            $static_file = $this->url_helper->get_static_file_path();
            $static_dir = dirname($static_file);
            
            // Ensure directory exists
            if (!is_dir($static_dir)) {
                wp_mkdir_p($static_dir);
            }

            $static_output = $output;
            
            // If async processing enabled, queue assets for background download
            if (SCG_ASYNC_ASSETS) {
                try {
                    // Rewrite paths first so static file has correct references
                    $static_output = $this->rewrite_asset_paths($static_output);
                    
                    // Extract and queue all assets
                    $assets = $this->extract_asset_urls($output);
                    $this->asset_handler->queue_asset_downloads($assets);
                } catch (Exception $e) {
                    error_log("[SCG] Error in async asset processing: " . $e->getMessage());
                    // Continue with original output
                    $static_output = $output;
                }
            }
            
            // Rewrite internal links for static navigation
            try {
                $static_output = $this->rewrite_links($static_output);
            } catch (Exception $e) {
                error_log("[SCG] Error rewriting links: " . $e->getMessage());
            }
            
            // Final cleanup and metadata injection
            try {
                $static_output = $this->process_static_html($static_output);
            } catch (Exception $e) {
                error_log("[SCG] Error in final HTML processing: " . $e->getMessage());
            }

            // Write to disk
            $bytes = file_put_contents($static_file, $static_output);
            if ($bytes === false) {
                error_log("[SCG] Failed to write static file: $static_file");
            }

        } catch (Exception $e) {
            error_log("[SCG] Critical error in save_output: " . $e->getMessage());
        }

        // Always return original output to browser
        return $output;
    }
    
    /**
     * Extract all asset URLs from HTML
     * 
     * Uses multiple regex patterns to find:
     * - CSS files (<link rel="stylesheet">)
     * - JavaScript files (<script src="">)
     * - Images (<img src=""> and srcset="")
     * - Icons (favicon, apple-touch-icon, etc.)
     * - Background images in inline styles
     * 
     * Why multiple patterns?
     * HTML is flexible - attributes can be in any order, use single or double
     * quotes, etc. We use multiple patterns to catch variations.
     * 
     * @param string $html Complete HTML document
     * @return array Array of absolute URLs (deduplicated, same-host only)
     */
    private function extract_asset_urls($html) {
        $assets = [];
        
        try {
            // ============================================================
            // CSS FILES - Three patterns to catch attribute order variations
            // ============================================================
            
            // Pattern 1: rel="stylesheet" before href
            // <link rel="stylesheet" href="style.css">
            preg_match_all('#<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\'][^>]*>#i', $html, $css1);
            
            // Pattern 2: href with .css before rel
            // <link href="style.css" rel="stylesheet">
            preg_match_all('#<link[^>]+href=["\']([^"\']+\.css[^"\']*)["\'][^>]+rel=["\']stylesheet["\'][^>]*>#i', $html, $css2);
            
            // Pattern 3: Any <link> with .css extension (catches edge cases)
            preg_match_all('#<link[^>]+href=["\']([^"\']+\.css[^"\']*)["\'][^>]*>#i', $html, $css3);
            
            $assets = array_merge($assets, $css1[1], $css2[1], $css3[1]);
            
            // ============================================================
            // JAVASCRIPT FILES
            // ============================================================
            // <script src="script.js"></script>
            preg_match_all('#<script[^>]+src=["\']([^"\']+\.js[^"\']*)["\'][^>]*>#i', $html, $js_matches);
            $assets = array_merge($assets, $js_matches[1]);
            
            // ============================================================
            // IMAGES
            // ============================================================
            // <img src="image.jpg">
            preg_match_all('#<img[^>]+src=["\']([^"\']+)["\'][^>]*>#i', $html, $img_matches);
            $assets = array_merge($assets, $img_matches[1]);
            
            // ============================================================
            // SRCSETS (responsive images)
            // ============================================================
            // srcset="image.jpg 1x, image@2x.jpg 2x"
            // Format: comma-separated list of "URL descriptor"
            preg_match_all('#srcset=["\']([^"\']+)["\']#i', $html, $srcset_matches);
            foreach ($srcset_matches[1] as $srcset) {
                // Parse out individual URLs from srcset
                preg_match_all('#(https?://[^\s,]+|/[^\s,]+)#i', $srcset, $urls);
                $assets = array_merge($assets, $urls[1]);
            }
            
            // ============================================================
            // ICONS (favicon, apple-touch-icon, etc.)
            // ============================================================
            // <link rel="icon" href="favicon.png">
            preg_match_all('#<link[^>]+href=["\']([^"\']+\.(ico|png|svg|gif|jpg|jpeg|webp))["\'][^>]*>#i', $html, $icon_matches);
            $assets = array_merge($assets, $icon_matches[1]);
            
            // ============================================================
            // BACKGROUND IMAGES in inline styles
            // ============================================================
            // style="background-image: url(bg.jpg)"
            // Matches both background and background-image properties
            preg_match_all('#style=["\'][^"\']*background(?:-image)?:\s*url\(["\']?([^"\')]+)["\']?\)[^"\']*["\']#i', $html, $bg_matches);
            $assets = array_merge($assets, $bg_matches[1]);
            
        } catch (Exception $e) {
            error_log("[SCG] Error extracting asset URLs: " . $e->getMessage());
        }
        
        // ============================================================
        // FILTER & DEDUPLICATE
        // ============================================================
        try {
            $filtered = [];
            foreach ($assets as $url) {
                $url = html_entity_decode(trim($url));
                if (empty($url)) continue;
                
                // Convert to absolute URL
                $abs_url = $this->url_helper->absolute_url($url);
                
                // Only include same-host assets (no external CDNs)
                if ($this->url_helper->is_same_host($abs_url)) {
                    $filtered[] = $abs_url;
                }
            }
            
            return array_unique($filtered);
        } catch (Exception $e) {
            error_log("[SCG] Error filtering asset URLs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Rewrite asset paths to local /assets/ directory
     * 
     * This is the heart of making the site work offline. We rewrite all
     * asset references to point to our local assets directory using
     * relative paths.
     * 
     * Complexity: Calculating correct ../ depth
     * - Homepage (/) needs: assets/style.css
     * - /about/ needs: ../assets/style.css  
     * - /blog/post/ needs: ../../assets/style.css
     * 
     * We calculate the depth based on URL segments and add appropriate
     * ../ prefixes to reach the root where assets/ lives.
     * 
     * @param string $html Original HTML with absolute asset URLs
     * @return string HTML with rewritten relative asset paths
     */
    private function rewrite_asset_paths($html) {
        try {
            $depth = $this->url_helper->get_current_depth();
            $assets_path = str_repeat('../', $depth) . 'assets/';
            
            // ============================================================
            // REWRITE <link> TAGS (CSS and icons)
            // ============================================================
            $html = preg_replace_callback(
                '#<link([^>]*?)href=["\']([^"\']+)["\']([^>]*)>#i',
                function($m) use ($assets_path) {
                    try {
                        $href = html_entity_decode($m[2]);
                        
                        // Determine if this is a CSS or icon link
                        $rel = '';
                        if (preg_match('/\brel=["\']([^"\']+)["\']/i', $m[1] . ' ' . $m[3], $rm)) {
                            $rel = strtolower($rm[1]);
                        }
                        
                        $is_css = preg_match('/\.css(\?|$)/i', $href);
                        $is_icon = preg_match('/\.(?:ico|png|svg|gif|jpg|jpeg|webp|avif)(\?|$)/i', $href)
                                    || strpos($rel, 'icon') !== false;
                        
                        // Only rewrite same-host CSS and icon files
                        if (($is_css || $is_icon) && $this->url_helper->is_same_host($href)) {
                            $filename = $this->url_helper->filename_from_url($href);
                            return '<link' . $m[1] . 'href="' . $assets_path . esc_attr($filename) . '"' . $m[3] . '>';
                        }
                        return $m[0];
                    } catch (Exception $e) {
                        error_log("[SCG] Error rewriting link tag: " . $e->getMessage());
                        return $m[0];
                    }
                },
                $html
            );

            // ============================================================
            // REWRITE <script> TAGS
            // ============================================================
            // <script src="script.js"></script>
            $html = preg_replace_callback(
                '#<script([^>]*?)src=["\']([^"\']+\.js[^"\']*)["\']([^>]*)></script>#i',
                function($m) use ($assets_path) {
                    try {
                        $src = html_entity_decode($m[2]);
                        if ($this->url_helper->is_same_host($src)) {
                            $filename = $this->url_helper->filename_from_url($src);
                            return '<script' . $m[1] . 'src="' . $assets_path . esc_attr($filename) . '"' . $m[3] . '></script>';
                        }
                        return $m[0];
                    } catch (Exception $e) {
                        error_log("[SCG] Error rewriting script tag: " . $e->getMessage());
                        return $m[0];
                    }
                },
                $html
            );

            // ============================================================
            // REWRITE <img> TAGS (including srcset)
            // ============================================================
            $html = preg_replace_callback(
                '#<img([^>]*?)src=["\']([^"\']+)["\']([^>]*)>#i',
                function($m) use ($assets_path) {
                    try {
                        $src = html_entity_decode($m[2]);
                        if ($this->url_helper->is_same_host($src)) {
                            $filename = $this->url_helper->filename_from_url($src);
                            $new = '<img' . $m[1] . 'src="' . $assets_path . esc_attr($filename) . '"' . $m[3] . '>';
                            
                            // Handle srcset attribute if present
                            // srcset="image.jpg 1x, image@2x.jpg 2x"
                            if (preg_match('/\ssrcset=["\']([^"\']+)["\']/i', $m[0], $sm)) {
                                $srcset = $sm[1];
                                
                                // Process each image in srcset
                                // Format: "URL descriptor, URL descriptor, ..."
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
                                
                                // Replace srcset in the tag
                                $new = preg_replace('/\ssrcset=["\'][^"\']+["\']/', ' srcset="' . trim($new_srcset) . '"', $new);
                            }
                            return $new;
                        }
                        return $m[0];
                    } catch (Exception $e) {
                        error_log("[SCG] Error rewriting img tag: " . $e->getMessage());
                        return $m[0];
                    }
                },
                $html
            );

            // ============================================================
            // REWRITE VIDEO/SOURCE TAGS
            // ============================================================
            // <video poster="thumb.jpg"><source src="video.mp4"></video>
            $html = preg_replace_callback(
                '#<(source|video)([^>]*?)(src|poster)=["\']([^"\']+)["\']([^>]*)>#i',
                function($m) use ($assets_path) {
                    try {
                        $url = html_entity_decode($m[4]);
                        if ($this->url_helper->is_same_host($url)) {
                            $fn = $this->url_helper->filename_from_url($url);
                            return '<' . $m[1] . $m[2] . $m[3] . '="' . $assets_path . esc_attr($fn) . '"' . $m[5] . '>';
                        }
                        return $m[0];
                    } catch (Exception $e) {
                        error_log("[SCG] Error rewriting video/source tag: " . $e->getMessage());
                        return $m[0];
                    }
                },
                $html
            );

            // ============================================================
            // REWRITE META TAGS (Open Graph, Twitter Cards)
            // ============================================================
            // <meta property="og:image" content="image.jpg">
            // <meta name="twitter:image" content="image.jpg">
            $html = preg_replace_callback(
                '#<meta([^>]+)(property|name)=[\'"](og:image|twitter:image)[\'"]([^>]+)content=[\'"]([^\'"]+)[\'"]([^>]*)>#i',
                function($m) use ($assets_path) {
                    try {
                        $url = html_entity_decode($m[5]);
                        if ($this->url_helper->is_same_host($url)) {
                            $fn = $this->url_helper->filename_from_url($url);
                            return '<meta' . $m[1] . $m[2] . '="' . $m[3] . '"' . $m[4] . 'content="' . $assets_path . esc_attr($fn) . '"' . $m[6] . '>';
                        }
                        return $m[0];
                    } catch (Exception $e) {
                        error_log("[SCG] Error rewriting meta tag: " . $e->getMessage());
                        return $m[0];
                    }
                },
                $html
            );

            return $html;
            
        } catch (Exception $e) {
            error_log("[SCG] Critical error in rewrite_asset_paths: " . $e->getMessage());
            return $html;
        }
    }
    
    /**
     * Rewrite internal links for static site navigation
     * 
     * WordPress URLs like:
     *   /about/
     *   /blog/my-post/
     *   /contact/
     * 
     * Need to become static HTML paths like:
     *   about/index.html
     *   blog/my-post/index.html
     *   contact/index.html
     * 
     * Depth-aware prefixing:
     * - From homepage: about/index.html
     * - From /blog/: ../about/index.html
     * - From /blog/post/: ../../about/index.html
     * 
     * Preserves:
     * - Query strings (?page=2)
     * - Fragments (#section)
     * - Special links (mailto:, tel:, javascript:)
     * - External links
     * 
     * @param string $html HTML with WordPress-style links
     * @return string HTML with static-site-style links
     */
    private function rewrite_links($html) {
        try {
            $current_depth = $this->url_helper->get_current_depth();
            $depth_prefix = str_repeat('../', $current_depth);
            
            return preg_replace_callback(
                '#<a([^>]+)href=["\']([^"\']+)["\']#i',
                function($m) use ($depth_prefix) {
                    try {
                        $href = html_entity_decode($m[2]);

                        // ============================================================
                        // SKIP SPECIAL LINKS (don't rewrite these)
                        // ============================================================
                        if (
                            stripos($href, 'mailto:') === 0 ||  // Email links
                            stripos($href, 'tel:') === 0 ||     // Phone links
                            stripos($href, 'javascript:') === 0 || // JS handlers
                            stripos($href, '#') === 0 ||        // Anchor links
                            !$this->url_helper->is_same_host($href) // External links
                        ) {
                            return $m[0];
                        }

                        // ============================================================
                        // CONVERT TO ABSOLUTE URL
                        // ============================================================
                        $abs = $this->url_helper->absolute_url($href);
                        $parsed = parse_url($abs);

                        $path = $parsed['path'] ?? '/';
                        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                        $frag  = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

                        // ============================================================
                        // BUILD STATIC PATH
                        // ============================================================
                        
                        // Homepage special case
                        if ($path === '/' || $path === '') {
                            $new_href = $depth_prefix . 'index.html';
                        } else {
                            // /about/ becomes about/index.html
                            $relative = ltrim($path, '/');
                            
                            // Ensure trailing slash (WordPress convention)
                            if (substr($relative, -1) !== '/') {
                                $relative .= '/';
                            }
                            
                            $new_href = $depth_prefix . $relative . 'index.html';
                        }

                        return '<a' . $m[1] . 'href="' . $new_href . $query . $frag . '"';
                        
                    } catch (Exception $e) {
                        error_log("[SCG] Error rewriting link: " . $e->getMessage());
                        return $m[0];
                    }
                },
                $html
            );
            
        } catch (Exception $e) {
            error_log("[SCG] Critical error in rewrite_links: " . $e->getMessage());
            return $html;
        }
    }
    
    /**
     * Final HTML processing and cleanup
     * 
     * Performs final touches on static HTML:
     * 1. Remove WordPress-specific tags (API links, emoji scripts)
     * 2. Inject generation metadata comment
     * 3. Add offline usage instructions
     * 
     * Why remove WordPress-specific tags?
     * - wp-emoji scripts won't work offline
     * - REST API links are useless in static context
     * - Alternate links may point to live site
     * 
     * @param string $html Processed HTML
     * @return string Final cleaned HTML ready for static use
     */
    private function process_static_html($html) {
        try {
            $timestamp = date('Y-m-d H:i:s');
            $comment = "\n<!-- Static version generated: $timestamp -->\n";
            $comment .= "<!-- To use offline: Extract ZIP and open index.html in browser -->\n";
            
            // ============================================================
            // REMOVE WORDPRESS-SPECIFIC TAGS
            // ============================================================
            
            // Remove REST API and alternate links
            // <link rel='https://api.w.org/' href='...' />
            // <link rel='alternate' type='application/json' href='...' />
            $html = preg_replace('#<link[^>]+rel=["\'](?:https://api\.w\.org/|alternate)["\'][^>]*>#i', '', $html);
            
            // Remove WordPress emoji scripts (won't work offline anyway)
            // <script>/* wp-emoji loader */</script>
            $html = preg_replace('#<script[^>]*>.*?wp-emoji-release\.min\.js.*?</script>#is', '', $html);
            
            // ============================================================
            // INJECT GENERATION METADATA
            // ============================================================
            
            // Try to inject before </body> if present
            if (stripos($html, '</body>') !== false) {
                $html = str_ireplace('</body>', $comment . '</body>', $html);
            } else {
                // Otherwise append to end
                $html .= $comment;
            }
            
            return $html;
            
        } catch (Exception $e) {
            error_log("[SCG] Error in process_static_html: " . $e->getMessage());
            return $html;
        }
    }
}
