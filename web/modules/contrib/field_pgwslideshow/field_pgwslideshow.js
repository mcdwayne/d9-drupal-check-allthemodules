/**
 * @file
 * Integrate the field_pgwslideshow module with the PgwSlideshow library.
 */

(function ($) {

  "use strict";

  Drupal.behaviors.field_pgwslideshow = {
    attach: function (context, settings) {
      $('.pgwSlideshow, .pgwSlideshowLight', context).once('field_pgwslideshow_load', function () {
        var id = $(this).attr('id');
        $(this).pgwSlideshow(Drupal.settings.field_pgwslideshow[id]);
      });
    }
  };

})(jQuery);
