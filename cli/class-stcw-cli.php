<?php
/**
 * WP-CLI commands for Static Cache Wrangler
 * 
 * Provides command-line interface for managing static site generation,
 * processing assets, and checking status via WP-CLI.
 *
 * @package StaticCacheWrangler
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class STCW_CLI {
    
    /**
     * Enable static site generation
     *
     * ## EXAMPLES
     *
     *     wp scw enable
     *
     * @when after_wp_load
     */
    public function enable() {
        update_option('stcw_enabled', true);
        WP_CLI::success("Static site generation enabled.");
    }
    
    /**
     * Disable static site generation
     *
     * ## EXAMPLES
     *
     *     wp scw disable
     *
     * @when after_wp_load
     */
    public function disable() {
        update_option('stcw_enabled', false);
        WP_CLI::success("Static site generation disabled.");
    }
    
    /**
     * Show static site generation status
     *
     * ## EXAMPLES
     *
     *     wp scw status
     *
     * @when after_wp_load
     */
    public function status() {
        $enabled = STCW_Core::is_enabled();
        $status = $enabled ? WP_CLI::colorize('%GEnabled%N') : WP_CLI::colorize('%RDisabled%N');
        WP_CLI::log("Static Generation: $status");
        
        // Show multisite information
        if (is_multisite()) {
            WP_CLI::log("Multisite: " . WP_CLI::colorize('%YYes%N') . " (Site ID: " . get_current_blog_id() . ")");
        } else {
            WP_CLI::log("Multisite: No");
        }
        
        if ($enabled) {
            $count = STCW_Core::count_static_files();
            $static_size = STCW_Core::format_bytes(STCW_Core::get_directory_size());
            
            WP_CLI::log("Static Files: $count");
            WP_CLI::log("Total Size: $static_size");
            WP_CLI::log("Pending Assets: " . count(get_option('stcw_pending_assets', [])));
            WP_CLI::log("Downloaded Assets: " . count(get_option('stcw_downloaded_assets', [])));
            WP_CLI::log("Static Directory: " . STCW_Core::get_static_dir());
            WP_CLI::log("Assets Directory: " . STCW_Core::get_assets_dir());
        }
    }
    
    /**
     * Process all pending assets
     *
     * Downloads all queued CSS, JS, images, and fonts to local assets directory.
     *
     * ## EXAMPLES
     *
     *     wp scw process
     *
     * @when after_wp_load
     */
    public function process() {
        WP_CLI::log("Processing pending assets...");
        $pending = get_option('stcw_pending_assets', []);
        $count = count($pending);
        
        if ($count === 0) {
            WP_CLI::success("No pending assets to process.");
            return;
        }
        
        WP_CLI::log("Found $count pending assets. Processing...");
        
        $progress = \WP_CLI\Utils\make_progress_bar('Downloading assets', $count);
        
        $asset_handler = new STCW_Asset_Handler();
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
            delete_option('stcw_pending_assets');
        } else {
            update_option('stcw_pending_assets', array_values($pending), false);
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
     *     wp scw clear
     *
     * @when after_wp_load
     */
    public function clear() {
        $static_dir = STCW_Core::get_static_dir();
        
        if (!is_dir($static_dir)) {
            WP_CLI::warning("Static directory doesn't exist.");
            return;
        }
        
        STCW_Core::clear_all_files();
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
     *     wp scw zip
     *     wp scw zip --output=/tmp/mysite.zip
     *
     * @when after_wp_load
     */
    public function zip($args, $assoc_args) {
        $count = STCW_Core::count_static_files();
        
        if ($count === 0) {
            WP_CLI::error("No static files to package. Enable generation and browse your site first.");
        }
        
        WP_CLI::log("Creating ZIP archive...");
        $zip_file = STCW_Core::create_zip();
        
        if (!$zip_file || !file_exists($zip_file)) {
            WP_CLI::error("Failed to create ZIP file.");
        }
        
        $size = STCW_Core::format_bytes(filesize($zip_file));
        
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
    
    /**
     * Generate sitemap.xml from cached static files
     *
     * Creates a sitemap.xml and sitemap.xsl file in the static directory root
     * based on the actual cached HTML files. Uses the file system as the source
     * of truth rather than the WordPress database.
     *
     * The sitemap includes:
     * - All cached index.html files as URLs
     * - Last modification times from file metadata
     * - Calculated priorities based on URL depth
     * - Reasonable change frequency defaults
     * - XSL stylesheet for browser viewing
     *
     * ## OPTIONS
     *
     * [--target-url=<url>]
     * : Target deployment URL for the static site.
     *
     * ## EXAMPLES
     *
     *     # Use WordPress site URL (default)
     *     wp scw sitemap
     *
     *     # Specify deployment URL
     *     wp scw sitemap --target-url=https://static.example.com
     *     wp scw sitemap --target-url=https://cdn.mysite.com
     *
     * @when after_wp_load
     */
    public function sitemap($args, $assoc_args) {
        WP_CLI::log("Generating sitemap from cached files...");
        
        // Get target URL from parameter or use WordPress site URL
        $target_url = '';
        if (isset($assoc_args['target-url']) && !empty($assoc_args['target-url'])) {
            $target_url = untrailingslashit($assoc_args['target-url']);
            WP_CLI::log("Using deployment URL: $target_url");
        }
        
        $generator = new STCW_Sitemap_Generator($target_url);
        $result = $generator->generate();
        
        if (!$result['success']) {
            WP_CLI::error($result['message']);
        }
        
        WP_CLI::success($result['message']);
        
        // Display file locations
        if (!empty($result['files'])) {
            WP_CLI::log("");
            WP_CLI::log("Files created:");
            foreach ($result['files'] as $type => $path) {
                WP_CLI::log("  " . ucfirst($type) . ": " . $path);
            }
        }
        
        // Provide helpful next steps with actual deployment URL
        $view_url = $target_url ? $target_url : home_url();
        WP_CLI::log("");
        WP_CLI::log("Next steps:");
        WP_CLI::log("  1. View sitemap in browser: " . $view_url . '/sitemap.xml');
        WP_CLI::log("  2. Submit to search engines (if deploying live)");
        WP_CLI::log("  3. Include in ZIP export: wp scw zip");
    }
    
    /**
     * Delete sitemap files
     *
     * Removes sitemap.xml and sitemap.xsl from the static directory.
     * Useful when you want to regenerate the sitemap or clean up before export.
     *
     * ## EXAMPLES
     *
     *     wp scw sitemap-delete
     *
     * @subcommand sitemap-delete
     * @when after_wp_load
     */
    public function sitemap_delete() {
        $generator = new STCW_Sitemap_Generator();
        $deleted = $generator->delete_sitemap();
        
        if ($deleted) {
            WP_CLI::success("Sitemap files deleted.");
        } else {
            WP_CLI::warning("No sitemap files found to delete.");
        }
    }
}
