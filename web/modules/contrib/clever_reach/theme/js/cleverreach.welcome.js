(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const authUrl = document.getElementById('cr-auth-url').value;
    const checkStatusUrl = document.getElementById('cr-check-status-url').value;
    const wakeupUrl = document.getElementById('cr-wakeup-url').value;
    const loginButton = document.getElementById('cr-log-account');
    const createAccountButton = document.getElementById('cr-new-account');

    function showSpinner() {
      document.getElementsByClassName('cr-loader-big')[0].style.display = 'flex';
      document.getElementsByClassName('cr-connecting')[0].style.display = 'block';
      document.getElementsByClassName('cr-content-window-wrapper')[0].style.display = 'none';
    }

    function hideSpinner() {
      document.getElementsByClassName('cr-loader-big')[0].style.display = 'none';
      document.getElementsByClassName('cr-connecting')[0].style.display = 'none';
      document.getElementsByClassName('cr-content-window-wrapper')[0].style.display = '';
    }

    function startAuthProcess(url) {
      showSpinner();
      const auth = new CleverReach.Authorization(url, checkStatusUrl, wakeupUrl);
      auth.checkConnectionStatus(() => {
        location.reload();
      });
    }

    loginButton.addEventListener('click', () => {
      startAuthProcess(`${authUrl}#login`);
    });

    createAccountButton.addEventListener('click', () => {
      startAuthProcess(`${authUrl}#register`);
    });

    const auth = new CleverReach.Authorization(authUrl, checkStatusUrl);
    showSpinner();
    auth.getStatus(() => {
      hideSpinner();
    });
  });
}());
