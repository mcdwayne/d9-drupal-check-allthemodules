/**
 * @file
 * Attach the settings for Zurb TwentyTwenty when the page loads.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.twentytwenty = {
    attach: function (context, settings) {
      $('.twentytwenty-container').once('init').twentytwenty(
        {
          default_offset_pct: drupalSettings.twentytwenty.default_offset_pct
        }
      );
    }
  };
})(jQuery, Drupal, drupalSettings);
