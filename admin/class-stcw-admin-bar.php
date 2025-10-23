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
            'title' => 'Static Cache',
            'href'  => admin_url('admin.php?page=static-cache-wrangler'),
            'meta'  => ['title' => 'Static Cache Wrangler'],
        ]);

        if ($enabled) {
            $pending = count(get_option('stcw_pending_assets', []));
            $downloaded = count(get_option('stcw_downloaded_assets', []));
            
            if ($pending > 0) {
                $wp_admin_bar->add_node([
                    'id'     => 'stcw_process',
                    'parent' => 'stcw_menu',
                    'title'  => "⚡ Process Assets Now ($pending pending)",
                    'href'   => '#',
                    'meta'   => [
                        'onclick' => 'stcwProcessNow(); return false;'
                    ],
                ]);
            }

            $static_count = STCW_Core::count_static_files();
            $status_text = "Files: {$static_count} HTML, {$downloaded} assets";
            if ($pending > 0) {
                $status_text .= " ({$pending} pending)";
            }
            
            $wp_admin_bar->add_node([
                'id'     => 'stcw_status',
                'parent' => 'stcw_menu',
                'title'  => $status_text,
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'stcw_settings',
                'parent' => 'stcw_menu',
                'title'  => 'Settings',
                'href'   => admin_url('admin.php?page=static-cache-wrangler'),
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'stcw_download',
                'parent' => 'stcw_menu',
                'title'  => 'Download ZIP',
                'href'   => wp_nonce_url(admin_url('admin-post.php?action=stcw_download'), 'stcw_download_action'),
            ]);
        } else {
            $wp_admin_bar->add_node([
                'id'     => 'stcw_disabled',
                'parent' => 'stcw_menu',
                'title'  => 'Status: DISABLED',
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'stcw_settings',
                'parent' => 'stcw_menu',
                'title'  => 'Settings',
                'href'   => admin_url('admin.php?page=static-cache-wrangler'),
            ]);
        }
    }
}
