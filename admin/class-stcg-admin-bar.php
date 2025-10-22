<?php
/**
 * Admin bar integration
 *
 * Adds Static Cache Generator menu and status to WordPress admin bar
 *
 * @package StaticCacheGenerator
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

class STCG_Admin_Bar {
    
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

        $enabled = STCG_Core::is_enabled();
        
        $wp_admin_bar->add_node([
            'id'    => 'stcg_menu',
            'title' => 'Static Cache',
            'href'  => admin_url('admin.php?page=static-cache-generator'),
            'meta'  => ['title' => 'Static Cache Generator'],
        ]);

        if ($enabled) {
            $pending = count(get_option('stcg_pending_assets', []));
            $downloaded = count(get_option('stcg_downloaded_assets', []));
            
            if ($pending > 0) {
                $wp_admin_bar->add_node([
                    'id'     => 'stcg_process',
                    'parent' => 'stcg_menu',
                    'title'  => "⚡ Process Assets Now ($pending pending)",
                    'href'   => '#',
                    'meta'   => [
                        'onclick' => 'stcgProcessNow(); return false;'
                    ],
                ]);
            }

            $static_count = STCG_Core::count_static_files();
            $status_text = "Files: {$static_count} HTML, {$downloaded} assets";
            if ($pending > 0) {
                $status_text .= " ({$pending} pending)";
            }
            
            $wp_admin_bar->add_node([
                'id'     => 'stcg_status',
                'parent' => 'stcg_menu',
                'title'  => $status_text,
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'stcg_settings',
                'parent' => 'stcg_menu',
                'title'  => 'Settings',
                'href'   => admin_url('admin.php?page=static-cache-generator'),
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'stcg_download',
                'parent' => 'stcg_menu',
                'title'  => 'Download ZIP',
                'href'   => wp_nonce_url(admin_url('admin-post.php?action=stcg_download'), 'stcg_download_action'),
            ]);
        } else {
            $wp_admin_bar->add_node([
                'id'     => 'stcg_disabled',
                'parent' => 'stcg_menu',
                'title'  => 'Status: DISABLED',
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'stcg_settings',
                'parent' => 'stcg_menu',
                'title'  => 'Settings',
                'href'   => admin_url('admin.php?page=static-cache-generator'),
            ]);
        }
    }
}
