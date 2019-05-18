/**
 * @file
 * File init.js.
 */

(function (Drupal, settings) {
  Drupal.behaviors.googleTranslatorElement = {

    init: function () {
      var displayMode = settings.googleTranslatorElement.displayMode;
      new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: settings.googleTranslatorElement.languages,
        layout: google.translate.TranslateElement.InlineLayout[displayMode],
      }, settings.googleTranslatorElement.id);
    },

  };
})(Drupal, drupalSettings);
