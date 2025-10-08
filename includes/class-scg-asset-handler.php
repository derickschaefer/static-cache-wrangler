<?php
/**
 * Asset downloading and processing
 *
 * Handles queuing, downloading, and processing of CSS, JS, images,
 * fonts, and other assets for static site generation.
 *
 * @package StaticCacheGenerator
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class SCG_Asset_Handler {
    
    /**
     * URL helper instance
     * @var SCG_URL_Helper
     */
    private $url_helper;
    
    /**
     * Constructor - initialize dependencies
     */
    public function __construct() {
        $this->url_helper = new SCG_URL_Helper();
    }
    
    /**
     * Queue assets for background downloading
     *
     * @param array $assets Array of asset URLs
     */
    public function queue_asset_downloads($assets) {
        if (empty($assets)) {
            return;
        }
        
        scg_log_debug( 'Queueing ' . count( $assets ) . ' assets' );
        
        // Merge with existing pending assets
        $existing = get_option('scg_pending_assets', []);
        $merged = array_unique(array_merge($existing, $assets));
        update_option('scg_pending_assets', $merged, false);
        
        // Schedule background processing if not already scheduled
        if (!wp_next_scheduled('scg_process_assets')) {
            wp_schedule_single_event(time() + 10, 'scg_process_assets');
        }
    }
    
    /**
     * Process queued assets in background
     *
     * Downloads assets in batches to avoid server overload
     */
    public function download_queued_assets() {
        $assets = get_option('scg_pending_assets', []);
        
        if (empty($assets)) {
            return;
        }
        
        $batch_size = 10;
        $processed = 0;
        
        // Process assets in batches
        foreach ($assets as $key => $url) {
            if ($processed >= $batch_size) {
                // Schedule next batch
                if (!wp_next_scheduled('scg_process_assets')) {
                    wp_schedule_single_event(time() + 30, 'scg_process_assets');
                }
                break;
            }
            
            $result = $this->download_to_assets($url);
            if ($result !== false) {
                unset($assets[$key]);
                $processed++;
            }
        }
        
        // Update pending assets list
        if (empty($assets)) {
            delete_option('scg_pending_assets');
        } else {
            update_option('scg_pending_assets', array_values($assets), false);
            
            // Schedule next batch if assets remain
            if (!wp_next_scheduled('scg_process_assets')) {
                wp_schedule_single_event(time() + 30, 'scg_process_assets');
            }
        }
    }
    
    /**
     * AJAX handler for manual asset processing
     *
     * Allows admin to trigger asset processing immediately
     */
    public function ajax_process_pending() {
        check_ajax_referer('scg_process', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $assets = get_option('scg_pending_assets', []);
        $batch_size = 5;
        $processed = 0;
        $failed = 0;
        
        // Process a small batch for AJAX response
        foreach ($assets as $key => $url) {
            if ($processed >= $batch_size) {
                break;
            }
            
            $result = $this->download_to_assets($url);
            if ($result !== false) {
                unset($assets[$key]);
                $processed++;
            } else {
                $failed++;
                unset($assets[$key]);
            }
        }
        
        // Update pending assets
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
    }
    
    /**
     * Download asset to local assets directory
     *
     * @param string $url Asset URL to download
     * @param int $retry Retry attempt number
     * @return string|false Path to downloaded file or false on failure
     */
    public function download_to_assets($url, $retry = 0) {
        $filename = $this->url_helper->filename_from_url($url);
        $dest = SCG_ASSETS_DIR . $filename;

        // Skip if already downloaded
        $downloaded = get_option('scg_downloaded_assets', []);
        if (in_array($filename, $downloaded) && file_exists($dest)) {
            return $dest;
        }

        // Download the file
        if (!file_exists($dest)) {
            $response = wp_remote_get($url, ['timeout' => 20]);
            
            // Handle errors with retry
            if (is_wp_error($response)) {
                if ($retry < 2) {
                    sleep(1);
                    return $this->download_to_assets($url, $retry + 1);
                }
                scg_log_debug( 'Failed to download: ' . $url );
                return false;
            }
            
            // Check HTTP status code
            $code = wp_remote_retrieve_response_code($response);
            if ($code < 200 || $code > 299) {
                scg_log_debug( 'HTTP ' . $code . ' for: ' . $url );
                return false;
            }
            
            // Get response body
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                return false;
            }

            // Process CSS files to rewrite internal URLs
            if (preg_match('/\.css$/i', $filename)) {
                $body = $this->process_css_content($body, $url);
            }
            
            // Process JavaScript files to rewrite asset URLs
            if (preg_match('/\.js$/i', $filename)) {
                $body = $this->process_js_content($body, $url);
            }

            // Save file to assets directory
            file_put_contents($dest, $body);
            
            // Mark as downloaded
            $downloaded[] = $filename;
            update_option('scg_downloaded_assets', array_unique($downloaded), false);
        }

        return $dest;
    }
    
    /**
     * Process CSS content to rewrite URLs
     *
     * Rewrites url() and @import statements in CSS files
     *
     * @param string $css CSS content
     * @param string $css_original_url Original URL of the CSS file
     * @return string Processed CSS content
     */
    private function process_css_content($css, $css_original_url) {
        // Process url() in CSS
        $css = preg_replace_callback('/url\(([^)]+)\)/i', function($m) use ($css_original_url) {
            $raw = trim($m[1], " \t\n\r\0\x0B'\"");
            
            // Skip empty or data URIs
            if ($raw === '' || stripos($raw, 'data:') === 0) {
                return $m[0];
            }

            // Resolve relative URLs
            if (preg_match('#^https?://#i', $raw) || strpos($raw, '//') === 0) {
                $abs_url = $raw;
                if (strpos($raw, '//') === 0) {
                    $abs_url = (is_ssl() ? 'https:' : 'http:') . $raw;
                }
            } elseif (isset($raw[0]) && $raw[0] === '/') {
                $abs_url = $this->url_helper->site_url() . $raw;
            } else {
                $css_parsed = wp_parse_url($css_original_url);
                $css_path = $css_parsed['path'] ?? '';
                $base_dir = dirname($css_path);
                $abs_url = $this->url_helper->site_url() . $base_dir . '/' . $raw;
            }

            $abs_url = $this->url_helper->normalize_url($abs_url);

            // Download and rewrite if same host
            if ($this->url_helper->is_same_host($abs_url)) {
                $saved = $this->download_to_assets($abs_url);
                if ($saved) {
                    return 'url(' . basename($saved) . ')';
                }
            }
            
            return $m[0];
        }, $css);
        
        // Process @import in CSS
        $css = preg_replace_callback('/@import\s+["\']([^"\']+)["\']/i', function($m) use ($css_original_url) {
            $url = $m[1];
            
            // Resolve relative URLs
            if (preg_match('#^https?://#i', $url)) {
                $abs_url = $url;
            } elseif (isset($url[0]) && $url[0] === '/') {
                $abs_url = $this->url_helper->site_url() . $url;
            } else {
                $css_parsed = wp_parse_url($css_original_url);
                $css_path = $css_parsed['path'] ?? '';
                $base_dir = dirname($css_path);
                $abs_url = $this->url_helper->site_url() . $base_dir . '/' . $url;
            }
            
            // Download and rewrite if same host
            if ($this->url_helper->is_same_host($abs_url)) {
                $saved = $this->download_to_assets($abs_url);
                if ($saved) {
                    return '@import "' . basename($saved) . '"';
                }
            }
            
            return $m[0];
        }, $css);
        
        return $css;
    }
    
    /**
     * Process JavaScript content to rewrite asset URLs
     *
     * Rewrites string literals containing asset URLs in JavaScript
     *
     * @param string $js JavaScript content
     * @param string $js_original_url Original URL of the JavaScript file
     * @return string Processed JavaScript content
     */
    private function process_js_content($js, $js_original_url) {
        // Find asset URLs in string literals and rewrite them
        $js = preg_replace_callback(
            '/(["\'`])(' . preg_quote($this->url_helper->site_url(), '/') . '[^"\'`]+\.(png|jpg|jpeg|gif|svg|webp|woff2?|ttf|eot))\1/i', 
            function($m) {
                $url = $m[2];
                if ($this->url_helper->is_same_host($url)) {
                    $saved = $this->download_to_assets($url);
                    if ($saved) {
                        return $m[1] . '/assets/' . basename($saved) . $m[1];
                    }
                }
                return $m[0];
            }, 
            $js
        );
        return $js;
    }
}
