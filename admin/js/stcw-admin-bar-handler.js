/**
 * Admin Bar Process Assets Handler for Static Cache Wrangler
 * Handles "Process Assets Now" click from WordPress admin bar
 *
 * @package StaticCacheWrangler
 * @since 2.0.6
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Find the admin bar link - try multiple selectors for robustness
        var $link = $('#wp-admin-bar-stcw_process').find('a');
        
        if ($link.length === 0) {
            $link = $('#wp-admin-bar-stcw_process a');
        }
        
        if ($link.length === 0) {
            return; // Link not found, exit gracefully
        }
        
        // Attach click handler
        $link.on('click', function(e) {
            e.preventDefault();
            
            // Verify configuration is loaded
            if (typeof stcwAdminBar === 'undefined') {
                alert('Error: Configuration not loaded. Please refresh the page.');
                return false;
            }
            
            // Show confirmation dialog with localized message
            if (confirm(stcwAdminBar.confirmMessage)) {
                // Redirect to admin page with auto_process parameter
                window.location.href = stcwAdminBar.redirectUrl;
            }
            
            return false;
        });
    });
    
})(jQuery);
