(function() {
    'use strict';

    document.addEventListener("DOMContentLoaded", function(event) {
    
        function initialSyncCompleteHandler(response) {
            if (response.status === 'completed') {
                attachRedirectButtonClickHandler();
                renderStatistics(response.statistics);
                CleverReach.AutoRedirect.start(5000);
                showSuccessPanel();
            } else {
                doRedirect();
            }
        }
    
        function attachRedirectButtonClickHandler() {
            var e = document.querySelector('[data-success-panel-go-to-dashboard-button]');
            if (e.addEventListener) {
                e.addEventListener("click", doRedirect, false);
            } else if (e.attachEvent) {
                e.attachEvent("click", doRedirect);
            }
        }
    
        function renderStatistics(statistics) {
            var panelMessageEl, successMessage;
    
            panelMessageEl = document.querySelector('[data-success-panel-message]');
            if (panelMessageEl) {
                successMessage = panelMessageEl.outerHTML
                    .replace('%s', statistics.recipients_count)
                    .replace('%s', statistics.group_name);

                panelMessageEl.outerHTML = successMessage;
            }
        }
    
        function showSuccessPanel() {
            document.querySelector('[data-task-list-panel]').classList.toggle('hidden');
            document.querySelector('[data-success-panel]').classList.toggle('hidden');
        }
    
        function doRedirect() {
            location.reload();
        }
    
        CleverReach.StatusChecker.init({
            statusCheckUrl: document.getElementById('cr-admin-status-check-url').value  + '',
            baseSelector: '.cr-container',
            finishedStatus: 'completed',
            onComplete: initialSyncCompleteHandler,
            pendingStatusClasses: ['cr-icofont-wait'],
            inProgressStatusClasses: ['cr-icofont-loader'],
            doneStatusClasses: ['cr-icofont-check']
        });
    });
})();