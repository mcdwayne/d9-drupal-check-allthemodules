(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.VkBehavior = {
    attach: function (context, settings) {
      // can access setting from 'drupalSettings';
      var pagesBack = drupalSettings.vk_authentication.vk_authenticationJS.pagesBack;
      var redirectTimeout = drupalSettings.vk_authentication.vk_authenticationJS.redirectTimeout;

      window.setTimeout(function () {
        window.history.back(pagesBack);
      }, redirectTimeout);
    }
  };
})(jQuery, Drupal, drupalSettings);
