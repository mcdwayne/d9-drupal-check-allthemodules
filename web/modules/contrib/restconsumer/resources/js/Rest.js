/**
 * Initialize the consumer, determine whether we are multilingual and add it to
 * the Drupal main object.
 */
(function ($, Drupal) {
  var initialized;

  function init(context, settings) {
    if (!initialized) {
      initialized = true;
      var connector = new Restconsumer_Wrapper();
      if (settings.path.isMultilingual) {
        connector.setLang(settings.path.currentLanguage);
      }
      Drupal.restconsumer = connector;
    }
  }
  Drupal.behaviors.restconsumer = {
    attach: function (context, settings) {
      init(context, settings);
    }
  };
})(jQuery, Drupal);
