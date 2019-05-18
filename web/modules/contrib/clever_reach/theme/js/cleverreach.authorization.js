var CleverReach = window.CleverReach || {};

/**
 * Checks connection status
 */
(function () {
  'use strict';

  /**
   * Configurations and constants
   *
   * @type {{get}}
   */
  const config = (function () {
    const constants = {
      CHECK_STATUS_URL: '',
      STATUS_FINISHED: 'finished'
    };

    return {
      get(name) {
        return constants[name];
      }
    };
  }());

  function AuthorizationConstructor(authUrl, checkStatusUrl, wakeupUrl) {
    this.checkConnectionStatus = function (successCallback) {
      const authWin = window.open(authUrl, 'authWindow', 'toolbar=0,location=0,menubar=0,width=750,height=700');
      const self = this;
      const winClosed = window.setInterval(() => {
        if (authWin.closed) {
          clearInterval(winClosed);
          if (wakeupUrl) {
            CleverReach.Ajax.get(wakeupUrl + config.get('CHECK_STATUS_URL'));
          }

          self.getStatus(successCallback);
        }
      }, 1000);
    };

    this.getStatus = function (successCallback) {
      const self = this;
      CleverReach.Ajax.post(checkStatusUrl + config.get('CHECK_STATUS_URL'), null, (response) => {
        if (response.status === config.get('STATUS_FINISHED')) {
          successCallback();
        }
        else {
          setTimeout(
            function () {
              self.getStatus(successCallback);
            },
            500
          );
        }
      }, 'json', true);
    };
  }

  CleverReach.Authorization = AuthorizationConstructor;
}());
