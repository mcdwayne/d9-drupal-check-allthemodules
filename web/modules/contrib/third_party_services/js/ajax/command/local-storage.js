/**
 * @file
 * Local storage controller from Drupal backend.
 */

(function (storage) {
  'use strict';

  Drupal.AjaxCommands.prototype.localStorage = function (ajax, response) {
    if (response.method in storage && storage[response.method] instanceof Function) {
      storage[response.method].apply(storage, response.args);
    }
  };
})(window.localStorage);
