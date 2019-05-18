(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    function doRedirect() {
      const defaultRecipientStatus = document.getElementById('cr-syncAsActive').checked;
      const configurationUrl = document.getElementById('cr-configuration').value;

      CleverReach.Ajax.post(configurationUrl, {
        configuredInitialSync: true,
        defaultRecipientStatus
      }, (response) => {
        if (response.status === 'success') {
          location.reload();
        }
        else {
          alert(response);
        }
      }, 'json', true);
    }

    function attachRedirectButtonClickHandler() {
      const e = document.querySelector('[data-success-panel-start-initial-sync]');
      if (e.addEventListener) {
        e.addEventListener('click', doRedirect, false);
      }
      else if (e.attachEvent) {
        e.attachEvent('click', doRedirect);
      }
    }

    function init() {
      attachRedirectButtonClickHandler();
    }

    init();
  });
}());
