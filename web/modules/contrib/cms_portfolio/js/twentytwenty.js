(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.twentytwenty = {
    attach: function (context, settings) {
      $('.twentytwenty-container').once('init').twentytwenty(
        {
          default_offset_pct: drupalSettings.twentytwenty.default_offset_pct
        }
      );
    }
  }
})(jQuery, Drupal, drupalSettings);