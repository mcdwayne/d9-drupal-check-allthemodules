/**
 * @file
 * Contains the definition of JqueryuiField Behaviours.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.JqueryuiField = {
    attach: function (context, settings) {
      // Jquery Tabs
      jQuery('#jqueryui_tabs').tabs();
      // Jquery Accordion
      jQuery('#jqueryui_accordion').accordion();

    }
  };
})(jQuery, Drupal, drupalSettings);
