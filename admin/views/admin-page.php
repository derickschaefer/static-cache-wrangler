<?php
/**
 * Admin page template
 *
 * Displays the main settings and dashboard interface for Static Cache Wrangler
 * with modern card-based UI design.
 *
 * @package StaticCacheWrangler
 * @since 2.0
 */

if (!defined('ABSPATH')) exit;

// Get status from STCW_Core
$enabled = STCW_Core::is_enabled();
$static_count = STCW_Core::count_static_files();

// Get directories
$static_dir = STCW_Core::get_static_dir();
$assets_dir = STCW_Core::get_assets_dir();

// Get asset counts from options
$pending_assets = get_option('stcw_pending_assets', []);
$downloaded_assets = get_option('stcw_downloaded_assets', []);
$pending_count = is_array($pending_assets) ? count($pending_assets) : 0;
$downloaded_count = is_array($downloaded_assets) ? count($downloaded_assets) : 0;

// Calculate directory size
$static_dir_size = STCW_Core::get_directory_size($static_dir);
$dir_size_pretty = STCW_Core::format_bytes($static_dir_size);

// Success messages
$messages = [
    'enabled'   => __('Static site generation enabled.', 'static-cache-wrangler'),
    'disabled'  => __('Static site generation disabled.', 'static-cache-wrangler'),
    'cleared'   => __('All static files cleared.', 'static-cache-wrangler'),
    'processed' => __('Assets processed successfully.', 'static-cache-wrangler'),
];

