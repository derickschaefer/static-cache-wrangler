<?php
/**
 * Static Cache Generator logger utility.
 *
 * Provides safe debug logging for all SCG classes.
 *
 * @package StaticCacheGenerator
 */

if ( ! function_exists( 'scg_log_debug' ) ) {
    /**
     * Log debug messages safely when WP_DEBUG is enabled.
     *
     * @param string $message Message to log.
     * @return void
     */
    function scg_log_debug( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( '[Static Cache Generator] ' . $message );
        }
    }
}
