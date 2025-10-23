<?php
/**
 * Plugin Name: Static Cache Wrangler
 * Plugin URI: https://moderncli.dev/code/static-cache-wrangler/
 * Description: Generate static HTML files with fully local CSS/JS/Images/Fonts
 * Version: 2.0.4
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
define('STCW_VERSION', '2.0.4');
define('STCW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STCW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('STCW_STATIC_DIR', WP_CONTENT_DIR . '/cache/_static/');
define('STCW_ASSETS_DIR', STCW_STATIC_DIR . 'assets/');
define('STCW_ASYNC_ASSETS', true);

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
 */
register_activation_hook(__FILE__, function() {
    STCW_Core::create_directories();
});
