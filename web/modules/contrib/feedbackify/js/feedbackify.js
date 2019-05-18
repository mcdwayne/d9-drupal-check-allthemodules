/**
 * @file
 * JavaScript for the Feedbackify module.
 */

"use strict";

/**
 * Add the Feedbackify script async.
 */
var feedbackifyId = drupalSettings.feedbackify.feedbackifyId,
  feedbackifyPos = drupalSettings.feedbackify.feedbackifyPos,
  feedbackifyColor = drupalSettings.feedbackify.feedbackifyColor;

var fby = fby || [];
fby.push(['showTab', {
  id: feedbackifyId,
  position: feedbackifyPos,
  color: feedbackifyColor
}]);
(function () {
  var f = document.createElement('script');
  f.type = 'text/javascript';
  f.async = true;
  f.src = '//cdn.feedbackify.com/f.js';
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(f, s);
})();
