<?php
/**
 * WP-CLI commands for Static Cache Generator
 * 
 * SECURITY UPDATE:
 * - Added validate_output_path() to prevent path traversal attacks
 * - Updated zip() method to sanitize --output parameter
 * - Prevents writing files outside allowed directories
 * 
 * Provides command-line interface for managing static site generation,
 * processing assets, and checking status via WP-CLI.
 *
 * @package StaticCacheGenerator
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class SCG_CLI {
    
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
        update_option('scg_enabled', true);
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
        update_option('scg_enabled', false);
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
        $enabled = SCG_Core::is_enabled();
        $status = $enabled ? WP_CLI::colorize('%GEnabled%N') : WP_CLI::colorize('%RDisabled%N');
        WP_CLI::log("Static Generation: $status");
        
        if ($enabled) {
            $count = SCG_Core::count_static_files();
            $static_size = SCG_Core::format_bytes(SCG_Core::get_directory_size());
            
            WP_CLI::log("Static Files: $count");
            WP_CLI::log("Total Size: $static_size");
            WP_CLI::log("Pending Assets: " . count(get_option('scg_pending_assets', [])));
            WP_CLI::log("Downloaded Assets: " . count(get_option('scg_downloaded_assets', [])));
            WP_CLI::log("Static Directory: " . SCG_Core::get_static_dir());
            WP_CLI::log("Assets Directory: " . SCG_Core::get_assets_dir());
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
        $pending = get_option('scg_pending_assets', []);
        $count = count($pending);
        
        if ($count === 0) {
            WP_CLI::success("No pending assets to process.");
            return;
        }
        
        WP_CLI::log("Found $count pending assets. Processing...");
        
        $progress = \WP_CLI\Utils\make_progress_bar('Downloading assets', $count);
        
        $asset_handler = new SCG_Asset_Handler();
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
            delete_option('scg_pending_assets');
        } else {
            update_option('scg_pending_assets', array_values($pending), false);
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
        $static_dir = SCG_Core::get_static_dir();
        
        if (!is_dir($static_dir)) {
            WP_CLI::warning("Static directory doesn't exist.");
            return;
        }
        
        SCG_Core::clear_all_files();
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
     * : Specify output path for ZIP file. Must be an absolute path within
     * : allowed directories (wp-content/cache/, /tmp/, or /var/tmp/).
     * : Example: /tmp/mysite.zip
     *
     * ## EXAMPLES
     *
     *     # Default location (wp-content/cache/)
     *     wp scg zip
     *
     *     # Custom location in /tmp/
     *     wp scg zip --output=/tmp/mysite.zip
     *
     *     # Custom location in wp-content/cache/
     *     wp scg zip --output=/var/www/html/wp-content/cache/backup.zip
     *
     * @when after_wp_load
     */
    public function zip($args, $assoc_args) {
        $count = SCG_Core::count_static_files();
        
        if ($count === 0) {
            WP_CLI::error("No static files to package. Enable generation and browse your site first.");
        }
        
        WP_CLI::log("Creating ZIP archive...");
        $zip_file = SCG_Core::create_zip();
        
        if (!$zip_file || !file_exists($zip_file)) {
            WP_CLI::error("Failed to create ZIP file.");
        }
        
        $size = SCG_Core::format_bytes(filesize($zip_file));
        
        // Handle custom output path with security validation
        if (isset($assoc_args['output'])) {
            $requested_output = $assoc_args['output'];
            
            // Validate and sanitize the output path
            $validated_path = $this->validate_output_path($requested_output);
            
            if ($validated_path === false) {
                // Clean up the temporary ZIP file
                @unlink($zip_file);
                WP_CLI::error(
                    "Invalid output path. Path must be:\n" .
                    "  - An absolute path (starting with /)\n" .
                    "  - Within allowed directories: wp-content/cache/, /tmp/, /var/tmp/\n" .
                    "  - Not contain path traversal sequences (..)\n" .
                    "  - Have a writable parent directory"
                );
            }
            
            // Attempt to move the file to validated location
            if (rename($zip_file, $validated_path)) {
                $zip_file = $validated_path;
                WP_CLI::log("ZIP moved to custom location.");
            } else {
                WP_CLI::warning("Could not move ZIP to specified output path. Using default location.");
            }
        }
        
        WP_CLI::success("ZIP created: $zip_file ($size)");
    }
    
    /**
     * Validate and sanitize output path for ZIP file
     * 
     * Security checks:
     * 1. Must be absolute path (starts with /)
     * 2. Must be within allowed directories
     * 3. Cannot contain path traversal sequences (..)
     * 4. Parent directory must exist and be writable
     * 5. Resolves symlinks to prevent attacks
     * 
     * @param string $requested_path User-supplied output path
     * @return string|false Validated absolute path or false if invalid
     */
    private function validate_output_path($requested_path) {
        // Step 1: Basic sanitization
        $requested_path = trim($requested_path);
        
        // Step 2: Must be absolute path
        if (empty($requested_path) || $requested_path[0] !== '/') {
            WP_CLI::warning("Output path must be an absolute path (starting with /)");
            return false;
        }
        
        // Step 3: Check for path traversal attempts
        if (strpos($requested_path, '..') !== false) {
            WP_CLI::warning("Path traversal sequences (..) are not allowed");
            return false;
        }
        
        // Step 4: Define allowed base directories
        $allowed_bases = [
            realpath(WP_CONTENT_DIR . '/cache'),  // WordPress cache directory
            realpath('/tmp'),                      // System temp
            realpath('/var/tmp'),                  // Alternative temp
        ];
        
        // Remove any false values (directories that don't exist)
        $allowed_bases = array_filter($allowed_bases);
        
        // Step 5: Get the parent directory (where file will be created)
        $parent_dir = dirname($requested_path);
        
        // Step 6: Check if parent directory exists
        if (!is_dir($parent_dir)) {
            WP_CLI::warning("Parent directory does not exist: {$parent_dir}");
            return false;
        }
        
        // Step 7: Resolve real path (handles symlinks)
        $real_parent = realpath($parent_dir);
        
        if ($real_parent === false) {
            WP_CLI::warning("Cannot resolve parent directory path");
            return false;
        }
        
        // Step 8: Verify parent is within allowed directories
        $is_allowed = false;
        foreach ($allowed_bases as $allowed_base) {
            if (strpos($real_parent, $allowed_base) === 0) {
                $is_allowed = true;
                break;
            }
        }
        
        if (!$is_allowed) {
            WP_CLI::warning(
                "Output path must be within allowed directories:\n" .
                "  - " . WP_CONTENT_DIR . "/cache/\n" .
                "  - /tmp/\n" .
                "  - /var/tmp/"
            );
            return false;
        }
        
        // Step 9: Check parent directory is writable
        if (!is_writable($real_parent)) {
            WP_CLI::warning("Parent directory is not writable: {$real_parent}");
            return false;
        }
        
        // Step 10: Sanitize filename
        $filename = basename($requested_path);
        $safe_filename = sanitize_file_name($filename);
        
        // Step 11: Ensure .zip extension
        if (substr($safe_filename, -4) !== '.zip') {
            $safe_filename .= '.zip';
            WP_CLI::log("Added .zip extension to filename");
        }
        
        // Step 12: Construct final validated path
        $validated_path = $real_parent . '/' . $safe_filename;
        
        // Step 13: Final sanity check - ensure we didn't escape during construction
        $final_real = dirname($validated_path);
        $still_allowed = false;
        foreach ($allowed_bases as $allowed_base) {
            if (strpos($final_real, $allowed_base) === 0) {
                $still_allowed = true;
                break;
            }
        }
        
        if (!$still_allowed) {
            WP_CLI::warning("Final path validation failed (security check)");
            return false;
        }
        
        return $validated_path;
    }
}
