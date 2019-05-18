(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const buildFirstEmailUrl = document.getElementById('cr-build-first-email-url').value;
    const retrySyncUrl = document.getElementById('cr-retry-sync-url').value;
    const buildEmailUrl = document.getElementById('cr-build-email-url').value;
    const buildEmailButton = document.getElementById('cr-buildEmail');
    const retrySynchronization = document.getElementById('cr-retrySync');

    function sendAjax(url, callback) {
      CleverReach.Ajax.post(`${url}`, null, (response) => {
        if (response.status === 'success') {
          if (callback) {
            callback();
          }
          else {
            location.reload();
          }
        }
      }, 'json', true);
    }

    function startBuildingEmail(url) {
      sendAjax(buildFirstEmailUrl);
      const win = window.open(url, '_blank');
      win.focus();
    }

    if (buildEmailButton) {
      buildEmailButton.addEventListener('click', () => {
        startBuildingEmail(`${buildEmailUrl}#login`);
      });
    }
    else {
      retrySynchronization.addEventListener('click', () => {
        sendAjax(retrySyncUrl);
      });
    }
  });
}());
