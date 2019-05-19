/**
 * @file
 * Extends the AjaxCommands and adds sweetalert as a method.
 */

(function (Drupal) {
  'use strict';

  if (typeof Drupal.AjaxCommands !== 'undefined') {
    Drupal.AjaxCommands.prototype.sweetalert = function (ajax, response, status) {
      swal(response.settings.options);
    };
  }
})(Drupal);
