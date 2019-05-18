(function(Drupal, drupalSettings, document, window) {
  'use strict';

  var init = false;

  // This variable must be set to global scope.
  window['refTagger'] = window['refTagger'] || {
    settings: {}
  };

  /**
   * Attaches RefTagger extenral JS, and attach settings.
   */
  Drupal.behaviors.refTagger = {
    attach: function(context) {
      if (init) return;
      init = true;

      window['refTagger'].settings = drupalSettings.refTagger;

      var g = document.createElement('script'),
        s = document.getElementsByTagName('script')[0];
      g.src = "//api.reftagger.com/v2/RefTagger.js";
      s.parentNode.insertBefore(g, s);
    }
  }
}) (Drupal, drupalSettings, document, window);
