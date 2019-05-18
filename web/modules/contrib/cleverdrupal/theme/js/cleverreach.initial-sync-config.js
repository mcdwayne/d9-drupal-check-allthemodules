(function () {
    'use strict';

    document.addEventListener("DOMContentLoaded", function (event) {

        function init() {
            attachRedirectButtonClickHandler();
        }

        function attachRedirectButtonClickHandler() {
            var e = document.querySelector('[data-success-panel-start-initial-sync]');
            if (e.addEventListener) {
                e.addEventListener("click", doRedirect, false);
            } else if (e.attachEvent) {
                e.attachEvent("click", doRedirect);
            }
        }

        function doRedirect() {
            var defaultRecipientStatus = document.getElementById('cr-syncAsActive').checked,
                configurationUrl = document.getElementById('cr-configuration').value;

            CleverReach.Ajax.post(configurationUrl, {
                configuredInitialSync: true,
                defaultRecipientStatus: defaultRecipientStatus
            }, function (response) {
                if (response.status === 'success') {
                    location.reload();
                } else {
                    alert(response);
                }
            }, 'json', true);
        }

        init();
    });
})();