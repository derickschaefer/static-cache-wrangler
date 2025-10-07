<?php
/**
 * Admin dashboard and settings
 */

if (!defined('ABSPATH')) exit;

class SCG_Admin {
    
    public function init() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_scg_toggle', [$this, 'handle_toggle']);
        add_action('admin_post_scg_clear', [$this, 'handle_clear']);
        add_action('admin_post_scg_download', [$this, 'handle_download']);
    }
    
    public function add_menu() {
        add_menu_page(
            __('Static Cache Generator', 'static-cache-generator'),
            __('Static Cache', 'static-cache-generator'),
            'manage_options',
            'static-cache-generator',
            [$this, 'render_page'],
            'dashicons-download',
            80
        );
    }
    
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        require_once SCG_PLUGIN_DIR . 'admin/views/admin-page.php';
    }
    
    public function handle_toggle() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.'));
        }
        
        check_admin_referer('scg_toggle_action', 'scg_toggle_nonce');
        
        $enable = isset($_POST['enable']) && $_POST['enable'] === '1';
        update_option('scg_enabled', $enable);
        
        $message = $enable ? 'enabled' : 'disabled';
        wp_safe_redirect(admin_url('admin.php?page=static-cache-generator&message=' . $message));
        exit;
    }
    
    public function handle_clear() {
        if (!current_user_can('manage_options') || !check_admin_referer('scg_clear_action')) {
            wp_die('You are not allowed to perform this action.');
        }
        
        SCG_Core::clear_all_files();
        
        wp_safe_redirect(admin_url('admin.php?page=static-cache-generator&message=cleared'));
        exit;
    }
    
    public function handle_download() {
        if (!current_user_can('manage_options') || !check_admin_referer('scg_download_action')) {
            wp_die('You are not allowed to perform this action.');
        }

        $zip_file = SCG_Core::create_zip();
        
        if ($zip_file && file_exists($zip_file)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
            header('Content-Length: ' . filesize($zip_file));
            header('Cache-Control: no-cache, must-revalidate');
            readfile($zip_file);
            @unlink($zip_file);
            exit;
        } else {
            wp_die('Failed to create static site zip.');
        }
    }
}
