<?php
/**
 * Admin bar integration
 *
 * Adds Static Cache Wrangler menu and status to WordPress admin bar
 *
 * @package StaticCacheWrangler
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class STCW_Admin_Bar {
    
    /**
     * Initialize admin bar integration
     */
    public function init() {
        add_action('admin_bar_menu', [$this, 'add_menu'], 100);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_admin_bar_script']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_bar_script']);
    }
    
    /**
     * Enqueue admin bar JavaScript
     *
     * Only loads when admin bar is showing and user has permissions.
     * Uses unique filename to avoid conflicts with WordPress core admin-bar.js
     */
    public function enqueue_admin_bar_script() {
        // Only load if user can manage options and admin bar is showing
        if (!current_user_can('manage_options') || !is_admin_bar_showing()) {
            return;
        }
        
        // Only load if static generation is enabled
        if (!STCW_Core::is_enabled()) {
            return;
        }
        
        // Only load if there are pending assets
        $pending = count(get_option('stcw_pending_assets', []));
        if ($pending === 0) {
            return;
        }
        
        // Enqueue with unique handle and filename to avoid WordPress core conflicts
        wp_enqueue_script(
            'stcw-admin-bar-handler',  // Unique handle
            STCW_PLUGIN_URL . 'admin/js/stcw-admin-bar-handler.js',  // Unique filename
            ['jquery'],
            STCW_VERSION,
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('stcw-admin-bar-handler', 'stcwAdminBar', [
            'confirmMessage' => sprintf(
                /* translators: %d: number of pending assets */
                __('Process %d pending assets now? This will download CSS, JS, images, and fonts.', 'static-cache-wrangler'),
                $pending
            ),
            'redirectUrl' => add_query_arg(
                ['page' => 'static-cache-wrangler', 'auto_process' => '1'],
                admin_url('admin.php')
            ),
        ]);
    }
    
    /**
     * Add menu items to WordPress admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance
     */
    public function add_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $enabled = STCW_Core::is_enabled();
        
        $wp_admin_bar->add_node([
            'id'    => 'stcw_menu',
            'title' => __('Static Cache', 'static-cache-wrangler'),
            'href'  => admin_url('admin.php?page=static-cache-wrangler'),
            'meta'  => ['title' => __('Static Cache Wrangler', 'static-cache-wrangler')],
        ]);

        if ($enabled) {
            $pending = count(get_option('stcw_pending_assets', []));
            $downloaded = count(get_option('stcw_downloaded_assets', []));
            
            if ($pending > 0) {
                $wp_admin_bar->add_node([
                    'id'     => 'stcw_process',
                    'parent' => 'stcw_menu',
                    'title'  => sprintf(
                        /* translators: %d: number of pending assets */
                        __('⚡ Process Assets Now (%d pending)', 'static-cache-wrangler'),
                        $pending
                    ),
                    'href'   => '#',
                    'meta'   => [
                        'class' => 'stcw-process-assets-link',
                    ],
                ]);
            }

            $static_count = STCW_Core::count_static_files();
            $status_text = sprintf(
                /* translators: 1: number of HTML files, 2: number of downloaded assets */
                __('Files: %1$d HTML, %2$d assets', 'static-cache-wrangler'),
                $static_count,
                $downloaded
            );
            if ($pending > 0) {
                $status_text .= sprintf(
                    /* translators: %d: number of pending assets */
                    __(' (%d pending)', 'static-cache-wrangler'),
                    $pending
                );
            }
            
            $wp_admin_bar->add_node([
                'id'     => 'stcw_status',
                'parent' => 'stcw_menu',
                'title'  => $status_text,
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'stcw_settings',
                'parent' => 'stcw_menu',
                'title'  => __('Settings', 'static-cache-wrangler'),
                'href'   => admin_url('admin.php?page=static-cache-wrangler'),
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'stcw_download',
                'parent' => 'stcw_menu',
                'title'  => __('Download ZIP', 'static-cache-wrangler'),
                'href'   => wp_nonce_url(admin_url('admin-post.php?action=stcw_download'), 'stcw_download_action'),
            ]);
        } else {
            $wp_admin_bar->add_node([
                'id'     => 'stcw_disabled',
                'parent' => 'stcw_menu',
                'title'  => __('Status: DISABLED', 'static-cache-wrangler'),
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'stcw_settings',
                'parent' => 'stcw_menu',
                'title'  => __('Settings', 'static-cache-wrangler'),
                'href'   => admin_url('admin.php?page=static-cache-wrangler'),
            ]);
        }
    }
}
