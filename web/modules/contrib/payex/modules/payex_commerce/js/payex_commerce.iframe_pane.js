/**
 * @file
 * Defines behaviors for the PayEx Commerce iframe pane.
 */

(function ($) {

  'use strict';

  // The receive message callback to handle the message from rpiframe
  var receiveMessage = function (e) {
    if (e.data == 'payExContinue') {
      $("#payex-commerce-iframe").parents("form").submit();
    }
  };

  if (window.addEventListener) {
    window.addEventListener('message', receiveMessage);
  }
  else {
    window.attachEvent('onmessage', receiveMessage);
  }
  
})(jQuery);
