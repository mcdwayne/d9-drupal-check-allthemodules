(function (window, navigator, drupalSettings) {

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register(drupalSettings.sw_register.path).then(function(registration) {
                // Registration was successful.
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
                // Registration failed.
                console.error('ServiceWorker registration failed: ', err);
            });
        });
    }
    else {
        console.error('ServiceWorker is not supported.');
    }

})(window, navigator, drupalSettings);
