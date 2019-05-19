(function () {
    // Get the modal
    var modal = document.getElementById('sourcepoint-cmp-modal');

    // When the user clicks anywhere outside of the modal, close it
    modal.onclick = function () {
        modal.style.display = 'none';
    }

    var modalLinks = document.querySelectorAll('[rel=sourcepoint-cmp-overlay]');
    for (var i = 0; i < modalLinks.length; i++) {
        modalLinks[i].onclick = function(e) {
            var iframe = document.getElementById('sourcepoint-cmp-modal-iframe');
            if (typeof iframe.src == 'undefined' || !iframe.src) {
                iframe.src = iframe.dataset.src;
            }
            modal.style.display = 'block';
            e.preventDefault();
        }
    }

    window.addEventListener('message', function (message) {
        var msgData = message.data;
        if (!msgData) {
            return;
        }
        if (!msgData.action || !(msgData.action === 'sp.complete' || msgData.action === 'sp.choiceComplete' || msgData.action === 'sp.cancel')) {
            return;
        }
        var iframeWindow = message && message.source;
        if (!iframeWindow) {
            return;
        }
        if (iframeWindow === window) {
            // we are on the privacy manager page so let's trigger a history navigation action here (going back in this case)
            window.history && window.history.go(-1);
            return;
        }
        var iframes = document.getElementsByTagName('iframe');
        var iframe;
        for (var i = 0; i < iframes.length; i++) {
            try {
                if (iframes[i].contentWindow === iframeWindow) {
                    iframe = iframes[i];
                }
            } catch (e) {
            }
        }
        if (!iframe) {
            return;
        }
        var parent = iframe.parentElement;
        if (!parent) {
            return;
        }
        // check to see if the sp_iframe_container is the parent which is a backhanded way of testing to see if this is being rendered by sp in which case we don't need to do anything
        if (window._sp_ && window._sp_.msg && window._sp_.msg.getMorphedClassName && window._sp_.msg.getMorphedClassName('sp_iframe_container') === parent.className) {
            return;
        }

        // here is where we trigger our action either removing iframe or hiding it or navigating back if this is a separate page or something
        //parent.removeChild(iframe);
        modal.style.display = 'none';
    });
})();
