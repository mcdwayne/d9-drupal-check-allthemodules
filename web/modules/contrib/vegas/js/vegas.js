/**
 * @file
 * Vegas jQuery Plugin Drupal Integration.
 */

(function ($) {

/**
 * Drupal behavior vegas.
 */
Drupal.behaviors.vegas = {
  attach: function (context, settings) {

    var vegas = drupalSettings.vegas.settings || [];

    if (vegas) {
      $('body', context).once('vegas').vegas(vegas);
    }
  }
};

})(jQuery);
