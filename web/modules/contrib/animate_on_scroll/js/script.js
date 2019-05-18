/**
 * @file
 * JavaScript file for the AOS module.
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.aos = {
    attach: function attach(context, settings) {
      AOS.init();
    }
  };

})(jQuery, Drupal, drupalSettings);
