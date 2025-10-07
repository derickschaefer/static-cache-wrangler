<?php
/**
 * ============================================================================
 * FILE: includes/class-scg-core.php
 * 
 * SECURITY UPDATE:
 * - Added validate_deletion_path() to prevent path traversal attacks
 * - Updated delete_directory_contents() with symlink protection
 * - Added realpath() validation to ensure paths stay within allowed directories
 * - Removed @ error suppression, added proper error handling
 * - Return boolean success/failure indicators
 * ============================================================================
 */

if (!defined('ABSPATH')) exit;

class SCG_Core {
    
    private $generator;
    private $asset_handler;
    
    /**
     * Initialize core functionality
     */
    public function init() {
        self::create_directories();
        
        $this->generator = new SCG_Generator();
        $this->asset_handler = new SCG_Asset_Handler();
        
        // Hook into WordPress
        add_action('wp', [$this->generator, 'start_output'], 1);
        add_action('scg_process_assets', [$this->asset_handler, 'download_queued_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_scg_process_pending', [$this->asset_handler, 'ajax_process_pending']);
        
        // Frontend/admin scripts
        add_action('admin_footer', [$this, 'admin_footer_script']);
        add_action('wp_footer', [$this, 'admin_footer_script']);
    }
    
    /**
     * Create necessary directories
     */
    public static function create_directories() {
        $directories = [self::get_static_dir(), self::get_assets_dir()];
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }
    
    /**
     * Get static files directory path
     * 
     * @return string Absolute path to static directory
     */
    public static function get_static_dir() {
        return SCG_STATIC_DIR;
    }
    
    /**
     * Get assets directory path
     * 
     * @return string Absolute path to assets directory
     */
    public static function get_assets_dir() {
        return SCG_ASSETS_DIR;
    }
    
    /**
     * Check if static generation is enabled
     * 
     * @return bool True if enabled
     */
    public static function is_enabled() {
        return (bool) get_option('scg_enabled', false);
    }
    
    /**
     * Count static HTML files
     * 
     * @return int Number of HTML files
     */
    public static function count_static_files() {
        $count = 0;
        $dir = self::get_static_dir();
        
        if (!is_dir($dir)) {
            return 0;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'html') {
                    $count++;
                }
            }
        } catch (Exception $e) {
            error_log('SCG Error counting files: ' . $e->getMessage());
        }
        
