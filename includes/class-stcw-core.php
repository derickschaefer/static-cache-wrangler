<?php
/**
 * Core functionality for Static Cache Wrangler
 *
 * Handles initialization, directory management, file operations,
 * and coordination between components.
 *
 * @package StaticCacheWrangler
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class STCW_Core {
    
    /**
     * Generator instance
     * @var STCW_Generator
     */
    private $generator;
    
    /**
     * Asset handler instance
     * @var STCW_Asset_Handler
     */
    private $asset_handler;
    
    /**
     * Initialize core functionality
     *
     * Sets up directory structure, initializes components,
     * and registers WordPress hooks.
     */
    public function init() {
        self::create_directories();
        
        $this->generator = new STCW_Generator();
        $this->asset_handler = new STCW_Asset_Handler();
        
        // Hook into WordPress
        add_action('wp', [$this->generator, 'start_output'], 1);
        add_action('stcw_process_assets', [$this->asset_handler, 'download_queued_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_stcw_process_pending', [$this->asset_handler, 'ajax_process_pending']);
        
        // Enqueue auto-process script on frontend and admin (when needed)
        add_action('admin_footer', [$this, 'enqueue_auto_process_script']);
        add_action('wp_footer', [$this, 'enqueue_auto_process_script']);
    }
    
    /**
     * Enqueue auto-process script for background asset processing
     */
    public function enqueue_auto_process_script() {
        if (!current_user_can('manage_options') || !self::is_enabled()) {
            return;
        }
        
        $pending = count(get_option('stcw_pending_assets', []));
        if ($pending === 0) {
            return;
        }
        
        wp_enqueue_script(
            'stcw-auto-process',
            STCW_PLUGIN_URL . 'includes/js/auto-process.js',
            [],
            STCW_VERSION,
            true
        );
        
        wp_localize_script('stcw-auto-process', 'stcwAutoProcess', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('stcw_process')
        ]);
    }
    
    /**
     * Create necessary directories
     *
     * Creates static files and assets directories with proper permissions
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
        return STCW_STATIC_DIR;
    }
    
    /**
     * Get assets directory path
     * 
     * @return string Absolute path to assets directory
     */
    public static function get_assets_dir() {
        return STCW_ASSETS_DIR;
    }
    
    /**
     * Check if static generation is enabled
     * 
     * @return bool True if enabled
     */
    public static function is_enabled() {
        return (bool) get_option('stcw_enabled', false);
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
            stcw_log_debug('Error counting files: ' . $e->getMessage());
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
            stcw_log_debug('Error calculating size: ' . $e->getMessage());
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
     */
    public static function clear_all_files() {
        $static_dir = self::get_static_dir();
        $assets_dir = self::get_assets_dir();
        
        // Clear static directory
        if (is_dir($static_dir)) {
            self::delete_directory_contents($static_dir);
        }
        
        // Clear assets directory
        if (is_dir($assets_dir)) {
            self::delete_directory_contents($assets_dir);
        }
        
        // Reset options
        delete_option('stcw_pending_assets');
        delete_option('stcw_downloaded_assets');
        
        // Recreate directories
        self::create_directories();
    }
    
    /**
     * Recursively delete directory contents
     * 
     * @param string $dir Directory path
     */
    private static function delete_directory_contents($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        // Initialize WP_Filesystem
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        // Use WP_Filesystem to remove directory recursively
        if ($wp_filesystem && $wp_filesystem->is_dir($dir)) {
            $wp_filesystem->rmdir($dir, true);
        }
    }
    
    /**
     * Create ZIP file of static site
     * 
     * @return string|false Path to ZIP file or false on failure
     */
    public static function create_zip() {
        if (!class_exists('ZipArchive')) {
            stcw_log_debug('Error: ZipArchive not available');
            return false;
        }

        $zip_file = trailingslashit(WP_CONTENT_DIR . '/cache') . 'static-site-' . current_time('Y-m-d-H-i-s') . '.zip';
        
        // Ensure cache directory exists
        wp_mkdir_p(dirname($zip_file));
        
        $zip = new ZipArchive();

        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            stcw_log_debug('Error: Could not create ZIP file');
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
     * Recursively add directory to ZIP
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
                $relative = ltrim(str_replace($dir, '', $path), '/');
                
                if ($base_path) {
                    $relative = $base_path . '/' . $relative;
                }
                
                if ($file->isFile()) {
                    $zip->addFile($path, $relative);
                }
            }
        } catch (Exception $e) {
            stcw_log_debug('Error adding to ZIP: ' . $e->getMessage());
        }
    }
}
