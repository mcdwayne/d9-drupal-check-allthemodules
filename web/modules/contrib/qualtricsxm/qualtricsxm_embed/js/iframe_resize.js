/**
 * @file
 *
 * Resize iframe height to fix the iframe content.
 */

(function ($) {
  'use strict';
  // Create IE + others compatible event handler
  var eventMethod = window.addEventListener ? 'addEventListener' : 'attachEvent';
  var eventer = window[eventMethod];
  var messageEvent = eventMethod === 'attachEvent' ? 'onmessage' : 'message';

  // Listen to message from survey
  eventer(messageEvent, function (e) {

    if (e.data === 'closeQSIWindow') {

      setTimeout(function () {

        $('.qualtrics_iframe').height('200px');

      }, 5000);

    }
    else {

      // Just do the Frame Expansion if pixel height is passed
      $('.qualtrics_iframe').height(e.data);
      $('.qualtrics_iframe').width('100%');

    }

  }, false);

})(jQuery);
