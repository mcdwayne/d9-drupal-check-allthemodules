/**
 * @file
 * Applies tabs on views semantic tabs display.
 */

(function ($, Drupal) {
  Drupal.behaviors.views_semantic_tabs = {
    attach: function (context, settings) {
      $.each(settings.views_semantic_tabs, function (key, config) {
        $("#" + key).once('views_semantic_tabs--' + key).tabs(config);
      });
    }
  };
})(jQuery, Drupal);