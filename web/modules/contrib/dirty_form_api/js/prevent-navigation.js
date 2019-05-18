(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.preventNavigation = {
    attach: function (context, settings) {
      var dirty = false;
      var $window = $(window);

      $('form').once().each(function () {
        $(this).on('dirty', function () {
          dirty = true;
        });

        $(this).on('submit', function() {
          $window.off('beforeunload.preventNavigation');
        });
      });

      $window.once().each(function() {
        $window.on('beforeunload.preventNavigation', function(e) {
          if (dirty) {
            return Drupal.t('You will lose unsaved work if you continue?');
          }
        });
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
