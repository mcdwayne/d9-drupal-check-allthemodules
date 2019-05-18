/**
 * @file
 * Call the 'Add to homescreen' library with the Drupal settings.
 */
(function () {

  'use strict';

  Drupal.behaviors.addToHomeScreen = {
    attach: function (context, settings) {

      /* global addToHomescreen */
      settings.addtohomescreen = settings.addtohomescreen || Drupal.settings.addtohomescreen;
      if (typeof (addToHomescreen) == 'function') {
        if (settings.addtohomescreen.debug) {
          addToHomescreen.removeSession();
        }
        addToHomescreen(settings.addtohomescreen);
      }
    }
  };

})();
