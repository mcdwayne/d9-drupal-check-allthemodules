/**
 * @file
 * Enables 'fancyselect' plugin behavior.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.fancyselect = {
    attach: function() {
      $(window).load(function() {
        var fancyselect_dom_selector = drupalSettings.fancyselectSettings.domSelector;
        $(fancyselect_dom_selector).each(function() {
          $(this).fancySelect();
        });
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
