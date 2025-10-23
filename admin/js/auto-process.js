/**
 * Auto-processing JavaScript for Static Cache Wrangler
 * Handles background asset processing in admin and frontend
 */

window.stcwProcessNow = function() {
    if (confirm('Process pending assets now? This will download CSS, JS, images, and fonts.')) {
        processPendingAssets(true);
    }
};

(function() {
    let processing = false;
    
    function processPendingAssets(manual = false) {
        if (processing) {
            if (manual) alert('Already processing...');
            return;
        }
        processing = true;
        
        if (manual) {
            console.log('STCW: Manually processing assets...');
        }
        
        fetch(stcwAutoProcess.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'stcw_process_pending',
                nonce: stcwAutoProcess.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            processing = false;
            console.log('STCW:', data);
            
            if (data.success && data.data.remaining > 0) {
                setTimeout(() => processPendingAssets(manual), 2000);
            } else if (data.success && data.data.remaining === 0) {
                console.log('STCW: All assets processed!');
                if (manual) {
                    alert('All assets processed successfully!');
                    location.reload();
                }
            }
        })
        .catch(error => {
            processing = false;
            console.error('STCW error:', error);
            if (manual) {
                alert('Error processing assets. Check console for details.');
            }
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => processPendingAssets(false), 3000);
        });
    } else {
        setTimeout(() => processPendingAssets(false), 3000);
    }
})();
