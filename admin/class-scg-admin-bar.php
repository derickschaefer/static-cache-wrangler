<?php
/**
 * Admin bar integration
 */

if (!defined('ABSPATH')) exit;

class SCG_Admin_Bar {
    
    public function init() {
        add_action('admin_bar_menu', [$this, 'add_menu'], 100);
    }
    
    public function add_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $enabled = SCG_Core::is_enabled();
        
        $wp_admin_bar->add_node([
            'id'    => 'scg_menu',
            'title' => 'Static Cache',
            'href'  => admin_url('admin.php?page=static-cache-generator'),
            'meta'  => ['title' => 'Static Cache Generator'],
        ]);

        if ($enabled) {
            $pending = count(get_option('scg_pending_assets', []));
            $downloaded = count(get_option('scg_downloaded_assets', []));
            
            if ($pending > 0) {
                $wp_admin_bar->add_node([
                    'id'     => 'scg_process',
                    'parent' => 'scg_menu',
                    'title'  => "⚡ Process Assets Now ($pending pending)",
                    'href'   => '#',
                    'meta'   => [
                        'onclick' => 'ssgProcessNow(); return false;'
                    ],
                ]);
            }

            $static_count = SCG_Core::count_static_files();
            $status_text = "Files: {$static_count} HTML, {$downloaded} assets";
            if ($pending > 0) {
                $status_text .= " ({$pending} pending)";
            }
            
            $wp_admin_bar->add_node([
                'id'     => 'scg_status',
                'parent' => 'scg_menu',
                'title'  => $status_text,
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'scg_settings',
                'parent' => 'scg_menu',
                'title'  => 'Settings',
                'href'   => admin_url('admin.php?page=static-cache-generator'),
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'scg_download',
                'parent' => 'scg_menu',
                'title'  => 'Download ZIP',
                'href'   => wp_nonce_url(admin_url('admin-post.php?action=scg_download'), 'scg_download_action'),
            ]);
        } else {
            $wp_admin_bar->add_node([
                'id'     => 'scg_disabled',
                'parent' => 'scg_menu',
                'title'  => 'Status: DISABLED',
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'scg_settings',
                'parent' => 'scg_menu',
                'title'  => 'Settings',
                'href'   => admin_url('admin.php?page=static-cache-generator'),
            ]);
        }
    }
}
