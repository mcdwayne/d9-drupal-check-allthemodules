/**
 * @file
 * Initialize Eloqua tracking.
 */
var _elqQ = _elqQ || [];

(function (window) {
  "use strict";

  Drupal.behaviors.eloquaTracking = {
    attach: function () {
      // Load the Eloqua logic.
      function async_load() {
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.async = true;
        s.src = '//img.en25.com/i/elqCfg.min.js';
        var x = document.getElementsByTagName('script')[0];
        x.parentNode.insertBefore(s, x);
      }
      if (window.addEventListener) {
        window.addEventListener('DOMContentLoaded', async_load, false);
      }
      else if (window.attachEvent) {
        window.attachEvent('onload', async_load);
      }

      // Wait until Eloqua is available, then track this page request.
      var elqQLoaded = setInterval(function() {
        if (typeof _elqQ !== 'undefined' && typeof _elqQ.push !== 'undefined') {
          _elqQ.push(['elqSetSiteId', drupalSettings.eloqua.siteID]);
          _elqQ.push(['elqTrackPageView']);
          clearInterval(elqQLoaded);
        }
      }, 500);
    }
  };

})(window);
