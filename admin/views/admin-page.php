<?php
/**
 * Admin page template (safe header/bootstrap)
 *
 * @package StaticCache
 */

if (!defined('ABSPATH')) exit;

// Get status from SCG_Core
$enabled = SCG_Core::is_enabled();
$static_count = SCG_Core::count_static_files();

// Get directories
$static_dir = SCG_Core::get_static_dir();
$assets_dir = SCG_Core::get_assets_dir();

// Get asset counts from options
$pending_assets = get_option('scg_pending_assets', []);
$downloaded_assets = get_option('scg_downloaded_assets', []);
$pending_count = is_array($pending_assets) ? count($pending_assets) : 0;
$downloaded_count = is_array($downloaded_assets) ? count($downloaded_assets) : 0;

// Calculate directory size
$static_dir_size = SCG_Core::get_directory_size($static_dir);
$dir_size_pretty = SCG_Core::format_bytes($static_dir_size);

// Calculate available disk space
$disk_free = @disk_free_space(WP_CONTENT_DIR);
$disk_free_pretty = $disk_free !== false ? SCG_Core::format_bytes($disk_free) : __('Unknown', 'static-cache-generator');

// Determine disk space warning level
$disk_warning = false;
$disk_warning_type = 'info';
if ($disk_free !== false) {
    if ($disk_free < 500 * 1024 * 1024) { // Less than 500MB
        $disk_warning = __('Low disk space available', 'static-cache-generator');
        $disk_warning_type = 'warning';
    } elseif ($static_dir_size > 1024 * 1024 * 1024) { // Static files over 1GB
        $disk_warning = __('Static cache is using significant disk space', 'static-cache-generator');
        $disk_warning_type = 'info';
    }
}

