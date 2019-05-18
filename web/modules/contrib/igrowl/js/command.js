/**
 * @file
 * Adds the iGrowl function into the AjaxCommands, which will call iGrowl with the passed configuration.
 */

(function ($, Drupal) {
  'use strict';

  if (typeof Drupal.AjaxCommands !== 'undefined') {
    Drupal.AjaxCommands.prototype.igrowl = function (ajax, response) {
      $.iGrowl(response.settings.options);
    };
  }
})(jQuery, Drupal);
