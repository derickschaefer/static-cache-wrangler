<?php
/**
 * Plugin Name: Static Cache Generator
 * Description: Generate static HTML files with fully local CSS/JS/Images/Fonts
 * Version: 2.0
 * Author: Derick Schaefer
 * Text Domain: static-cache-generator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('SCG_VERSION', '2.0');
define('SCG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCG_STATIC_DIR', WP_CONTENT_DIR . '/cache/_static/');
define('SCG_ASSETS_DIR', SCG_STATIC_DIR . 'assets/');
define('SCG_ASYNC_ASSETS', true);

// Autoload classes
spl_autoload_register(function($class) {
    if (strpos($class, 'SCG_') !== 0) {
        return;
    }
    
    $class_file = strtolower(str_replace('_', '-', $class));
    $paths = [
        SCG_PLUGIN_DIR . 'includes/',
        SCG_PLUGIN_DIR . 'admin/',
        SCG_PLUGIN_DIR . 'cli/',
    ];
    
    foreach ($paths as $path) {
        $file = $path . 'class-' . $class_file . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Initialize plugin
function scg_init() {
    // Load text domain for translations
    load_plugin_textdomain('static-cache-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Core functionality
    $core = new SCG_Core();
    $core->init();
    
    // Admin interface
    if (is_admin()) {
        $admin = new SCG_Admin();
        $admin->init();
    }
    
    // Admin bar (frontend and backend)
    if (current_user_can('manage_options')) {
        $admin_bar = new SCG_Admin_Bar();
        $admin_bar->init();
    }
    
    // WP-CLI
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::add_command('scg', 'SCG_CLI');
    }
}
add_action('plugins_loaded', 'scg_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    SCG_Core::create_directories();
});

// Add settings link on plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('admin.php?page=static-cache-generator')),
        esc_html__('Settings', 'static-cache-generator')
    );
    
    // Add settings link at the beginning of the array
    array_unshift($links, $settings_link);
    
    return $links;
});

// Add additional plugin meta links
add_filter('plugin_row_meta', function($links, $file) {
    if (plugin_basename(__FILE__) !== $file) {
        return $links;
    }
    
    $additional_links = [
        sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url('https://github.com/yourusername/static-cache-generator/wiki'),
            esc_html__('Documentation', 'static-cache-generator')
        ),
        sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url('https://github.com/yourusername/static-cache-generator/issues'),
            esc_html__('Support', 'static-cache-generator')
        ),
    ];
    
    return array_merge($links, $additional_links);
}, 10, 2);
