<?php
/**
 * Admin dashboard and settings
 *
 * Handles the WordPress admin interface for Static Cache Wrangler,
 * including settings page, form processing, and file downloads.
 *
 * @package StaticCacheWrangler
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class STCW_Admin {
    
    /**
     * Initialize admin functionality
     *
     * Registers admin menu, hooks, and enqueues scripts/styles
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_stcw_toggle', [$this, 'handle_toggle']);
        add_action('admin_post_stcw_clear', [$this, 'handle_clear']);
        add_action('admin_post_stcw_download', [$this, 'handle_download']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our settings page
        if ($hook !== 'toplevel_page_static-cache-wrangler') {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'stcw-admin-style',
            STCW_PLUGIN_URL . 'admin/css/admin-style.css',
            [],
            STCW_VERSION
        );
        
        // Enqueue admin JS
        wp_enqueue_script(
            'stcw-admin-script',
            STCW_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery'],
            STCW_VERSION,
            true
        );
        
        // Localize script with data
        $pending_assets = get_option('stcw_pending_assets', []);
        $pending_count = is_array($pending_assets) ? count($pending_assets) : 0;
        
        wp_localize_script('stcw-admin-script', 'stcwAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('stcw_process'),
            'pendingCount' => $pending_count,
            'i18n' => [
                'assetsProcessed' => __('assets processed', 'static-cache-wrangler'),
                'complete' => __('Complete!', 'static-cache-wrangler'),
                'errorProcessing' => __('Error processing assets. Please try again.', 'static-cache-wrangler'),
            ]
        ]);
    }
    
    /**
     * Add admin menu page
     *
     * Creates the Static Cache menu item in WordPress admin
     */
    public function add_menu() {
        add_menu_page(
            __('Static Cache Wrangler', 'static-cache-wrangler'),
            __('Static Cache', 'static-cache-wrangler'),
            'manage_options',
            'static-cache-wrangler',
            [$this, 'render_page'],
            'dashicons-download',
            80
        );
    }
    
    /**
     * Render the admin settings page
     *
     * Checks user permissions and loads the admin page template
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'static-cache-wrangler'));
        }
        
        require_once STCW_PLUGIN_DIR . 'admin/views/admin-page.php';
    }
    
    /**
     * Handle enable/disable toggle form submission
     *
     * Processes the form to enable or disable static site generation
     */
    public function handle_toggle() {
        // Verify user permissions
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'static-cache-wrangler'));
        }
        
        // Verify nonce for security
        check_admin_referer('stcw_toggle_action', 'stcw_toggle_nonce');
        
        // Sanitize and validate the enable parameter
        $enable = isset($_POST['enable']) && $_POST['enable'] === '1';
        update_option('stcw_enabled', $enable);
        
        // Redirect with success message
        $message = $enable ? 'enabled' : 'disabled';
        wp_safe_redirect(
            add_query_arg(
                'message',
                $message,
                admin_url('admin.php?page=static-cache-wrangler')
            )
        );
        exit;
    }
    
    /**
     * Handle clear all files form submission
     *
     * Removes all generated static files and assets
     */
    public function handle_clear() {
        // Verify user permissions and nonce
        if (!current_user_can('manage_options') || !check_admin_referer('stcw_clear_action')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'static-cache-wrangler'));
        }
        
        // Clear all static files
        STCW_Core::clear_all_files();
        
        // Redirect with success message
        wp_safe_redirect(
            add_query_arg(
                'message',
                'cleared',
                admin_url('admin.php?page=static-cache-wrangler')
            )
        );
        exit;
    }
    
    /**
     * Handle ZIP download request
     *
     * Creates and serves a ZIP file of the complete static site
     */
    public function handle_download() {
        // Verify user permissions and nonce
        if (!current_user_can('manage_options') || !check_admin_referer('stcw_download_action')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'static-cache-wrangler'));
        }

        // Create the ZIP file
        $zip_file = STCW_Core::create_zip();
        
        // Serve the file if it was created successfully
        if ($zip_file && file_exists($zip_file)) {
            // Initialize WP_Filesystem
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            
            // Set headers for file download
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
            header('Content-Length: ' . filesize($zip_file));
            header('Cache-Control: no-cache, must-revalidate');
            
            // Output file contents using WP_Filesystem
            if ($wp_filesystem && $wp_filesystem->exists($zip_file)) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary ZIP file content
                echo $wp_filesystem->get_contents($zip_file);
            } else {
                wp_die(esc_html__('Could not read ZIP file.', 'static-cache-wrangler'));
            }
            
            // Clean up the temporary ZIP file
            wp_delete_file($zip_file);
            exit;
        } else {
            wp_die(esc_html__('Failed to create static site zip.', 'static-cache-wrangler'));
        }
    }
}
