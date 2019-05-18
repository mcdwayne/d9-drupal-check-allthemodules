(function ($, Drupal) {
  "use strict";

  /**
   * Behavior to initialize "Read more" and "Read less" links.
   *
   * @type {{attach: Drupal.behaviors.initReadmoreLinks.attach}}
   */
  Drupal.behaviors.initReadmoreLinks = {
    attach: function (context, settings) {
      $(context)
        .find('.readmore-summary .readmore-link')
        .once('init-readmore-links')
        .each(function () {
          $(this).click(function () {
            var summary = $(this).closest('.readmore-summary');
            summary.hide();
            summary.next('.readmore-text').slideDown(100);
            return false;
          });
        });

      $(context)
        .find('.readmore-text .readless-link')
        .once('init-readmore-links')
        .each(function () {
          $(this).click(function () {
            var text = $(this).closest('.readmore-text');
            text.slideUp(100);
            text.prev('.readmore-summary').slideDown(100);
            return false;
          });
        });
    }
  };

})(jQuery, Drupal);
