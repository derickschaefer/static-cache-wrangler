<?php
/**
 * Uninstall script for Static Cache Generator
 * 
 * Fired when the plugin is uninstalled via WordPress admin.
 * Cleans up all plugin data including:
 * - Options from wp_options table
 * - Static files directory
 * - Assets directory
 * - Scheduled cron events
 * 
 * @package StaticCacheGenerator
 * @since 2.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin options
 */
function scg_delete_options() {
    delete_option('scg_enabled');
    delete_option('scg_pending_assets');
    delete_option('scg_downloaded_assets');
    
    // For multisite, delete from all sites
    if (is_multisite()) {
        global $wpdb;
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            delete_option('scg_enabled');
            delete_option('scg_pending_assets');
            delete_option('scg_downloaded_assets');
            restore_current_blog();
        }
    }
}

/**
 * Recursively delete directory and all contents
 * 
 * @param string $dir Directory to delete
 * @return bool True on success, false on failure
 */
function scg_delete_directory($dir) {
    if (!is_dir($dir)) {
        return true;
    }
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            // Skip symlinks for security
            if (is_link($file->getPathname())) {
                continue;
            }
            
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        
        // Remove the directory itself
        @rmdir($dir);
        
        return true;
    } catch (Exception $e) {
        error_log('SCG Uninstall: Error deleting directory: ' . $e->getMessage());
        return false;
    }
}

/**
 * Clean up static files and directories
 */
function scg_delete_static_files() {
    $static_dir = WP_CONTENT_DIR . '/cache/_static/';
    
    if (is_dir($static_dir)) {
        scg_delete_directory($static_dir);
    }
    
    // Note: We don't delete the cache directory itself as other plugins might use it
}

/**
 * Clear any scheduled cron events
 */
function scg_clear_scheduled_events() {
    // Remove our scheduled event
    $timestamp = wp_next_scheduled('scg_process_assets');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'scg_process_assets');
    }
    
    // Clear any remaining instances
    wp_clear_scheduled_hook('scg_process_assets');
}

/**
 * Main uninstall routine
 */
function scg_uninstall() {
    // Security check - only run if we have proper permissions
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    // Clear scheduled events first
    scg_clear_scheduled_events();
    
    // Delete all options
    scg_delete_options();
    
    // Delete static files and directories
    scg_delete_static_files();
    
    // Optional: Add transient to show "data deleted" message on next admin load
    set_transient('scg_uninstalled', true, 60);
}

// Run the uninstall
scg_uninstall();
