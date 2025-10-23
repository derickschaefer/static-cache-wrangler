/**
 * Admin Dashboard JavaScript for Static Cache Wrangler
 * Handles asset processing UI and AJAX interactions
 */

jQuery(document).ready(function($) {
    $('#stcw-process-now').on('click', function() {
        var $btn = $(this);
        var $status = $('#stcw-processing-status');
        var $progressBar = $('#stcw-progress-bar');
        var $progressText = $('#stcw-progress-text');

        $btn.prop('disabled', true);
        $status.show();

        function processAssets() {
            $.ajax({
                url: stcwAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'stcw_process_pending',
                    nonce: stcwAdmin.nonce
                },
                success: function(response) {
                    if (response && response.success) {
                        var data = response.data || {};
                        var total = parseInt(stcwAdmin.pendingCount, 10);
                        var remaining = parseInt(data.remaining, 10) || 0;
                        var processed = Math.max(0, total - remaining);
                        var percent = total > 0 ? (processed / total) * 100 : 100;

                        $progressBar.css('width', percent + '%');
                        $progressText.text(processed + ' / ' + total + ' ' + stcwAdmin.i18n.assetsProcessed);

                        if (remaining > 0) {
                            setTimeout(processAssets, 800);
                        } else {
                            $progressText.text(stcwAdmin.i18n.complete);
                            setTimeout(function() {
                                window.location.href = window.location.href.split('?')[0] + '?page=static-cache-wrangler&message=processed';
                            }, 900);
                        }
                    } else {
                        alert(stcwAdmin.i18n.errorProcessing);
                        $btn.prop('disabled', false);
                        $status.hide();
                    }
                },
                error: function() {
                    alert(stcwAdmin.i18n.errorProcessing);
                    $btn.prop('disabled', false);
                    $status.hide();
                }
            });
        }

        processAssets();
    });
});
