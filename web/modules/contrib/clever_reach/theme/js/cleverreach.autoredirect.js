var CleverReach = window.CleverReach || {};

(function () {
  'use strict';

  let autoRedirectInterval;
  let startTime;
  let period;
  let countdownTemplate;

  function refreshCounter() {
    const elapsedTime = (new Date()).getTime() - startTime.getTime();
    const countdownValue = parseInt((period - elapsedTime) / 1000);


    const countdownTextEls = document.querySelectorAll('[data-success-panel] [data-success-panel-autoredirect-text]');
    for (let i = 0; i < countdownTextEls.length; i++) {
      countdownTextEls[i].innerHTML = countdownTemplate.replace('%d', `${countdownValue}`);
    }
  }

  function redirect() {
    clearInterval(autoRedirectInterval);
    location.reload();
  }

  function start(waitPeriod) {
    const countdownEl = document.querySelector('[data-success-panel] [data-success-panel-autoredirect-text]');

    period = waitPeriod;
    startTime = new Date();
    countdownTemplate = countdownEl.innerHTML;

    refreshCounter();
    autoRedirectInterval = setInterval(refreshCounter, 250);
    setTimeout(redirect, period);
  }

  CleverReach.AutoRedirect = {
    start
  };
}());
