<?php
/**
 * Static Cache Wrangler logger utility.
 *
 * Provides safe debug logging for all STCW classes.
 *
 * @package StaticCacheWrangler
 */

if (!function_exists('stcw_log_debug')) {
    /**
     * Log debug messages safely when WP_DEBUG is enabled.
     *
     * @param string $message Message to log.
     * @return void
     */
    function stcw_log_debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[Static Cache Wrangler] ' . $message);
        }
    }
}
