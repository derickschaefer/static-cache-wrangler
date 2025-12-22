<?php
/**
 * Plugin Name: Static Cache Wrangler
 * Plugin URI: https://moderncli.dev/code/static-cache-wrangler/
 * Description: Generate static HTML files with fully local CSS/JS/Images/Fonts
 * Version: 2.1.3
 * Author: Derick Schaefer
 * Author URI: https://moderncli.dev/author/
 * Text Domain: static-cache-wrangler
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('STCW_VERSION', '2.1.3');
define('STCW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STCW_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Cache TTL (Time To Live) in seconds
 * 
 * Determines how long a cached static file is considered fresh before regeneration.
 * Default: 86400 seconds (24 hours)
 * 
 * Cached files are regenerated when:
 * - File age exceeds this TTL
 * - Plugin version is upgraded
 * - File has no metadata (pre-v2.1.1)
 * 
 * Can be overridden in wp-config.php:
 * 
 * Examples:
 *   define('STCW_CACHE_TTL', 3600);    // 1 hour
 *   define('STCW_CACHE_TTL', 604800);  // 1 week  
 *   define('STCW_CACHE_TTL', 0);       // Never expire based on time (version check only)
 * 
 * @since 2.1.1
 */
if (!defined('STCW_CACHE_TTL')) {
    define('STCW_CACHE_TTL', 86400); // 24 hours default
}

/**
 * Custom sitemap base URL
 * 
 * Use when deploying static site to a different domain than WordPress installation.
 * Default: Empty string (uses WordPress site URL)
 * 
 * This is useful when you build your static site on one domain but deploy it to another.
 * The sitemap will contain URLs for the deployment domain instead of the WordPress domain.
 * 
 * Examples:
 * 
 * CDN deployment:
 *   define('STCW_SITEMAP_URL', 'https://static.example.com');
 * 
 * Amazon S3 deployment:
 *   define('STCW_SITEMAP_URL', 'https://mybucket.s3.amazonaws.com');
 * 
 * Netlify deployment:
 *   define('STCW_SITEMAP_URL', 'https://mysite.netlify.app');
 * 
 * If not defined or empty, the sitemap will use your WordPress site URL.
 * 
 * @since 2.1.1
 */
if (!defined('STCW_SITEMAP_URL')) {
    define('STCW_SITEMAP_URL', '');
}

/**
 * Storage locations for static files and assets
 * ========================================
 * All generated files are stored in:
 *   wp-content/cache/stcw_static/
 * 
 * For MULTISITE installations, each site gets its own subdirectory:
 *   wp-content/cache/stcw_static/site-{blog_id}/
 * 
 * Design goals are to ensure:
 * ✓ Data persists through plugin updates (plugin folder deleted on update)
 * ✓ Files are not publicly accessible via plugin URL
 * ✓ Multisite compatibility with isolated storage per site
 * ✓ WordPress.org Plugin Directory compliance
 * ✓ Follows WordPress best practices for cache storage
 * ✓ No collision with other plugins (unique stcw_static namespace)
 * 
 * Storage structure (Single Site):
 * wp-content/
 *   └── cache/
 *       └── stcw_static/              ← Static HTML files (STCW_STATIC_DIR)
 *           ├── index.html
 *           ├── about/
 *           │   └── index.html
 *           └── assets/               ← Downloaded CSS/JS/Images (STCW_ASSETS_DIR)
 *               ├── style.css
 *               ├── script.js
 *               └── logo.png
 * 
 * Storage structure (Multisite):
 * wp-content/
 *   └── cache/
 *       └── stcw_static/
 *           ├── site-1/               ← Main site
 *           │   ├── index.html
 *           │   └── assets/
 *           ├── site-2/               ← Blog ID 2
 *           │   ├── index.html
 *           │   └── assets/
 *           └── site-3/               ← Blog ID 3
 *               ├── index.html
 *               └── assets/
 * 
 * Users can override these paths in wp-config.php if needed:
 * define('STCW_STATIC_DIR', WP_CONTENT_DIR . '/my-custom-path/');
 * define('STCW_ASSETS_DIR', WP_CONTENT_DIR . '/my-custom-assets/');
 */
if (!defined('STCW_STATIC_DIR')) {
    $stcw_base_dir = WP_CONTENT_DIR . '/cache/stcw_static/';
    
    // Add site-specific subdirectory for multisite installations
    if (is_multisite()) {
        $stcw_base_dir .= 'site-' . get_current_blog_id() . '/';
    }
    
    define('STCW_STATIC_DIR', $stcw_base_dir);
}

if (!defined('STCW_ASSETS_DIR')) {
    define('STCW_ASSETS_DIR', STCW_STATIC_DIR . 'assets/');
}

if (!defined('STCW_ASYNC_ASSETS')) {
    define('STCW_ASYNC_ASSETS', true);
}

// Autoload classes
spl_autoload_register(function($class) {
    if (strpos($class, 'STCW_') !== 0) {
        return;
    }
    
    $class_file = strtolower(str_replace('_', '-', $class));
    $paths = [
        STCW_PLUGIN_DIR . 'includes/',
        STCW_PLUGIN_DIR . 'admin/',
        STCW_PLUGIN_DIR . 'cli/',
    ];
    
    foreach ($paths as $path) {
        $file = $path . 'class-' . $class_file . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load logger utility
require_once STCW_PLUGIN_DIR . 'includes/stcw-logger.php';

/**
 * Initialize plugin
 */
function stcw_init() {
    // Core functionality
    $core = new STCW_Core();
    $core->init();
    
    // Admin interface
    if (is_admin()) {
        $admin = new STCW_Admin();
        $admin->init();
    }
    
    // Admin bar (frontend and backend)
    if (current_user_can('manage_options')) {
        $admin_bar = new STCW_Admin_Bar();
        $admin_bar->init();
    }
    
    // WP-CLI (keep command as 'scw')
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::add_command('scw', 'STCW_CLI');
    }
}
add_action('plugins_loaded', 'stcw_init');

/**
 * Add a Settings link on the Plugins page
 */
function stcw_add_settings_link($links) {
    $settings_url = admin_url('admin.php?page=static-cache-wrangler');
    $settings_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'static-cache-wrangler') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'stcw_add_settings_link');

/**
 * Activation hook
 * 
 * Creates directory structure in wp-content/cache/stcw_static/
 * For multisite: wp-content/cache/stcw_static/site-{blog_id}/
 * NOT in plugin directory
 */
register_activation_hook(__FILE__, function() {
    STCW_Core::create_directories();
});
