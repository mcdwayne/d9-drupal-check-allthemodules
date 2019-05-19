/**
 * @file
 * Some basic behaviors and utility functions for RH.
 */

(function ($, Drupal, drupalSettings) {
  var poll_swish_payment = function() {
    setTimeout(function() {
      $.getJSON("/swish/poll/" + drupalSettings.transactionId, function(data) {
        if(data && data.status == "PAID") {
          document.location = drupalSettings.destination;
        }
        else {
          poll_swish_payment();
        }
      });
    }, 2000);
  };
  poll_swish_payment();
})(jQuery, Drupal, drupalSettings);
