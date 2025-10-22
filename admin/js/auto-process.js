/**
 * Auto-processing JavaScript for Static Cache Generator
 * Handles background asset processing in admin and frontend
 */

window.stcgProcessNow = function() {
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
            console.log('STCG: Manually processing assets...');
        }
        
        fetch(stcgAutoProcess.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'stcg_process_pending',
                nonce: stcgAutoProcess.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            processing = false;
            console.log('STCG:', data);
            
            if (data.success && data.data.remaining > 0) {
                setTimeout(() => processPendingAssets(manual), 2000);
            } else if (data.success && data.data.remaining === 0) {
                console.log('STCG: All assets processed!');
                if (manual) {
                    alert('All assets processed successfully!');
                    location.reload();
                }
            }
        })
        .catch(error => {
            processing = false;
            console.error('STCG error:', error);
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
