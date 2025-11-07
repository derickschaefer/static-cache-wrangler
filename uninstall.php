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

/**
 * Get the static directory path (multisite-aware)
 *
 * @return string Static directory path
 */
function stcw_get_static_dir() {
    $stcw_uninstall_base_dir = WP_CONTENT_DIR . '/cache/stcw_static/';
    
    // Add site-specific subdirectory for multisite installations
    if (is_multisite()) {
        $stcw_uninstall_base_dir .= 'site-' . get_current_blog_id() . '/';
    }
    
    return $stcw_uninstall_base_dir;
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
    
    // Delete static files directory for current site
    $stcw_uninstall_static_dir = stcw_get_static_dir();
    if (is_dir($stcw_uninstall_static_dir)) {
        stcw_delete_directory($stcw_uninstall_static_dir);
    }
    
    // For multisite network-wide uninstall, clean up all sites
    if (is_multisite() && function_exists('get_sites')) {
        $stcw_uninstall_sites = get_sites(['number' => 9999]);
        foreach ($stcw_uninstall_sites as $stcw_uninstall_site) {
            switch_to_blog($stcw_uninstall_site->blog_id);
            
            // Delete options for this site
            stcw_delete_options();
            
            // Delete static directory for this site
            $stcw_uninstall_site_static_dir = WP_CONTENT_DIR . '/cache/stcw_static/site-' . $stcw_uninstall_site->blog_id . '/';
            if (is_dir($stcw_uninstall_site_static_dir)) {
                stcw_delete_directory($stcw_uninstall_site_static_dir);
            }
            
            // Clear any scheduled cron events for this site
            $stcw_uninstall_timestamp = wp_next_scheduled('stcw_process_assets');
            if ($stcw_uninstall_timestamp) {
                wp_unschedule_event($stcw_uninstall_timestamp, 'stcw_process_assets');
            }
            
            restore_current_blog();
        }
        
        // Delete the parent stcw_static directory if empty or only has site subdirs
        $stcw_uninstall_parent_dir = WP_CONTENT_DIR . '/cache/stcw_static/';
        if (is_dir($stcw_uninstall_parent_dir)) {
            stcw_delete_directory($stcw_uninstall_parent_dir);
        }
    } else {
        // Single site - clear any scheduled cron events
        $stcw_uninstall_timestamp = wp_next_scheduled('stcw_process_assets');
        if ($stcw_uninstall_timestamp) {
            wp_unschedule_event($stcw_uninstall_timestamp, 'stcw_process_assets');
        }
        
        // Delete the parent stcw_static directory
        $stcw_uninstall_parent_dir = WP_CONTENT_DIR . '/cache/stcw_static/';
        if (is_dir($stcw_uninstall_parent_dir)) {
            stcw_delete_directory($stcw_uninstall_parent_dir);
        }
    }
}

// Execute uninstall
stcw_uninstall();
