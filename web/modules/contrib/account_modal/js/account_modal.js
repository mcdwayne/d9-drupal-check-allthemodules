(function ($, Drupal, drupalSettings, window) {
  'use strict';

  Drupal.behaviors.accountModal = {
    attach: function () {
      Drupal.AjaxCommands.prototype.accountModalRefreshPage = function () {
        window.location.reload();
      };
    }
  };

}(jQuery, Drupal, drupalSettings, window));
