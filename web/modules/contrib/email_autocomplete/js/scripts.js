/**
 * @file
 * JavaScript file for the Email autocomplete module.
 */

(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.email_autocomplete = {
    attach: function attach(context, settings) {
      $('input[type=email]').once().emailautocomplete({
        // Add addtional domains.
        domains: drupalSettings.email_autocomplete.domains
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
