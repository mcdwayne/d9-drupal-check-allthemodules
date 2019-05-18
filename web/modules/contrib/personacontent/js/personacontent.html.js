(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Queued variables.
   */
  var keyCache = 'personacontent--html-2';

  Drupal.behaviors.personacontentGlobal = {
    attach: function (context, settings) {
      $(context).find('html').once().each(function() {
        window.personaContent.init();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
