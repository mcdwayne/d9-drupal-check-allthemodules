/**
 * @file
 * Description.
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.text_rotator_filter = {
    attach: function (context, settings) {
      $(".filter-rotate").once('text_rotator_filter').textrotator({
        animation: drupalSettings.text_rotator_filter.animation,
        separator: "|",
        speed: drupalSettings.text_rotator_filter.speed
      });
    }
  }
})(jQuery, Drupal, drupalSettings);