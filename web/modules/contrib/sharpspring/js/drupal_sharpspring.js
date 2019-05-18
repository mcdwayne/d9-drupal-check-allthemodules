/**
 * @file
 * Sharpspring specific javascript.
 */

"use strict";

/**
 * Handles sharpspring response.
 *
 * @param resp
 *   response object returned from Sharpspring
 */
function ssDrupalCallback(resp) {
  var sendData = {};

  if (resp) {
    if (resp.contact) {
      sendData = resp.contact;
    }
    else if (resp.lead) {
      sendData = resp.lead;
    }
    drupalSettings.sharpspring.response = sendData;
    Drupal.attachBehaviors();
  }
}

(function ($, window) {

  $(function () {
    var sharpspring = drupalSettings.sharpspring || false;

    if (sharpspring) {
      var _ss = window._ss || [];
      _ss.push(['_setDomain', 'https://' + sharpspring.domain + '/net']);
      _ss.push(['_setAccount', sharpspring.id]);
      _ss.push(['_setResponseCallback', ssDrupalCallback]);
      _ss.push(['_trackPageView']);

      var ss = document.createElement('script');
      ss.type = 'text/javascript';
      ss.async = true;
      ss.src = ('https:' === document.location.protocol ? 'https://' : 'http://') + sharpspring.domain + '/client/ss.js';
      var scr = document.getElementsByTagName('script')[0];
      scr.parentNode.insertBefore(ss, scr);

      window._ss = _ss;
    }

  });

  Drupal.behaviors.sharpspringfns = {
    attach: function (context, settings) {
      var sharpspring = settings.sharpspring || false;
      // This allows additional modules to define their own JS handler functions.
      if (sharpspring && sharpspring.calledFuncs && sharpspring.response) {
        for (var n = 0; n < sharpspring.calledFuncs.length; n++) {
          var fn = sharpspring.calledFuncs[n];
          if (typeof fn === 'function') {
            // SharpSpring module handler functions should accept
            // a response object as a variable.
            fn(sharpspring.response);
          }
        }
      }

    }
  };
})(jQuery,window);
