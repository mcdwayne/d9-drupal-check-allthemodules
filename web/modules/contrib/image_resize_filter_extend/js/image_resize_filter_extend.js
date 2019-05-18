(function ($) {
  "use strict";
  /**
   * Enable the colorbox link to image source functionality.
   */
  Drupal.behaviors.imageResizeFilterExtend = {
    attach: function (context, drupalSettings) {
      $('a.colorbox[rel="colorbox"]', context).once().click(function (event) {
        event.preventDefault();
        $(this).colorbox();
      });
    }
  };
})(jQuery);

