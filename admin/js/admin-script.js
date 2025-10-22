/**
 * Admin Dashboard JavaScript for Static Cache Generator
 * Handles asset processing UI and AJAX interactions
 */

jQuery(document).ready(function($) {
    $('#stcg-process-now').on('click', function() {
        var $btn = $(this);
        var $status = $('#stcg-processing-status');
        var $progressBar = $('#stcg-progress-bar');
        var $progressText = $('#stcg-progress-text');

        $btn.prop('disabled', true);
        $status.show();

        function processAssets() {
            $.ajax({
                url: stcgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'stcg_process_pending',
                    nonce: stcgAdmin.nonce
                },
                success: function(response) {
                    if (response && response.success) {
                        var data = response.data || {};
                        var total = parseInt(stcgAdmin.pendingCount, 10);
                        var remaining = parseInt(data.remaining, 10) || 0;
                        var processed = Math.max(0, total - remaining);
                        var percent = total > 0 ? (processed / total) * 100 : 100;

                        $progressBar.css('width', percent + '%');
                        $progressText.text(processed + ' / ' + total + ' ' + stcgAdmin.i18n.assetsProcessed);

                        if (remaining > 0) {
                            setTimeout(processAssets, 800);
                        } else {
                            $progressText.text(stcgAdmin.i18n.complete);
                            setTimeout(function() {
                                window.location.href = window.location.href.split('?')[0] + '?page=static-cache-generator&message=processed';
                            }, 900);
                        }
                    } else {
                        alert(stcgAdmin.i18n.errorProcessing);
                        $btn.prop('disabled', false);
                        $status.hide();
                    }
                },
                error: function() {
                    alert(stcgAdmin.i18n.errorProcessing);
                    $btn.prop('disabled', false);
                    $status.hide();
                }
            });
        }

        processAssets();
    });
});
