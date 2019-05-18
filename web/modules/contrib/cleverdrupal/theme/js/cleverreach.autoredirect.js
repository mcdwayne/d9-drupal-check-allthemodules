var CleverReach = window['CleverReach'] || {};

(function () {
    'use strict';

    var autoRedirectInterval, startTime, period, countdownTemplate;

    function start(waitPeriod) {
        var countdownEl = document.querySelector('[data-success-panel] [data-success-panel-autoredirect-text]');

        period = waitPeriod;
        startTime = new Date();
        countdownTemplate = countdownEl.innerHTML;

        refreshCounter();
        autoRedirectInterval = setInterval(refreshCounter, 250);
        setTimeout(redirect, period);
    }

    function refreshCounter() {
        var elapsedTime = (new Date()).getTime() - startTime.getTime(),
            countdownValue = parseInt((period - elapsedTime) / 1000),
            i, countdownTextEls;

        countdownTextEls = document.querySelectorAll('[data-success-panel] [data-success-panel-autoredirect-text]');
        for (i = 0; i < countdownTextEls.length; i++) {
            countdownTextEls[i].innerHTML = countdownTemplate.replace('%d', countdownValue + "");
        }
    }

    function redirect() {
        clearInterval(autoRedirectInterval);
        location.reload();
    }

    CleverReach.AutoRedirect = {
        start: start
    };
})();