<?php
/**
 * URL manipulation and path helpers
 *
 * Provides utility methods for URL parsing, path generation,
 * and filename extraction for static site generation.
 *
 * @package StaticCacheWrangler
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class STCW_URL_Helper {
    
    /**
     * Get the site URL without trailing slash
     *
     * @return string Site URL
     */
    public function site_url() {
        return rtrim(home_url(), '/');
    }
    
    /**
     * Check if URL belongs to the same host as the WordPress site
     *
     * @param string $url URL to check
     * @return bool True if same host
     */
    public function is_same_host($url) {
        $site_host = wp_parse_url($this->site_url(), PHP_URL_HOST);
        if ($site_host === null) {
            return false;
        }

        // Handle protocol-relative URLs
        if (strpos($url, '//') === 0) {
            $url = (is_ssl() ? 'https:' : 'http:') . $url;
        } elseif (isset($url[0]) && $url[0] === '/') {
            // Handle absolute paths
            $url = $this->site_url() . $url;
        } elseif (!preg_match('#^https?://#i', $url)) {
            // Handle relative paths
            $url = trailingslashit($this->site_url()) . ltrim($url, '/');
        }

        $host = wp_parse_url($url, PHP_URL_HOST);
        return strtolower($host ?? '') === strtolower($site_host);
    }
    
    /**
     * Convert relative or protocol-relative URL to absolute URL
     *
     * @param string $url URL to convert
     * @return string Absolute URL
     */
    public function absolute_url($url) {
        // Protocol-relative URL
        if (strpos($url, '//') === 0) {
            return (is_ssl() ? 'https:' : 'http:') . $url;
        }
        
        // Already absolute
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        
        // Absolute path
        if (isset($url[0]) && $url[0] === '/') {
            return $this->site_url() . $url;
        }
        
        // Relative path
        return trailingslashit($this->site_url()) . ltrim($url, '/');
    }
    
    /**
     * Extract filename from URL for local storage
     *
     * Generates a sanitized filename, optionally including version parameter
     *
     * @param string $url URL to extract filename from
     * @return string Sanitized filename
     */
    public function filename_from_url($url) {
        $parsed = wp_parse_url($url);
        $path = $parsed['path'] ?? '';
        
        if (!$path) {
            return wp_hash($url);
        }
        
        $base = basename($path);
        $query = $parsed['query'] ?? '';
        
        // Include version parameter in filename if present
        if ($query && preg_match('/(?:^|&)ver=([^&]+)/i', $query, $m)) {
            $ver = sanitize_file_name($m[1]);
            $dot = strrpos($base, '.');
            if ($dot !== false) {
                $base = substr($base, 0, $dot) . ".{$ver}" . substr($base, $dot);
            } else {
                $base .= ".{$ver}";
            }
        }
        
        return sanitize_file_name($base ?: wp_hash($url));
    }
    
    /**
     * Get current URL depth (number of path segments)
     *
     * Used to calculate relative paths for assets
     *
     * @return int Depth level (0 for root)
     */
    public function get_current_depth() {
        // Safely get and sanitize REQUEST_URI
        $request_uri = isset($_SERVER['REQUEST_URI']) 
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) 
            : '/';
        
        $parsed_url = wp_parse_url($request_uri);
        $path = rtrim($parsed_url['path'] ?? '', '/');
        
        if ($path === '' || $path === '/') {
            return 0;
        }
        
        return substr_count(trim($path, '/'), '/') + 1;
    }
    
    /**
     * Normalize URL by resolving . and .. in path
     *
     * @param string $url URL to normalize
     * @return string Normalized URL
     */
    public function normalize_url($url) {
        $parts = wp_parse_url($url);
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
        
        // Rebuild URL
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
    * @param string $extension File extension (default: 'html')
    * @return string Full path to static file
    */
    public function get_static_file_path($extension = 'html') {
    	// Safely get and sanitize REQUEST_URI inline
    	$request_uri = isset($_SERVER['REQUEST_URI'])
    	    ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
    	    : '/';

    	$parsed_url = wp_parse_url($request_uri);
    	$path = rtrim($parsed_url['path'] ?? '', '/');

    	// Remove any potentially dangerous characters
    	$path = preg_replace('/[^a-zA-Z0-9\/\-_\.]/', '', $path);

    	// Root or homepage
    	if ($path === '' || $path === '/') {
    	    return STCW_STATIC_DIR . 'index.' . $extension;
    	}

	if (preg_match('/\.(xml|txt|json|xsl|rss|atom|rdf|ico|svg|webmanifest)$/i', $path, $matches)) {
            // This is already a file with extension - save it directly
            return STCW_STATIC_DIR . ltrim($path, '/');
	}

	// Regular page - create directory structure matching URL path
	$dir = STCW_STATIC_DIR . ltrim($path, '/');
	return $dir . '/index.' . $extension;
    }

}
