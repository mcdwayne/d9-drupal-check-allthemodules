/**
 * Remove status message by clicking on the close button.
 */
(function () {
  'use strict';
  document.getElementById('js-close-status-message').onclick = function (e) {
    var elem = document.getElementById('js-status-message');
    elem.remove();
  };

}());