// Success messages
$messages = [
    'enabled'   => __('Static site generation enabled.', 'static-cache-generator'),
    'disabled'  => __('Static site generation disabled.', 'static-cache-generator'),
    'cleared'   => __('All static files cleared.', 'static-cache-generator'),
    'processed' => __('Assets processed successfully.', 'static-cache-generator'),
];
$message_key = isset($_GET['message']) ? sanitize_key($_GET['message']) : '';
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if ($message_key && isset($messages[$message_key])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($messages[$message_key]); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($disk_warning): ?>
        <div class="notice notice-<?php echo esc_attr($disk_warning_type); ?>">
            <p>
                <strong><?php echo esc_html($disk_warning); ?>:</strong>
                <?php echo esc_html($dir_size_pretty); ?> <?php _e('used by static files', 'static-cache-generator'); ?>,
                <?php echo esc_html($disk_free_pretty); ?> <?php _e('available', 'static-cache-generator'); ?>.
                <?php if ($disk_warning_type === 'warning'): ?>
                    <?php _e('Consider clearing old files or freeing up disk space through your hosting control panel.', 'static-cache-generator'); ?>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Cards Grid -->
    <div class="scg-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-top:20px;">
        <!-- Generation Status Card -->
        <div class="scg-card">
            <h3><?php _e('Generation Status', 'static-cache-generator'); ?></h3>
            <div class="scg-value <?php echo $enabled ? 'scg-on' : 'scg-off'; ?>">
                <?php echo $enabled ? __('Running', 'static-cache-generator') : __('Paused', 'static-cache-generator'); ?>
            </div>
            <div class="scg-label">
                <?php echo number_format_i18n($static_count); ?> <?php _e('static files', 'static-cache-generator'); ?>
            </div>
        </div>

        <!-- Assets Card -->
        <div class="scg-card">
            <h3><?php _e('Assets', 'static-cache-generator'); ?></h3>
            <div class="scg-value"><?php echo number_format_i18n($downloaded_count); ?></div>
            <div class="scg-label">
                <?php echo number_format_i18n($pending_count); ?> <?php _e('pending', 'static-cache-generator'); ?>
                <?php if ($pending_count > 0 && $enabled): ?>
                    <span aria-hidden="true" title="<?php esc_attr_e('Pending assets exist', 'static-cache-generator'); ?>">⚠ </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Total Size Card -->
        <div class="scg-card">
            <h3><?php _e('Total Size', 'static-cache-generator'); ?></h3>
            <div class="scg-value"><?php echo esc_html($dir_size_pretty); ?></div>
            <div class="scg-label"><?php _e('Static directory footprint', 'static-cache-generator'); ?></div>
        </div>
    </div>

    <div class="scg-layout" style="display:flex;gap:20px;align-items:flex-start;margin-top:20px;">
        <!-- Main Content -->
        <div style="flex:1;min-width:0;">

            <!-- Disk Usage Information Panel -->
            <div class="scg-panel scg-card">
                <h2 class="scg-panel-title"><?php _e('Disk Usage Information', 'static-cache-generator'); ?></h2>
                <table class="widefat" style="margin-top:10px;">
                    <tbody>
                        <tr>
                            <td style="width:220px;"><strong><?php _e('Static Files Size', 'static-cache-generator'); ?></strong></td>
                            <td><?php echo esc_html($dir_size_pretty); ?></td>
                        </tr>
                        <tr class="alternate">
                            <td><strong><?php _e('Available Disk Space', 'static-cache-generator'); ?></strong></td>
                            <td>
                                <?php if ($disk_free !== false): ?>
                                    <?php echo esc_html($disk_free_pretty); ?>
                                    <?php if ($disk_free < 500 * 1024 * 1024): ?>
                                        <span class="scg-bad">⚠ <?php _e('Low', 'static-cache-generator'); ?></span>
                                    <?php else: ?>
                                        <span class="scg-ok">✓ <?php _e('Adequate', 'static-cache-generator'); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#666;"><?php _e('Unable to determine', 'static-cache-generator'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('HTML Files', 'static-cache-generator'); ?></strong></td>
                            <td><?php echo number_format_i18n($static_count); ?> <?php _e('files', 'static-cache-generator'); ?></td>
                        </tr>
                        <tr class="alternate">
                            <td><strong><?php _e('Downloaded Assets', 'static-cache-generator'); ?></strong></td>
                            <td><?php echo number_format_i18n($downloaded_count); ?> <?php _e('files (CSS, JS, images, fonts)', 'static-cache-generator'); ?></td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top:15px;padding:12px;background:#f0f0f1;border-left:4px solid #72aee6;font-size:13px;line-height:1.6;">
                    <strong>ℹ️ <?php _e('About Disk Usage', 'static-cache-generator'); ?></strong><br>
                    <?php _e('This plugin does not enforce disk limits. Disk space is managed by your hosting environment.', 'static-cache-generator'); ?>
                    <?php _e('Typical usage: 50-200 MB (small sites), 200 MB - 1 GB (medium sites), 1-5 GB (large sites).', 'static-cache-generator'); ?>
                    <?php if ($static_dir_size > 1024 * 1024 * 1024): ?>
                        <br><br>
                        <strong><?php _e('Tip:', 'static-cache-generator'); ?></strong>
                        <?php _e('Your static cache is over 1 GB. Consider clearing old files if disk space is a concern.', 'static-cache-generator'); ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- File System Locations Panel -->
            <div class="scg-panel scg-card">
                <h2 class="scg-panel-title"><?php _e('File System Locations', 'static-cache-generator'); ?></h2>
                <table class="widefat" style="margin-top:10px;">
                    <tbody>
                        <tr>
                            <td style="width:220px;"><strong><?php _e('Static Files', 'static-cache-generator'); ?></strong></td>
                            <td><code><?php echo esc_html($static_dir); ?></code></td>
                        </tr>
                        <tr class="alternate">
                            <td><strong><?php _e('Assets Directory', 'static-cache-generator'); ?></strong></td>
                            <td><code><?php echo esc_html($assets_dir); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Directory Exists', 'static-cache-generator'); ?></strong></td>
                            <td>
                                <?php if (is_dir($static_dir)): ?>
                                    <span class="scg-ok">✓ <?php _e('Yes', 'static-cache-generator'); ?></span>
                                <?php else: ?>
                                    <span class="scg-bad">✗ <?php _e('No', 'static-cache-generator'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="alternate">
                            <td><strong><?php _e('Directory Writable', 'static-cache-generator'); ?></strong></td>
                            <td>
                                <?php if (is_dir($static_dir) && is_writable($static_dir)): ?>
                                    <span class="scg-ok">✓ <?php _e('Yes', 'static-cache-generator'); ?></span>
                                <?php else: ?>
                                    <span class="scg-bad">✗ <?php _e('No', 'static-cache-generator'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php if ($enabled && $pending_count > 0): ?>
            <!-- Pending Assets Processing Panel -->
            <div class="scg-panel scg-card">
                <h2 class="scg-panel-title"><?php _e('Asset Processing', 'static-cache-generator'); ?></h2>
                <p><?php printf(__('There are %d assets waiting to be downloaded.', 'static-cache-generator'), $pending_count); ?></p>
                <div id="scg-processing-status" class="scg-progress" style="display:none;">
                    <strong><?php _e('Processing...', 'static-cache-generator'); ?></strong>
                    <div class="scg-progress-bar-outer">
                        <div id="scg-progress-bar" class="scg-progress-bar-inner" style="width:0%;"></div>
                    </div>
                    <div id="scg-progress-text" class="scg-progress-text"></div>
                </div>
                <button type="button" class="button button-secondary" id="scg-process-now">
                    <span class="dashicons dashicons-update" style="margin-top:3px;"></span>
                    <?php _e('Process Assets Now', 'static-cache-generator'); ?>
                </button>
            </div>
            <?php endif; ?>

	    <!-- How It Works Panel -->
            <div class="scg-panel scg-card">
                <h2 class="scg-panel-title"><?php _e('How It Works', 'static-cache-generator'); ?></h2>
                <ol style="margin-left:20px;">
                    <li><?php _e('Enable static site generation using the toggle in the sidebar.', 'static-cache-generator'); ?></li>
                    <li><?php _e('As users browse your site normally - each page visit creates a static HTML file.', 'static-cache-generator'); ?></li>
                    <li><?php _e('Assets (CSS, JS, images, fonts) are automatically downloaded and localized.', 'static-cache-generator'); ?></li>
                    <li><?php _e('Processing is done in the background and can be paused at any time.', 'static-cache-generator'); ?></li>
                    <li><?php _e('Download the complete static site as a ZIP file.', 'static-cache-generator'); ?></li>
                    <li><?php _e('Extract and open index.html in any browser - works completely offline!', 'static-cache-generator'); ?></li>
		</ol>
               <h2 class="scg-panel-title"><?php _e('Use Cases', 'static-cache-generator'); ?></h2>
               <ol style="margin-left:20px;">
                    <li><?php _e('Generate a fully self-contained static version of a WordPress site for portability or offline use.', 'static-cache-generator'); ?></li>
                    <li><?php _e('Rsync to a backup Nginx failover server to provide high read-only availability.', 'static-cache-generator'); ?></li>
                    <li><?php _e('Build in WordPress and seamlessly publish to Amazon S3 or a static CDN for global delivery.', 'static-cache-generator'); ?></li>
                    <li><?php _e('Rsync to multiple geographies and geo-load balance with Cloudflare or another DNS provider for fast, local reads.', 'static-cache-generator'); ?></li>
                </ol>
            </div>
	</div>


        <!-- Sidebar -->
        <div style="width:320px;flex:0 0 320px;">
            <!-- Quick Actions Card -->
            <div class="scg-card">
                <h2 class="scg-panel-title"><?php _e('Quick Actions', 'static-cache-generator'); ?></h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:15px;">
                    <?php wp_nonce_field('scg_toggle_action', 'scg_toggle_nonce'); ?>
                    <input type="hidden" name="action" value="scg_toggle" />
                    <input type="hidden" name="enable" value="<?php echo $enabled ? '0' : '1'; ?>" />

                    <?php if ($enabled): ?>
                        <button type="submit" class="button button-secondary button-large" style="width:100%;margin-bottom:10px;">
                            <span class="dashicons dashicons-no" style="margin-top:3px;"></span>
                            <?php _e('Pause Generation', 'static-cache-generator'); ?>
                        </button>
                    <?php else: ?>
                        <button type="submit" class="button button-primary button-large" style="width:100%;margin-bottom:10px;">
                            <span class="dashicons dashicons-yes" style="margin-top:3px;"></span>
                            <?php _e('Enable Generation', 'static-cache-generator'); ?>
                        </button>
                    <?php endif; ?>
                </form>

                <?php if ($static_count > 0): ?>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=scg_download'), 'scg_download_action')); ?>"
                       class="button button-primary button-large" style="width:100%;text-align:center;margin-bottom:10px;">
                        <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                        <?php _e('Download ZIP', 'static-cache-generator'); ?>
                    </a>

                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=scg_clear'), 'scg_clear_action')); ?>"
                       class="button button-secondary button-large" style="width:100%;text-align:center;"
                       onclick="return confirm('<?php echo esc_attr__('Are you sure you want to delete all static files?', 'static-cache-generator'); ?>');">
                        <span class="dashicons dashicons-trash" style="margin-top:3px;"></span>
                        <?php _e('Clear All Files', 'static-cache-generator'); ?>
                    </a>
                <?php else: ?>
                    <p style="color:#666;font-size:13px;margin-top:10px;">
                        <?php _e('No static files yet. Enable generation and browse your site to create static files.', 'static-cache-generator'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Info Card -->
            <div class="scg-card">
                <h2 class="scg-panel-title"><?php _e('Information', 'static-cache-generator'); ?></h2>
                <p style="font-size:13px;line-height:1.6;">
                    <strong><?php _e('Version:', 'static-cache-generator'); ?></strong>
                    <?php echo defined('SCG_VERSION') ? esc_html(SCG_VERSION) : 'N/A'; ?><br>
                    <strong><?php _e('Plugin:', 'static-cache-generator'); ?></strong> Static Cache Generator
                </p>
                <p style="font-size:13px;line-height:1.6;margin-top:10px;">
                    <?php _e('This plugin generates a fully self-contained static version of your WordPress site that can be deployed anywhere or run completely offline.', 'static-cache-generator'); ?>
                </p>
            </div>

            <!-- WP-CLI Card -->
            <div class="scg-card">
                <h2 class="scg-panel-title"><?php _e('WP-CLI Commands', 'static-cache-generator'); ?></h2>
                <div style="font-size:12px;line-height:1.8;">
                    <code style="display:block;padding:5px;background:#f0f0f1;margin-bottom:5px;">wp scg enable</code>
                    <code style="display:block;padding:5px;background:#f0f0f1;margin-bottom:5px;">wp scg disable</code>
                    <code style="display:block;padding:5px;background:#f0f0f1;margin-bottom:5px;">wp scg status</code>
                    <code style="display:block;padding:5px;background:#f0f0f1;margin-bottom:5px;">wp scg process</code>
                    <code style="display:block;padding:5px;background:#f0f0f1;">wp scg clear</code>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#scg-process-now').on('click', function() {
        var $btn = $(this);
        var $status = $('#scg-processing-status');
        var $progressBar = $('#scg-progress-bar');
        var $progressText = $('#scg-progress-text');

        $btn.prop('disabled', true);
        $status.show();

        function processAssets() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'scg_process_pending',
                    nonce: '<?php echo wp_create_nonce('scg_process'); ?>'
                },
                success: function(response) {
                    if (response && response.success) {
                        var data = response.data || {};
                        var total = <?php echo (int) $pending_count; ?>;
                        var remaining = parseInt(data.remaining, 10) || 0;
                        var processed = Math.max(0, total - remaining);
                        var percent = total > 0 ? (processed / total) * 100 : 100;

                        $progressBar.css('width', percent + '%');
                        $progressText.text(processed + ' / ' + total + ' <?php echo esc_js(__('assets processed', 'static-cache-generator')); ?>');

                        if (remaining > 0) {
                            setTimeout(processAssets, 800);
                        } else {
                            $progressText.text('<?php echo esc_js(__('Complete!', 'static-cache-generator')); ?>');
                            setTimeout(function() {
                                window.location.href = window.location.href.split('?')[0] + '?page=static-cache-generator&message=processed';
                            }, 900);
                        }
                    } else {
                        alert('<?php echo esc_js(__('Error processing assets. Please try again.', 'static-cache-generator')); ?>');
                        $btn.prop('disabled', false);
                        $status.hide();
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('Error processing assets. Please try again.', 'static-cache-generator')); ?>');
                    $btn.prop('disabled', false);
                    $status.hide();
                }
            });
        }

        processAssets();
    });
});
</script>

<style>
/* Modern card-based UI styling */
.scg-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 6px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.scg-card h3,
.scg-panel-title {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    color: #23282d;
}

.scg-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1.1;
    margin: 4px 0 6px;
    color: #1d2327;
}

.scg-value.scg-on {
    color: #46b450;
}

.scg-value.scg-off {
    color: #dc3232;
}

.scg-label {
    font-size: 13px;
    color: #646970;
}

.scg-panel {
    margin-top: 20px;
}

.scg-ok {
    color: #46b450;
    font-weight: 600;
}

.scg-bad {
    color: #dc3232;
    font-weight: 600;
}

/* Progress bar styling */
.scg-progress {
    margin: 15px 0;
    padding: 10px;
    background: #f0f0f1;
    border-radius: 4px;
}

.scg-progress-bar-outer {
    background: #fff;
    height: 20px;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 10px;
}

.scg-progress-bar-inner {
    background: #2271b1;
    height: 100%;
    width: 0%;
    transition: width .3s;
}

.scg-progress-text {
    margin-top: 6px;
    font-size: 12px;
}
</style>
