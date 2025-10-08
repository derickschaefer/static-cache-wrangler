<?php
/**
 * Uninstall Script for Static Cache Generator
 *
 * Fired when the plugin is uninstalled. Removes all plugin data,
 * static files, and options from the database.
 *
 * @package StaticCacheGenerator
 * @since 2.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define plugin constants if not already defined
if (!defined('SCG_STATIC_DIR')) {
    define('SCG_STATIC_DIR', WP_CONTENT_DIR . '/cache/_static/');
}
if (!defined('SCG_ASSETS_DIR')) {
    define('SCG_ASSETS_DIR', SCG_STATIC_DIR . 'assets/');
}

/**
 * Delete all plugin options from database
 */
function scg_delete_options() {
    delete_option('scg_enabled');
    delete_option('scg_pending_assets');
    delete_option('scg_downloaded_assets');
}

/**
 * Recursively delete directory and all contents
 *
 * Uses WP_Filesystem for proper file operations
 *
 * @param string $dir Directory path to delete
 * @return bool True on success, false on failure
 */
function scg_delete_directory($dir) {
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
function scg_uninstall() {
    // Delete all plugin options
    scg_delete_options();
    
    // Delete static files directory
    if (is_dir(SCG_STATIC_DIR)) {
        scg_delete_directory(SCG_STATIC_DIR);
    }
    
    // Clear any scheduled cron events
    $timestamp = wp_next_scheduled('scg_process_assets');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'scg_process_assets');
    }
}

// Execute uninstall
scg_uninstall();