// Safely get and validate the message parameter (nonce verified by admin-post.php handlers)
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by admin-post.php action handlers
$message_key = isset($_GET['message']) ? sanitize_key(wp_unslash($_GET['message'])) : '';
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if ($message_key && isset($messages[$message_key])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($messages[$message_key]); ?></p>
        </div>
    <?php endif; ?>

    <!-- Cards Grid -->
    <div class="stcw-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-top:20px;">
        <!-- Generation Status Card -->
        <div class="stcw-card">
            <h3><?php esc_html_e('Generation Status', 'static-cache-wrangler'); ?></h3>
            <div class="stcw-value <?php echo $enabled ? 'stcw-on' : 'stcw-off'; ?>">
                <?php echo $enabled ? esc_html__('Running', 'static-cache-wrangler') : esc_html__('Paused', 'static-cache-wrangler'); ?>
            </div>
            <div class="stcw-label">
                <?php
                $static_files_text = sprintf(
                    /* translators: %s: formatted number of static files */
                    _n(
                        '%s static file',
                        '%s static files',
                        $static_count,
                        'static-cache-wrangler'
                    ),
                    number_format_i18n($static_count)
                );
                echo esc_html($static_files_text);
                ?>
            </div>
        </div>

        <!-- Assets Card -->
        <div class="stcw-card">
            <h3><?php esc_html_e('Assets', 'static-cache-wrangler'); ?></h3>
            <div class="stcw-value">
                <?php echo esc_html(number_format_i18n($downloaded_count)); ?>
            </div>
            <div class="stcw-label">
                <?php
                $pending_text = sprintf(
                    /* translators: %s: formatted number of pending assets */
                    _n(
                        '%s pending',
                        '%s pending',
                        $pending_count,
                        'static-cache-wrangler'
                    ),
                    number_format_i18n($pending_count)
                );
                echo esc_html($pending_text);
                ?>

                <?php if ($pending_count > 0 && $enabled): ?>
                    <span aria-hidden="true" title="<?php esc_attr_e('Pending assets exist', 'static-cache-wrangler'); ?>">⚠</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Total Size Card -->
        <div class="stcw-card">
            <h3><?php esc_html_e('Total Size', 'static-cache-wrangler'); ?></h3>
            <div class="stcw-value"><?php echo esc_html($dir_size_pretty); ?></div>
            <div class="stcw-label"><?php esc_html_e('Static directory footprint', 'static-cache-wrangler'); ?></div>
        </div>
    </div>

    <div class="stcw-layout" style="display:flex;gap:20px;align-items:flex-start;margin-top:20px;">
        <!-- Main Content -->
        <div style="flex:1;min-width:0;">

            <!-- File System Locations Panel -->
            <div class="stcw-panel stcw-card">
                <h2 class="stcw-panel-title"><?php esc_html_e('File System Locations', 'static-cache-wrangler'); ?></h2>
                <table class="widefat" style="margin-top:10px;">
                    <tbody>
                        <tr>
                            <td style="width:220px;"><strong><?php esc_html_e('Static Files', 'static-cache-wrangler'); ?></strong></td>
                            <td><code><?php echo esc_html($static_dir); ?></code></td>
                        </tr>
                        <tr class="alternate">
                            <td><strong><?php esc_html_e('Assets Directory', 'static-cache-wrangler'); ?></strong></td>
                            <td><code><?php echo esc_html($assets_dir); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Directory Exists', 'static-cache-wrangler'); ?></strong></td>
                            <td>
                                <?php if (is_dir($static_dir)): ?>
                                    <span class="stcw-ok">✓ <?php esc_html_e('Yes', 'static-cache-wrangler'); ?></span>
                                <?php else: ?>
                                    <span class="stcw-bad">✗ <?php esc_html_e('No', 'static-cache-wrangler'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="alternate">
                            <td><strong><?php esc_html_e('Directory Writable', 'static-cache-wrangler'); ?></strong></td>
                            <td>
                                <?php if (is_dir($static_dir) && wp_is_writable($static_dir)): ?>
                                    <span class="stcw-ok">✓ <?php esc_html_e('Yes', 'static-cache-wrangler'); ?></span>
                                <?php else: ?>
                                    <span class="stcw-bad">✗ <?php esc_html_e('No', 'static-cache-wrangler'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php if ($enabled && $pending_count > 0): ?>
            <!-- Pending Assets Processing Panel -->
            <div class="stcw-panel stcw-card">
                <h2 class="stcw-panel-title"><?php esc_html_e('Asset Processing', 'static-cache-wrangler'); ?></h2>
                <p>
                <?php
                $assets_message = sprintf(
                    /* translators: %d: number of assets waiting to be downloaded */
                    _n(
                        'There is %d asset waiting to be downloaded.',
                        'There are %d assets waiting to be downloaded.',
                        $pending_count,
                        'static-cache-wrangler'
                    ),
                    $pending_count
                );
                echo esc_html($assets_message);
                ?>	
                </p>
                <div id="stcw-processing-status" class="stcw-progress" style="display:none;">
                    <strong><?php esc_html_e('Processing...', 'static-cache-wrangler'); ?></strong>
                    <div class="stcw-progress-bar-outer">
                        <div id="stcw-progress-bar" class="stcw-progress-bar-inner" style="width:0%;"></div>
                    </div>
                    <div id="stcw-progress-text" class="stcw-progress-text"></div>
                </div>
                <button type="button" class="button button-secondary" id="stcw-process-now">
                    <span class="dashicons dashicons-update" style="margin-top:3px;"></span>
                    <?php esc_html_e('Process Assets Now', 'static-cache-wrangler'); ?>
                </button>
            </div>
            <?php endif; ?>

            <!-- How It Works Panel -->
            <div class="stcw-panel stcw-card">
                <h2 class="stcw-panel-title"><?php esc_html_e('How It Works', 'static-cache-wrangler'); ?></h2>
                <ol style="margin-left:20px;">
                    <li><?php esc_html_e('Enable static site generation using the toggle in the sidebar.', 'static-cache-wrangler'); ?></li>
                    <li><?php esc_html_e('As users browse your site normally - each page visit creates a static HTML file.', 'static-cache-wrangler'); ?></li>
                    <li><?php esc_html_e('Assets (CSS, JS, images, fonts) are automatically downloaded and localized.', 'static-cache-wrangler'); ?></li>
                    <li><?php esc_html_e('Processing is done in the background and can be paused at any time.', 'static-cache-wrangler'); ?></li>
                    <li><?php esc_html_e('Download the complete static site as a ZIP file.', 'static-cache-wrangler'); ?></li>
                    <li><?php esc_html_e('Extract and open index.html in any browser - works completely offline!', 'static-cache-wrangler'); ?></li>
                </ol>
                <h2 class="stcw-panel-title"><?php esc_html_e('Use Cases', 'static-cache-wrangler'); ?></h2>
                <ol style="margin-left:20px;">
                    <li><?php esc_html_e('Generate a fully self-contained static version of a WordPress site for portability or offline use.', 'static-cache-wrangler'); ?></li>
                    <li><?php esc_html_e('Rsync to a backup Nginx failover server to provide high read-only availability.', 'static-cache-wrangler'); ?></li>
                    <li><?php esc_html_e('Build in WordPress and seamlessly publish to Amazon S3&reg; or a static CDN for global delivery.', 'static-cache-wrangler'); ?></li>
                    <li><?php esc_html_e('Rsync to multiple geographies and geo-load balance with Cloudflare&reg; or another DNS provider for fast, local reads.', 'static-cache-wrangler'); ?></li>
                </ol>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="width:320px;flex:0 0 320px;">
            <!-- Quick Actions Card -->
            <div class="stcw-card">
                <h2 class="stcw-panel-title"><?php esc_html_e('Quick Actions', 'static-cache-wrangler'); ?></h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:15px;">
                    <?php wp_nonce_field('stcw_toggle_action', 'stcw_toggle_nonce'); ?>
                    <input type="hidden" name="action" value="stcw_toggle" />
                    <input type="hidden" name="enable" value="<?php echo $enabled ? '0' : '1'; ?>" />

                    <?php if ($enabled): ?>
                        <button type="submit" class="button button-secondary button-large" style="width:100%;margin-bottom:10px;">
                            <span class="dashicons dashicons-no" style="margin-top:3px;"></span>
                            <?php esc_html_e('Pause Generation', 'static-cache-wrangler'); ?>
                        </button>
                    <?php else: ?>
                        <button type="submit" class="button button-primary button-large" style="width:100%;margin-bottom:10px;">
                            <span class="dashicons dashicons-yes" style="margin-top:3px;"></span>
                            <?php esc_html_e('Enable Generation', 'static-cache-wrangler'); ?>
                        </button>
                    <?php endif; ?>
                </form>

                <?php if ($static_count > 0): ?>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=stcw_download'), 'stcw_download_action')); ?>"
                       class="button button-primary button-large" style="width:100%;text-align:center;margin-bottom:10px;">
                        <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                        <?php esc_html_e('Download ZIP', 'static-cache-wrangler'); ?>
                    </a>

                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=stcw_clear'), 'stcw_clear_action')); ?>"
                       class="button button-secondary button-large" style="width:100%;text-align:center;"
                       onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete all static files?', 'static-cache-wrangler')); ?>');">
                        <span class="dashicons dashicons-trash" style="margin-top:3px;"></span>
                        <?php esc_html_e('Clear All Files', 'static-cache-wrangler'); ?>
                    </a>
                <?php else: ?>
                    <p style="color:#666;font-size:13px;margin-top:10px;">
                        <?php esc_html_e('No static files yet. Enable generation and browse your site to create static files.', 'static-cache-wrangler'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Info Card -->
            <div class="stcw-card">
                <h2 class="stcw-panel-title"><?php esc_html_e('Information', 'static-cache-wrangler'); ?></h2>
                <p style="font-size:13px;line-height:1.6;">
                    <strong><?php esc_html_e('Version:', 'static-cache-wrangler'); ?></strong>
                    <?php echo defined('STCW_VERSION') ? esc_html(STCW_VERSION) : 'N/A'; ?><br>
                    <strong><?php esc_html_e('Plugin:', 'static-cache-wrangler'); ?></strong> Static Cache Wrangler
                </p>
                <p style="font-size:13px;line-height:1.6;margin-top:10px;">
                    <?php esc_html_e('This plugin generates a fully self-contained static version of your WordPress site that can be deployed anywhere or run completely offline.', 'static-cache-wrangler'); ?>
                </p>
            </div>

            <!-- WP-CLI Card -->
            <div class="stcw-card">
                <h2 class="stcw-panel-title"><?php esc_html_e('WP-CLI Commands', 'static-cache-wrangler'); ?></h2>
                <div style="font-size:12px;line-height:1.8;">
                    <code style="display:block;padding:5px;background:#f0f0f1;margin-bottom:5px;">wp scw enable</code>
                    <code style="display:block;padding:5px;background:#f0f0f1;margin-bottom:5px;">wp scw disable</code>
                    <code style="display:block;padding:5px;background:#f0f0f1;margin-bottom:5px;">wp scw status</code>
                    <code style="display:block;padding:5px;background:#f0f0f1;margin-bottom:5px;">wp scw process</code>
                    <code style="display:block;padding:5px;background:#f0f0f1;">wp scw clear</code>
                </div>
            </div>
        </div>
    </div>
</div>
