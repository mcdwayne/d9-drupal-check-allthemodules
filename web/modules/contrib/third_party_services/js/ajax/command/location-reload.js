/**
 * @file
 * Trigger location reloading from Drupal backend.
 */

(function (location) {
  'use strict';

  Drupal.AjaxCommands.prototype.locationReload = function () {
    location.reload();
  };
})(window.location);
