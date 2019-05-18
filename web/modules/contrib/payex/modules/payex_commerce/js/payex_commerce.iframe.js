/**
 * @file
 * Defines behaviors for the PayEx Commerce iframe page.
 */

(function ($, drupalSettings) {

  'use strict';

  function getOrigin() {
    var defaultPorts = {"http:": 80, "https:": 443};
    return window.location.protocol + "//" + window.location.hostname
        + (((window.location.port) && (window.location.port != defaultPorts[window.location.protocol]))
            ? (":" + window.location.port) : "");
  }

  $(document).ready(function() {
    if (drupalSettings.payExIframeStatus == 'fail') {
      window.parent.location = window.parent.location;
    }
    else if (drupalSettings.payExIframeStatus == 'continue') {
      window.parent.postMessage('payExContinue', getOrigin());
    }
  });
  
})(jQuery, drupalSettings);
