<?php
/**
 * Plugin Name: Static Cache Generator
 * Plugin URI: https://moderncli.dev/code/static-cache-generator/
 * Description: Generate static HTML files with fully local CSS/JS/Images/Fonts
 * Version: 2.0.2
 * Author: Derick Schaefer
 * Author URI: https://moderncli.dev/author/
 * Text Domain: static-cache-generator
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('SCG_VERSION', '2.0.2');
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

// Load logger utility
require_once SCG_PLUGIN_DIR . 'includes/scg-logger.php';

// Initialize plugin
function scg_init() {
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

/**
 * Add a Settings link on the Plugins page
 */
function scg_add_settings_link( $links ) {
    $settings_url = admin_url( 'options-general.php?page=static-cache-generator' ); // Adjust if your admin slug differs
    $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'static-cache-generator' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'scg_add_settings_link' );

// Activation hook
register_activation_hook(__FILE__, function() {
    SCG_Core::create_directories();
});
