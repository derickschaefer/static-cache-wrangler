<?php
/**
 * Asset downloading and processing
 */

if (!defined('ABSPATH')) exit;

class SCG_Asset_Handler {
    
    private $url_helper;
    
    public function __construct() {
        $this->url_helper = new SCG_URL_Helper();
    }
    
    public function queue_asset_downloads($assets) {
        if (empty($assets)) {
            return;
        }
        
        error_log("[SCG] Queueing " . count($assets) . " assets");
        
        $existing = get_option('scg_pending_assets', []);
        $merged = array_unique(array_merge($existing, $assets));
        update_option('scg_pending_assets', $merged, false);
        
        if (!wp_next_scheduled('scg_process_assets')) {
            wp_schedule_single_event(time() + 10, 'scg_process_assets');
        }
    }
    
    public function download_queued_assets() {
        $assets = get_option('scg_pending_assets', []);
        
        if (empty($assets)) {
            return;
        }
        
        $batch_size = 10;
        $processed = 0;
        
        foreach ($assets as $key => $url) {
            if ($processed >= $batch_size) {
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
        
        if (empty($assets)) {
            delete_option('scg_pending_assets');
        } else {
            update_option('scg_pending_assets', array_values($assets), false);
            
            if (!wp_next_scheduled('scg_process_assets')) {
                wp_schedule_single_event(time() + 30, 'scg_process_assets');
            }
        }
    }
    
    public function ajax_process_pending() {
        check_ajax_referer('scg_process', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $assets = get_option('scg_pending_assets', []);
        $batch_size = 5;
        $processed = 0;
        $failed = 0;
        
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
    
    public function download_to_assets($url, $retry = 0) {
        $filename = $this->url_helper->filename_from_url($url);
        $dest = SCG_ASSETS_DIR . $filename;

        $downloaded = get_option('scg_downloaded_assets', []);
        if (in_array($filename, $downloaded) && file_exists($dest)) {
            return $dest;
        }

        if (!file_exists($dest)) {
            $response = wp_remote_get($url, ['timeout' => 20]);
            
            if (is_wp_error($response)) {
                if ($retry < 2) {
                    sleep(1);
                    return $this->download_to_assets($url, $retry + 1);
                }
                error_log("[SCG] Failed to download: $url");
                return false;
            }
            
            $code = wp_remote_retrieve_response_code($response);
            if ($code < 200 || $code > 299) {
                error_log("[SCG] HTTP $code for: $url");
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                return false;
            }

            if (preg_match('/\.css$/i', $filename)) {
                $body = $this->process_css_content($body, $url);
            }
            
            if (preg_match('/\.js$/i', $filename)) {
                $body = $this->process_js_content($body, $url);
            }

            file_put_contents($dest, $body);
            
            $downloaded[] = $filename;
            update_option('scg_downloaded_assets', array_unique($downloaded), false);
        }

        return $dest;
    }
    
    private function process_css_content($css, $css_original_url) {
        // Process url() in CSS
        $css = preg_replace_callback('/url\(([^)]+)\)/i', function($m) use ($css_original_url) {
            $raw = trim($m[1], " \t\n\r\0\x0B'\"");
            
            if ($raw === '' || stripos($raw, 'data:') === 0) {
                return $m[0];
            }

            if (preg_match('#^https?://#i', $raw) || strpos($raw, '//') === 0) {
                $abs_url = $raw;
                if (strpos($raw, '//') === 0) {
                    $abs_url = (is_ssl() ? 'https:' : 'http:') . $raw;
                }
            } elseif (isset($raw[0]) && $raw[0] === '/') {
                $abs_url = $this->url_helper->site_url() . $raw;
            } else {
                $css_path = parse_url($css_original_url, PHP_URL_PATH);
                $base_dir = dirname($css_path);
                $abs_url = $this->url_helper->site_url() . $base_dir . '/' . $raw;
            }

            $abs_url = $this->url_helper->normalize_url($abs_url);

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
            
            if (preg_match('#^https?://#i', $url)) {
                $abs_url = $url;
            } elseif (isset($url[0]) && $url[0] === '/') {
                $abs_url = $this->url_helper->site_url() . $url;
            } else {
                $css_path = parse_url($css_original_url, PHP_URL_PATH);
                $base_dir = dirname($css_path);
                $abs_url = $this->url_helper->site_url() . $base_dir . '/' . $url;
            }
            
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
    
    private function process_js_content($js, $js_original_url) {
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
