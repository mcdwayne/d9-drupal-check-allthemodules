(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.initHaystack = {
    attach: function (context, settings) {
      if (!$('body').hasClass('init-haystack')) {
        drupalSettings.haystack = new Haystack(drupalSettings.haystack_settings);
        $('body').addClass('init-haystack');
      }
    }
  }
})(jQuery, Drupal, drupalSettings);