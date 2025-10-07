<?php
/**
 * Asset downloading and processing
 * 
 * IMPROVEMENTS IN THIS VERSION:
 * - Comprehensive try-catch blocks for all file operations and network requests
 * - Detailed inline documentation explaining CSS/JS processing logic
 * - Better error messages with context
 * - Graceful degradation on failures
 * 
 * @package StaticCacheGenerator
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class SCG_Asset_Handler {
    
    private $url_helper;
    
    public function __construct() {
        $this->url_helper = new SCG_URL_Helper();
    }
    
    /**
     * Queue assets for background downloading
     * 
     * Merges new assets with existing queue and schedules WordPress cron
     * event for processing. Deduplicates URLs to avoid redundant downloads.
     * 
     * @param array $assets Array of absolute URLs to queue
     * @return void
     */
    public function queue_asset_downloads($assets) {
        if (empty($assets)) {
            return;
        }
        
        try {
            error_log("[SCG] Queueing " . count($assets) . " assets");
            
            $existing = get_option('scg_pending_assets', []);
            $merged = array_unique(array_merge($existing, $assets));
            update_option('scg_pending_assets', $merged, false);
            
            // Schedule single-run cron event if not already scheduled
            if (!wp_next_scheduled('scg_process_assets')) {
                wp_schedule_single_event(time() + 10, 'scg_process_assets');
            }
        } catch (Exception $e) {
            error_log("[SCG] Error queueing assets: " . $e->getMessage());
        }
    }
    
    /**
     * Process queued assets in batches (WordPress cron callback)
     * 
     * Downloads assets in small batches to avoid memory/timeout issues.
     * Automatically reschedules itself if more assets remain in queue.
     * 
     * @return void
     */
    public function download_queued_assets() {
        try {
            $assets = get_option('scg_pending_assets', []);
            
            if (empty($assets)) {
                return;
            }
            
            $batch_size = 10; // Process 10 assets per run
            $processed = 0;
            
            foreach ($assets as $key => $url) {
                if ($processed >= $batch_size) {
                    // Schedule next batch
                    if (!wp_next_scheduled('scg_process_assets')) {
                        wp_schedule_single_event(time() + 30, 'scg_process_assets');
                    }
                    break;
                }
                
                try {
                    $result = $this->download_to_assets($url);
                    if ($result !== false) {
                        unset($assets[$key]);
                        $processed++;
                    }
                } catch (Exception $e) {
                    error_log("[SCG] Error processing asset {$url}: " . $e->getMessage());
                    // Remove failed asset to prevent infinite retry
                    unset($assets[$key]);
                }
            }
            
            // Update or clear the queue
            if (empty($assets)) {
                delete_option('scg_pending_assets');
            } else {
                update_option('scg_pending_assets', array_values($assets), false);
                
                // Ensure next batch is scheduled
                if (!wp_next_scheduled('scg_process_assets')) {
                    wp_schedule_single_event(time() + 30, 'scg_process_assets');
                }
            }
        } catch (Exception $e) {
            error_log("[SCG] Critical error in download_queued_assets: " . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for manual asset processing from admin UI
     * 
     * Processes assets synchronously with smaller batch size for
     * responsive UI feedback. Returns JSON progress data.
     * 
     * @return void (outputs JSON and exits)
     */
    public function ajax_process_pending() {
        try {
            check_ajax_referer('scg_process', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
                return;
            }
            
            $assets = get_option('scg_pending_assets', []);
            $batch_size = 5; // Smaller batches for AJAX to stay responsive
            $processed = 0;
            $failed = 0;
            
            foreach ($assets as $key => $url) {
                if ($processed >= $batch_size) {
                    break;
                }
                
                try {
                    $result = $this->download_to_assets($url);
                    if ($result !== false) {
                        unset($assets[$key]);
                        $processed++;
                    } else {
                        $failed++;
                        unset($assets[$key]); // Remove to avoid infinite retry
                    }
                } catch (Exception $e) {
                    error_log("[SCG] AJAX asset processing error: " . $e->getMessage());
                    $failed++;
                    unset($assets[$key]);
                }
            }
            
            // Update queue
            if (empty($assets)) {
                delete_option('scg_pending_assets');
            } else {
                update_option('scg_pending_assets', array_values($assets), false);
            }
            
            $remaining = count($assets);
            
            wp_send_json_success([
                'processed' => $processed,
                'failed' => $failed,
                'remaining' => $remaining,
                'message' => "Processed $processed assets. $remaining remaining."
            ]);
        } catch (Exception $e) {
            error_log("[SCG] AJAX handler critical error: " . $e->getMessage());
            wp_send_json_error('Processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Download asset to local assets directory
     * 
     * Downloads file via HTTP, processes CSS/JS to localize nested assets,
     * and saves to assets directory. Includes retry logic for transient failures.
     * 
     * @param string $url Absolute URL to download
     * @param int $retry Current retry attempt (internal use)
     * @return string|false Path to saved file or false on failure
     */
    public function download_to_assets($url, $retry = 0) {
        try {
            $filename = $this->url_helper->filename_from_url($url);
            $dest = SCG_ASSETS_DIR . $filename;

            // Check if already downloaded
            $downloaded = get_option('scg_downloaded_assets', []);
            if (in_array($filename, $downloaded) && file_exists($dest)) {
                return $dest;
            }

            // Download file if not exists
            if (!file_exists($dest)) {
                $response = wp_remote_get($url, ['timeout' => 20]);
                
                if (is_wp_error($response)) {
                    // Retry on transient network errors
                    if ($retry < 2) {
                        sleep(1);
                        return $this->download_to_assets($url, $retry + 1);
                    }
                    error_log("[SCG] Failed to download after retries: $url - " . $response->get_error_message());
                    return false;
                }
                
                $code = wp_remote_retrieve_response_code($response);
                if ($code < 200 || $code > 299) {
                    error_log("[SCG] HTTP $code for: $url");
                    return false;
                }
                
                $body = wp_remote_retrieve_body($response);
                if (empty($body)) {
                    error_log("[SCG] Empty response body for: $url");
                    return false;
                }

                // Process CSS files to localize nested assets (fonts, images in url())
                if (preg_match('/\.css$/i', $filename)) {
                    try {
                        $body = $this->process_css_content($body, $url);
                    } catch (Exception $e) {
                        error_log("[SCG] CSS processing error for {$url}: " . $e->getMessage());
                        // Continue with unprocessed CSS rather than failing completely
                    }
                }
                
                // Process JS files to localize hardcoded asset URLs
                if (preg_match('/\.js$/i', $filename)) {
                    try {
                        $body = $this->process_js_content($body, $url);
                    } catch (Exception $e) {
                        error_log("[SCG] JS processing error for {$url}: " . $e->getMessage());
                        // Continue with unprocessed JS rather than failing completely
                    }
                }

                // Write to disk
                $bytes_written = file_put_contents($dest, $body);
                if ($bytes_written === false) {
                    error_log("[SCG] Failed to write file: $dest");
                    return false;
                }
                
                // Track as downloaded
                $downloaded[] = $filename;
                update_option('scg_downloaded_assets', array_unique($downloaded), false);
            }

            return $dest;
        } catch (Exception $e) {
            error_log("[SCG] Exception in download_to_assets for {$url}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process CSS content to localize nested assets
     * 
     * This is one of the most complex methods in the plugin. It handles:
     * 
     * 1. url() references - background images, fonts, etc.
     *    - Converts absolute and relative URLs to local asset references
     *    - Downloads referenced assets recursively
     *    - Handles both quoted and unquoted url() values
     * 
     * 2. @import statements - nested stylesheets
     *    - Resolves relative imports against the CSS file's location
     *    - Downloads imported CSS files
     * 
     * Why this is necessary:
     * CSS files often reference other assets using relative paths like:
     *   url(../fonts/myfont.woff2)
     *   url(../../images/bg.png)
     * 
     * These paths are relative to the CSS file's original server location.
     * When we move the CSS to /assets/, these relative paths break.
     * 
     * Solution:
     * - Parse out all url() and @import references
     * - Resolve them to absolute URLs using the CSS file's original location
     * - Download those assets to our local assets directory
     * - Rewrite the url() to just reference the filename (now all in same dir)
     * 
     * @param string $css Raw CSS content
     * @param string $css_original_url The original URL where this CSS was hosted
     * @return string Processed CSS with localized asset references
     */
    private function process_css_content($css, $css_original_url) {
        try {
            // ============================================================
            // PART 1: Process url() references
            // ============================================================
            // Regex matches: url(value) where value can be:
            // - Unquoted: url(image.png)
            // - Single-quoted: url('image.png')
            // - Double-quoted: url("image.png")
            $css = preg_replace_callback('/url\(([^)]+)\)/i', function($m) use ($css_original_url) {
                try {
                    // Extract the URL and remove quotes/whitespace
                    $raw = trim($m[1], " \t\n\r\0\x0B'\"");
                    
                    // Skip data URIs (inline base64) and empty values
                    if ($raw === '' || stripos($raw, 'data:') === 0) {
                        return $m[0];
                    }

                    // STEP 1: Convert to absolute URL
                    // Handle three cases:
                    
                    // Case A: Already absolute (http://... or https://...)
                    if (preg_match('#^https?://#i', $raw) || strpos($raw, '//') === 0) {
                        $abs_url = $raw;
                        // Protocol-relative URLs (//example.com/file.png)
                        if (strpos($raw, '//') === 0) {
                            $abs_url = (is_ssl() ? 'https:' : 'http:') . $raw;
                        }
                    } 
                    // Case B: Root-relative (/images/file.png)
                    elseif (isset($raw[0]) && $raw[0] === '/') {
                        $abs_url = $this->url_helper->site_url() . $raw;
                    } 
                    // Case C: Relative to CSS file (../fonts/file.woff2)
                    else {
                        // Get the directory where the CSS file lives
                        $css_path = parse_url($css_original_url, PHP_URL_PATH);
                        $base_dir = dirname($css_path);
                        // Combine base directory + relative path
                        $abs_url = $this->url_helper->site_url() . $base_dir . '/' . $raw;
                    }

                    // STEP 2: Normalize URL (resolve ../ and ./ segments)
                    $abs_url = $this->url_helper->normalize_url($abs_url);

                    // STEP 3: Download if same-host, rewrite reference
                    if ($this->url_helper->is_same_host($abs_url)) {
                        $saved = $this->download_to_assets($abs_url);
                        if ($saved) {
                            // Rewrite to just the filename since all assets in same dir
                            return 'url(' . basename($saved) . ')';
                        }
                    }
                    
                    // If external or download failed, leave unchanged
                    return $m[0];
                } catch (Exception $e) {
                    error_log("[SCG] Error processing CSS url(): " . $e->getMessage());
                    return $m[0]; // Return original on error
                }
            }, $css);
            
            // ============================================================
            // PART 2: Process @import statements
            // ============================================================
            // Matches: @import "file.css" or @import 'file.css'
            // @import statements load other CSS files, which may also have
            // relative paths that need resolution
            $css = preg_replace_callback('/@import\s+["\']([^"\']+)["\']/i', function($m) use ($css_original_url) {
                try {
                    $url = $m[1];
                    
                    // Convert to absolute URL (same logic as url() processing)
                    if (preg_match('#^https?://#i', $url)) {
                        $abs_url = $url;
                    } elseif (isset($url[0]) && $url[0] === '/') {
                        $abs_url = $this->url_helper->site_url() . $url;
                    } else {
                        $css_path = parse_url($css_original_url, PHP_URL_PATH);
                        $base_dir = dirname($css_path);
                        $abs_url = $this->url_helper->site_url() . $base_dir . '/' . $url;
                    }
                    
                    // Download and rewrite if same-host
                    if ($this->url_helper->is_same_host($abs_url)) {
                        $saved = $this->download_to_assets($abs_url);
                        if ($saved) {
                            return '@import "' . basename($saved) . '"';
                        }
                    }
                    
                    return $m[0];
                } catch (Exception $e) {
                    error_log("[SCG] Error processing CSS @import: " . $e->getMessage());
                    return $m[0];
                }
            }, $css);
            
            return $css;
        } catch (Exception $e) {
            error_log("[SCG] Critical error in process_css_content: " . $e->getMessage());
            return $css; // Return original CSS on catastrophic failure
        }
    }
    
    /**
     * Process JavaScript content to localize hardcoded asset URLs
     * 
     * Why this matters:
     * JavaScript files sometimes contain hardcoded URLs like:
     *   var bgImage = "https://mysite.com/wp-content/themes/mytheme/images/bg.jpg";
     *   logo.src = '/wp-content/uploads/2024/logo.png';
     * 
     * When we create a static site, these absolute URLs will try to fetch
     * from the live server. We need to rewrite them to local references.
     * 
     * Strategy:
     * - Find quoted strings that contain our site URL + asset extensions
     * - Download those assets
     * - Rewrite to relative /assets/ path
     * 
     * Limitations:
     * - Only catches simple quoted strings (not complex concatenation)
     * - Only processes common asset types (images, fonts)
     * - Template literals with backticks are included for ES6 support
     * 
     * @param string $js Raw JavaScript content
     * @param string $js_original_url Original URL of the JS file (for context)
     * @return string Processed JavaScript with localized asset references
     */
    private function process_js_content($js, $js_original_url) {
        try {
            // Build regex pattern to match:
            // ["'`] + site_url + path + asset_extension + ["'`]
            // 
            // Example matches:
            // "https://mysite.com/wp-content/uploads/image.png"
            // '/wp-content/themes/theme/font.woff2'
            // `${baseUrl}/assets/logo.svg`  (template literal)
            $js = preg_replace_callback(
                '/(["\'`])(' . preg_quote($this->url_helper->site_url(), '/') . '[^"\'`]+\.(png|jpg|jpeg|gif|svg|webp|woff2?|ttf|eot))\1/i', 
                function($m) {
                    try {
                        $url = $m[2];
                        
                        // Only process same-host assets
                        if ($this->url_helper->is_same_host($url)) {
                            $saved = $this->download_to_assets($url);
                            if ($saved) {
                                // Rewrite to /assets/filename (works in static site structure)
                                return $m[1] . '/assets/' . basename($saved) . $m[1];
                            }
                        }
                        return $m[0];
                    } catch (Exception $e) {
                        error_log("[SCG] Error processing JS asset reference: " . $e->getMessage());
                        return $m[0];
                    }
                }, 
                $js
            );
            
            return $js;
        } catch (Exception $e) {
            error_log("[SCG] Critical error in process_js_content: " . $e->getMessage());
            return $js; // Return original JS on catastrophic failure
        }
    }
}
