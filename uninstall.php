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
    $base_dir = WP_CONTENT_DIR . '/cache/stcw_static/';
    
    // Add site-specific subdirectory for multisite installations
    if (is_multisite()) {
        $base_dir .= 'site-' . get_current_blog_id() . '/';
    }
    
    return $base_dir;
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
    $static_dir = stcw_get_static_dir();
    if (is_dir($static_dir)) {
        stcw_delete_directory($static_dir);
    }
    
    // For multisite network-wide uninstall, clean up all sites
    if (is_multisite() && function_exists('get_sites')) {
        $sites = get_sites(['number' => 9999]);
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            
            // Delete options for this site
            stcw_delete_options();
            
            // Delete static directory for this site
            $site_static_dir = WP_CONTENT_DIR . '/cache/stcw_static/site-' . $site->blog_id . '/';
            if (is_dir($site_static_dir)) {
                stcw_delete_directory($site_static_dir);
            }
            
            // Clear any scheduled cron events for this site
            $timestamp = wp_next_scheduled('stcw_process_assets');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'stcw_process_assets');
            }
            
            restore_current_blog();
        }
        
        // Delete the parent stcw_static directory if empty or only has site subdirs
        $base_dir = WP_CONTENT_DIR . '/cache/stcw_static/';
        if (is_dir($base_dir)) {
            stcw_delete_directory($base_dir);
        }
    } else {
        // Single site - clear any scheduled cron events
        $timestamp = wp_next_scheduled('stcw_process_assets');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'stcw_process_assets');
        }
        
        // Delete the parent stcw_static directory
        $base_dir = WP_CONTENT_DIR . '/cache/stcw_static/';
        if (is_dir($base_dir)) {
            stcw_delete_directory($base_dir);
        }
    }
}

// Execute uninstall
stcw_uninstall();
