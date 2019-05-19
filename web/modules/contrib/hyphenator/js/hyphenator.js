(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.hyphenator = {
    attach: function attach(context) {
      // Checking settings.
      if (!drupalSettings.hyphenator || drupalSettings.hyphenator.selector === '') {
        return;
      }

      var options = drupalSettings.hyphenator.options;
      var exceptions = drupalSettings.hyphenator.exceptions;

      // Replace selector function.
      options.selectorfunction = function () {
        return $(drupalSettings.hyphenator.selector, context).once('hyphenator').get();
      };

      // Exceptions.
      $.each(exceptions, function (lang, words) {
        if (lang == 'GLOBAL') {
          lang = '';
        }
        Hyphenator.addExceptions(lang, words);
      });

      Hyphenator.config(options);
      Hyphenator.run();
    }
  };

})(jQuery, Drupal, drupalSettings);