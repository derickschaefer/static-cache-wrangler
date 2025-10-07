<?php
/**
 * URL manipulation and path helpers
 */

if (!defined('ABSPATH')) exit;

class SCG_URL_Helper {
    
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
    
    public function filename_from_url($url) {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return wp_hash($url);
        }
        
        $base = basename($path);
        $query = parse_url($url, PHP_URL_QUERY);
        
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
    
    public function get_static_file_path($extension = 'html') {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsed_url = parse_url($request_uri);
        $path = rtrim($parsed_url['path'] ?? '', '/');
        $path = preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $path);
        
        if ($path === '' || $path === '/') {
            return SCG_STATIC_DIR . 'index.' . $extension;
        }
        
        $dir = SCG_STATIC_DIR . ltrim($path, '/');
        return $dir . '/index.' . $extension;
    }
}
