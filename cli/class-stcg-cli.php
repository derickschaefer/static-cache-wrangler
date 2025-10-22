<?php
/**
 * WP-CLI commands for Static Cache Generator
 * 
 * Provides command-line interface for managing static site generation,
 * processing assets, and checking status via WP-CLI.
 *
 * @package StaticCacheGenerator
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class STCG_CLI {
    
    /**
     * Enable static site generation
     *
     * ## EXAMPLES
     *
     *     wp scg enable
     *
     * @when after_wp_load
     */
    public function enable() {
        update_option('stcg_enabled', true);
        WP_CLI::success("Static site generation enabled.");
    }
    
    /**
     * Disable static site generation
     *
     * ## EXAMPLES
     *
     *     wp scg disable
     *
     * @when after_wp_load
     */
    public function disable() {
        update_option('stcg_enabled', false);
        WP_CLI::success("Static site generation disabled.");
    }
    
    /**
     * Show static site generation status
     *
     * ## EXAMPLES
     *
     *     wp scg status
     *
     * @when after_wp_load
     */
    public function status() {
        $enabled = STCG_Core::is_enabled();
        $status = $enabled ? WP_CLI::colorize('%GEnabled%N') : WP_CLI::colorize('%RDisabled%N');
        WP_CLI::log("Static Generation: $status");
        
        if ($enabled) {
            $count = STCG_Core::count_static_files();
            $static_size = STCG_Core::format_bytes(STCG_Core::get_directory_size());
            
            WP_CLI::log("Static Files: $count");
            WP_CLI::log("Total Size: $static_size");
            WP_CLI::log("Pending Assets: " . count(get_option('stcg_pending_assets', [])));
            WP_CLI::log("Downloaded Assets: " . count(get_option('stcg_downloaded_assets', [])));
            WP_CLI::log("Static Directory: " . STCG_Core::get_static_dir());
            WP_CLI::log("Assets Directory: " . STCG_Core::get_assets_dir());
        }
    }
    
    /**
     * Process all pending assets
     *
     * Downloads all queued CSS, JS, images, and fonts to local assets directory.
     *
     * ## EXAMPLES
     *
     *     wp scg process
     *
     * @when after_wp_load
     */
    public function process() {
        WP_CLI::log("Processing pending assets...");
        $pending = get_option('stcg_pending_assets', []);
        $count = count($pending);
        
        if ($count === 0) {
            WP_CLI::success("No pending assets to process.");
            return;
        }
        
        WP_CLI::log("Found $count pending assets. Processing...");
        
        $progress = \WP_CLI\Utils\make_progress_bar('Downloading assets', $count);
        
        $asset_handler = new STCG_Asset_Handler();
        $downloaded = 0;
        $failed = 0;
        
        foreach ($pending as $key => $url) {
            $result = $asset_handler->download_to_assets($url);
            if ($result !== false) {
                $downloaded++;
                unset($pending[$key]);
            } else {
                $failed++;
            }
            $progress->tick();
        }
        
        $progress->finish();
        
        // Update the pending list
        if (empty($pending)) {
            delete_option('stcg_pending_assets');
        } else {
            update_option('stcg_pending_assets', array_values($pending), false);
        }
        
        WP_CLI::success("Downloaded $downloaded assets successfully!");
        if ($failed > 0) {
            WP_CLI::warning("Failed to download $failed assets.");
        }
    }
    
    /**
     * Clear all static files
     *
     * Removes all generated HTML files and downloaded assets.
     * This action cannot be undone.
     *
     * ## EXAMPLES
     *
     *     wp scg clear
     *
     * @when after_wp_load
     */
    public function clear() {
        $static_dir = STCG_Core::get_static_dir();
        
        if (!is_dir($static_dir)) {
            WP_CLI::warning("Static directory doesn't exist.");
            return;
        }
        
        STCG_Core::clear_all_files();
        WP_CLI::success("All static files cleared.");
    }
    
    /**
     * Generate a ZIP file of the static site
     *
     * Creates a downloadable ZIP archive containing all static HTML files
     * and downloaded assets. The ZIP can be extracted and used offline.
     *
     * ## OPTIONS
     *
     * [--output=<path>]
     * : Specify output path for ZIP file. Defaults to wp-content/cache/
     *
     * ## EXAMPLES
     *
     *     wp scg zip
     *     wp scg zip --output=/tmp/mysite.zip
     *
     * @when after_wp_load
     */
    public function zip($args, $assoc_args) {
        $count = STCG_Core::count_static_files();
        
        if ($count === 0) {
            WP_CLI::error("No static files to package. Enable generation and browse your site first.");
        }
        
        WP_CLI::log("Creating ZIP archive...");
        $zip_file = STCG_Core::create_zip();
        
        if (!$zip_file || !file_exists($zip_file)) {
            WP_CLI::error("Failed to create ZIP file.");
        }
        
        $size = STCG_Core::format_bytes(filesize($zip_file));
        
        // Handle custom output path
        if (isset($assoc_args['output'])) {
            $output = $assoc_args['output'];
            
            // Initialize WP_Filesystem
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            
            // Use WP_Filesystem to move file
            if ($wp_filesystem->move($zip_file, $output, true)) {
                $zip_file = $output;
            } else {
                WP_CLI::warning("Could not move ZIP to specified output path.");
            }
        }
        
        WP_CLI::success("ZIP created: $zip_file ($size)");
    }
}
