<?php
/**
 * URL manipulation and path helpers
 * 
 * SECURITY UPDATE:
 * - Enhanced filename_from_url() with multi-layer validation
 * - Added explicit path traversal checks
 * - Whitelist-based file extension validation
 * - Additional sanitization after version insertion
 * - Fallback to hash for suspicious filenames
 * 
 * @package StaticCacheGenerator
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class SCG_URL_Helper {
    
    /**
     * Allowed file extensions for downloaded assets
     * 
     * Video and audio files are intentionally excluded to prevent
     * excessive disk usage. Static sites should link to video/audio
     * hosted externally or on the original WordPress server.
     * 
     * @var array
     */
    private static $allowed_extensions = [
        // Stylesheets
        'css',
        // Scripts
        'js',
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'avif', 'ico', 'bmp',
        // Fonts
        'woff', 'woff2', 'ttf', 'otf', 'eot',
        // Documents (if needed)
        'pdf',
        // Note: Video (mp4, webm, ogg) and audio (mp3, wav) intentionally excluded
        // Note: ZIP files excluded to prevent nested compression issues
    ];
    
    public function site_url() {
        return rtrim(home_url(), '/');
    }
    
    public function is_same_host($url) {
        $site_host = parse_url($this->site_url(), PHP_URL_HOST);
        if ($site_host === null) {
            return false;
        }

        if (strpos($url, '//') === 0) {
            $url = (is_ssl() ? 'https:' : 'http:') . $url;
        } elseif (isset($url[0]) && $url[0] === '/') {
            $url = $this->site_url() . $url;
        } elseif (!preg_match('#^https?://#i', $url)) {
            $url = trailingslashit($this->site_url()) . ltrim($url, '/');
        }

        $host = parse_url($url, PHP_URL_HOST);
        return strtolower($host ?? '') === strtolower($site_host);
    }
    
    public function absolute_url($url) {
        if (strpos($url, '//') === 0) {
            return (is_ssl() ? 'https:' : 'http:') . $url;
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (isset($url[0]) && $url[0] === '/') {
            return $this->site_url() . $url;
        }
        return trailingslashit($this->site_url()) . ltrim($url, '/');
    }
    
    /**
     * Extract safe filename from URL with security validation
     * 
     * Security layers:
     * 1. Parse URL and extract path
     * 2. Strip any path traversal sequences before basename()
     * 3. Extract filename using basename()
     * 4. Validate file extension against whitelist
     * 5. Handle version query strings
     * 6. Multiple sanitization passes
     * 7. Final validation for dangerous patterns
     * 8. Fallback to hash if anything suspicious
     * 
     * @param string $url URL to extract filename from
     * @return string Safe filename or hash
     */
    public function filename_from_url($url) {
        // Step 1: Parse URL
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return $this->safe_hash($url);
        }
        
        // Step 2: URL decode to handle encoded characters
        $path = rawurldecode($path);
        
        // Step 3: Strip any path traversal sequences BEFORE basename()
        // This prevents: /path/../../etc/passwd
        $path = str_replace(['../', '..\\'], '', $path);
        
        // Step 4: Remove null bytes (defense in depth, even though PHP 5.3+ handles this)
        $path = str_replace("\0", '', $path);
        
        // Step 5: Extract filename
        $base = basename($path);
        
        // Step 6: Early validation - must have content
        if (empty($base) || $base === '.' || $base === '..') {
            return $this->safe_hash($url);
        }
        
        // Step 7: Extract and validate file extension
        $extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        
        if (empty($extension) || !in_array($extension, self::$allowed_extensions, true)) {
            error_log('[SCG] Invalid or missing file extension: ' . $extension . ' for URL: ' . $url);
            return $this->safe_hash($url);
        }
        
        // Step 8: First sanitization pass
        $base = sanitize_file_name($base);
        
        // Step 9: Handle version query string (e.g., ?ver=1.2.3)
        $query = parse_url($url, PHP_URL_QUERY);
        
        if ($query && preg_match('/(?:^|&)ver=([^&]+)/i', $query, $m)) {
            // Sanitize version string
            $ver = sanitize_file_name($m[1]);
            
            // Remove any path traversal from version
            $ver = str_replace(['..', '/', '\\'], '', $ver);
            
            // Limit version length
            $ver = substr($ver, 0, 20);
            
            // Only add version if it's safe
            if (!empty($ver) && ctype_alnum(str_replace(['.', '-', '_'], '', $ver))) {
                $dot = strrpos($base, '.');
                if ($dot !== false) {
                    $base = substr($base, 0, $dot) . ".{$ver}" . substr($base, $dot);
                } else {
                    $base .= ".{$ver}";
                }
            }
        }
        
        // Step 10: Second sanitization pass (after version insertion)
        $base = sanitize_file_name($base);
        
        // Step 11: Final validation - check for dangerous patterns
        if ($this->contains_dangerous_patterns($base)) {
            error_log('[SCG] Dangerous pattern detected in filename: ' . $base);
            return $this->safe_hash($url);
        }
        
        // Step 12: Validate final extension still matches whitelist
        // (in case sanitization changed it)
        $final_extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        if (!in_array($final_extension, self::$allowed_extensions, true)) {
            error_log('[SCG] Final extension validation failed: ' . $final_extension);
            return $this->safe_hash($url);
        }
        
        // Step 13: Length validation (filesystem limits)
        if (strlen($base) > 255) {
            // Truncate but keep extension
            $ext = '.' . $final_extension;
            $base = substr($base, 0, 255 - strlen($ext)) . $ext;
        }
        
        return $base ?: $this->safe_hash($url);
    }
    
    /**
     * Check if filename contains dangerous patterns
     * 
     * @param string $filename Filename to check
     * @return bool True if dangerous patterns found
     */
    private function contains_dangerous_patterns($filename) {
        // Check for path traversal
        if (strpos($filename, '..') !== false) {
            return true;
        }
        
        // Check for directory separators
        if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            return true;
        }
        
        // Check for null bytes
        if (strpos($filename, "\0") !== false) {
            return true;
        }
        
        // Check for control characters
        if (preg_match('/[\x00-\x1F\x7F]/', $filename)) {
            return true;
        }
        
        // Check for executable extensions (even if not primary extension)
        $dangerous_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'pht', 'phar', 'sh', 'bash', 'cgi', 'pl', 'py', 'exe', 'bat', 'com'];
        $filename_lower = strtolower($filename);
        foreach ($dangerous_extensions as $ext) {
            if (strpos($filename_lower, '.' . $ext) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate safe hash for filename
     * 
     * @param string $url URL to hash
     * @return string Hash-based filename with .dat extension
     */
    private function safe_hash($url) {
        // Use MD5 for filename (not for security, just uniqueness)
        // Add .dat extension so it's clearly a generated file
        return md5($url) . '.dat';
    }
    
    public function get_current_depth() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsed_url = parse_url($request_uri);
        $path = rtrim($parsed_url['path'] ?? '', '/');
        
        if ($path === '' || $path === '/') {
            return 0;
        }
        
        return substr_count(trim($path, '/'), '/') + 1;
    }
    
    public function normalize_url($url) {
        $parts = parse_url($url);
        if (!isset($parts['path'])) {
            return $url;
        }
        
        $path = $parts['path'];
        $segments = explode('/', $path);
        $normalized = [];
        
        foreach ($segments as $segment) {
            if ($segment === '..') {
                array_pop($normalized);
            } elseif ($segment !== '.' && $segment !== '') {
                $normalized[] = $segment;
            }
        }
        
        $parts['path'] = '/' . implode('/', $normalized);
        
        $url = '';
        if (isset($parts['scheme'])) $url .= $parts['scheme'] . '://';
        if (isset($parts['host'])) $url .= $parts['host'];
        if (isset($parts['port'])) $url .= ':' . $parts['port'];
        if (isset($parts['path'])) $url .= $parts['path'];
        if (isset($parts['query'])) $url .= '?' . $parts['query'];
        if (isset($parts['fragment'])) $url .= '#' . $parts['fragment'];
        
        return $url;
    }
    
    /**
     * Get static file path for current request
     * 
     * SECURITY: Sanitizes request URI to prevent path traversal
     * 
     * @param string $extension File extension (default: 'html')
     * @return string Safe file path within static directory
     */
    public function get_static_file_path($extension = 'html') {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Sanitize request URI first
        $request_uri = sanitize_text_field($request_uri);
        
        $parsed_url = parse_url($request_uri);
        $path = rtrim($parsed_url['path'] ?? '', '/');
        
        // Remove any dangerous characters (keep only alphanumeric, /, -, _)
        $path = preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $path);
        
        // Remove path traversal sequences
        $path = str_replace(['../', '..\\'], '', $path);
        
        if ($path === '' || $path === '/') {
            return SCG_STATIC_DIR . 'index.' . $extension;
        }
        
        $dir = SCG_STATIC_DIR . ltrim($path, '/');
        return $dir . '/index.' . $extension;
    }
}