        return $count;
    }
    
    /**
     * Get directory size in bytes
     * 
     * @param string|null $path Optional specific path, defaults to static dir
     * @return int Size in bytes
     */
    public static function get_directory_size($path = null) {
        if ($path === null) {
            $path = self::get_static_dir();
        }
        
        $size = 0;
        
        if (!is_dir($path)) {
            return 0;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (Exception $e) {
            error_log('SCG Error calculating size: ' . $e->getMessage());
        }
        
        return $size;
    }
    
    /**
     * Format bytes to human readable string
     * 
     * @param int $bytes Number of bytes
     * @param int $precision Decimal precision
     * @return string Formatted string (e.g., "1.5 MB")
     */
    public static function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Clear all static files and reset options
     * 
     * @return bool True on success, false on failure
     */
    public static function clear_all_files() {
        $static_dir = self::get_static_dir();
        $assets_dir = self::get_assets_dir();
        
        $success = true;
        
        // Clear static directory with validation
        if (is_dir($static_dir)) {
            if (!self::delete_directory_contents($static_dir)) {
                error_log('[SCG] Failed to clear static directory: ' . $static_dir);
                $success = false;
            }
        }
        
        // Clear assets directory with validation
        if (is_dir($assets_dir)) {
            if (!self::delete_directory_contents($assets_dir)) {
                error_log('[SCG] Failed to clear assets directory: ' . $assets_dir);
                $success = false;
            }
        }
        
        // Reset options even if file deletion partially failed
        delete_option('scg_pending_assets');
        delete_option('scg_downloaded_assets');
        
        // Recreate directories
        self::create_directories();
        
        return $success;
    }
    
    /**
     * Validate path is safe for deletion operations
     * 
     * Security checks:
     * 1. Path must exist
     * 2. Must resolve to real path (no broken symlinks)
     * 3. Must be within WP_CONTENT_DIR/cache/ directory
     * 4. Cannot be a symlink itself (prevents symlink attacks)
     * 5. Must be a directory (sanity check)
     * 
     * @param string $dir Directory path to validate
     * @return bool True if path is safe to delete from
     */
    private static function validate_deletion_path($dir) {
        // Step 1: Path must exist
        if (!file_exists($dir)) {
            error_log('[SCG] Validation failed: Path does not exist: ' . $dir);
            return false;
        }
        
        // Step 2: Must be a directory (not a file)
        if (!is_dir($dir)) {
            error_log('[SCG] Validation failed: Not a directory: ' . $dir);
            return false;
        }
        
        // Step 3: Cannot be a symlink (prevents symlink attack on directory itself)
        if (is_link($dir)) {
            error_log('[SCG] Security: Refusing to delete symlinked directory: ' . $dir);
            return false;
        }
        
        // Step 4: Resolve to real path (handles any remaining symlinks in path)
        $real_dir = realpath($dir);
        if ($real_dir === false) {
            error_log('[SCG] Validation failed: Cannot resolve real path for: ' . $dir);
            return false;
        }
        
        // Step 5: Define allowed base directory (only cache directory)
        $allowed_base = realpath(WP_CONTENT_DIR . '/cache');
        
        if ($allowed_base === false) {
            error_log('[SCG] Validation failed: Cannot resolve cache directory path');
            return false;
        }
        
        // Step 6: Verify path is within allowed directory
        // Use strpos to check if real path starts with allowed base
        if (strpos($real_dir, $allowed_base) !== 0) {
            error_log('[SCG] Security: Refusing to delete outside cache directory: ' . $real_dir);
            error_log('[SCG] Allowed base: ' . $allowed_base);
            return false;
        }
        
        // Step 7: Additional sanity check - ensure we're not deleting the cache root itself
        if ($real_dir === $allowed_base) {
            error_log('[SCG] Security: Refusing to delete cache root directory');
            return false;
        }
        
        return true;
    }
    
    /**
     * Recursively delete directory contents with security validation
     * 
     * Security features:
     * - Validates path before any deletion
     * - Checks each file/directory is not a symlink
     * - Only deletes within allowed directories
     * - Proper error handling (no @ suppression)
     * - Returns success/failure status
     * 
     * @param string $dir Directory path to clear
     * @return bool True on success, false on failure
     */
    private static function delete_directory_contents($dir) {
        // SECURITY: Validate path before any deletion operations
        if (!self::validate_deletion_path($dir)) {
            error_log('[SCG] Path validation failed, aborting deletion: ' . $dir);
            return false;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            
            $delete_count = 0;
            $skip_count = 0;
            
            foreach ($iterator as $file) {
                $file_path = $file->getPathname();
                
                // SECURITY: Skip symlinks (don't follow them)
                if (is_link($file_path)) {
                    error_log('[SCG] Skipping symlink: ' . $file_path);
                    $skip_count++;
                    continue;
                }
                
                // SECURITY: Double-check each file is still within allowed directory
                $real_file_path = realpath($file_path);
                if ($real_file_path === false) {
                    error_log('[SCG] Cannot resolve real path, skipping: ' . $file_path);
                    $skip_count++;
                    continue;
                }
                
                $allowed_base = realpath(WP_CONTENT_DIR . '/cache');
                if (strpos($real_file_path, $allowed_base) !== 0) {
                    error_log('[SCG] File outside allowed directory, skipping: ' . $real_file_path);
                    $skip_count++;
                    continue;
                }
                
                // Delete file or directory
                if ($file->isDir()) {
                    if (rmdir($file_path)) {
                        $delete_count++;
                    } else {
                        error_log('[SCG] Failed to delete directory: ' . $file_path);
                    }
                } else {
                    if (unlink($file_path)) {
                        $delete_count++;
                    } else {
                        error_log('[SCG] Failed to delete file: ' . $file_path);
                    }
                }
            }
            
            if ($skip_count > 0) {
                error_log("[SCG] Deleted {$delete_count} items, skipped {$skip_count} items");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('[SCG] Exception during directory deletion: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create ZIP file of static site
     * 
     * @return string|false Path to ZIP file or false on failure
     */
    public static function create_zip() {
        if (!class_exists('ZipArchive')) {
            error_log('SCG Error: ZipArchive not available');
            return false;
        }

        $zip_file = trailingslashit(WP_CONTENT_DIR . '/cache') . 'static-site-' . date('Y-m-d-H-i-s') . '.zip';
        
        // Ensure cache directory exists
        wp_mkdir_p(dirname($zip_file));
        
        $zip = new ZipArchive();

        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            error_log('SCG Error: Could not create ZIP file');
            return false;
        }

        $static_dir = self::get_static_dir();
        $assets_dir = self::get_assets_dir();

        // Add static files
        if (is_dir($static_dir)) {
            self::add_directory_to_zip($zip, $static_dir, '');
        }
        
        // Add assets
        if (is_dir($assets_dir)) {
            self::add_directory_to_zip($zip, $assets_dir, 'assets');
        }

        $zip->close();
        return $zip_file;
    }
    
    /**
     * Recursively add directory to ZIP with symlink protection
     * 
     * @param ZipArchive $zip ZIP archive object
     * @param string $dir Source directory
     * @param string $base_path Base path in ZIP
     */
    private static function add_directory_to_zip($zip, $dir, $base_path = '') {
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                $path = $file->getPathname();
                
                // SECURITY: Skip symlinks to prevent including files outside intended directory
                if (is_link($path)) {
                    error_log('[SCG] Skipping symlink in ZIP: ' . $path);
                    continue;
                }
                
                $relative = ltrim(str_replace($dir, '', $path), '/');
                
                if ($base_path) {
                    $relative = $base_path . '/' . $relative;
                }
                
                if ($file->isFile()) {
                    $zip->addFile($path, $relative);
                }
            }
        } catch (Exception $e) {
            error_log('SCG Error adding to ZIP: ' . $e->getMessage());
        }
    }
    
    /**
     * Admin footer script for auto-processing
     */
    public function admin_footer_script() {
        if (!current_user_can('manage_options') || !self::is_enabled()) {
            return;
        }
        
        $pending = count(get_option('scg_pending_assets', []));
        if ($pending === 0) {
            return;
        }
        
        $nonce = wp_create_nonce('scg_process');
        ?>
        <script type="text/javascript">
        window.ssgProcessNow = function() {
            if (confirm('Process pending assets now? This will download CSS, JS, images, and fonts.')) {
                processPendingAssets(true);
            }
        };
        
        (function() {
            let processing = false;
            
            function processPendingAssets(manual = false) {
                if (processing) {
                    if (manual) alert('Already processing...');
                    return;
                }
                processing = true;
                
                if (manual) {
                    console.log('SSG: Manually processing assets...');
                }
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'scg_process_pending',
                        nonce: '<?php echo $nonce; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    processing = false;
                    console.log('SSG:', data);
                    
                    if (data.success && data.data.remaining > 0) {
                        setTimeout(() => processPendingAssets(manual), 2000);
                    } else if (data.success && data.data.remaining === 0) {
                        console.log('SSG: All assets processed!');
                        if (manual) {
                            alert('All assets processed successfully!');
                            location.reload();
                        }
                    }
                })
                .catch(error => {
                    processing = false;
                    console.error('SSG error:', error);
                    if (manual) {
                        alert('Error processing assets. Check console for details.');
                    }
                });
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(() => processPendingAssets(false), 3000);
                });
            } else {
                setTimeout(() => processPendingAssets(false), 3000);
            }
        })();
        </script>
        <?php
    }
}
