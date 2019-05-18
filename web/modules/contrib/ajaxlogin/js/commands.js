(function ($, Drupal) {
  'use strict';
  Drupal.AjaxCommands.prototype.reload = function (ajax, response, status) {
    window.location.reload();
  };
})(jQuery, Drupal);
