<?php
/**
 * Uninstall Script for Static Cache Wrangler
 *
 * Fired when the plugin is uninstalled. Removes all plugin data,
 * static files, and options from the database.
 *
 * @package StaticCacheWrangler
 * @since 2.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define plugin constants if not already defined
if (!defined('STCW_STATIC_DIR')) {
    define('STCW_STATIC_DIR', WP_CONTENT_DIR . '/cache/_static/');
}
if (!defined('STCW_ASSETS_DIR')) {
    define('STCW_ASSETS_DIR', STCW_STATIC_DIR . 'assets/');
}

/**
 * Delete all plugin options from database
 */
function stcw_delete_options() {
    delete_option('stcw_enabled');
    delete_option('stcw_pending_assets');
    delete_option('stcw_downloaded_assets');
}

/**
 * Recursively delete directory and all contents
 *
 * Uses WP_Filesystem for proper file operations
 *
 * @param string $dir Directory path to delete
 * @return bool True on success, false on failure
 */
function stcw_delete_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    // Initialize WP_Filesystem
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }
    
    // Use WP_Filesystem to delete directory recursively
    if ($wp_filesystem && $wp_filesystem->is_dir($dir)) {
        return $wp_filesystem->rmdir($dir, true);
    }
    
    return false;
}

/**
 * Main uninstall routine
 */
function stcw_uninstall() {
    // Delete all plugin options
    stcw_delete_options();
    
    // Delete static files directory
    if (is_dir(STCW_STATIC_DIR)) {
        stcw_delete_directory(STCW_STATIC_DIR);
    }
    
    // Clear any scheduled cron events
    $timestamp = wp_next_scheduled('stcw_process_assets');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'stcw_process_assets');
    }
}

// Execute uninstall
stcw_uninstall();
