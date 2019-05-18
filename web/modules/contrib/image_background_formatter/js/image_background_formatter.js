'use strict';

(function ($, Drupal) {

  Drupal.behaviors.image_background_formatter = {
    attach: function (context, settings) {

      $('.image-background-formatter').once().each(function () {
        const bg = $(this).data('bg');
        if (bg !== '' && $(this).css('background-image') === 'none') {
          $(this).css({
            'background-image': 'url(' + bg + ')'
          });
        }
      });
    }
  };

})(jQuery, Drupal);
