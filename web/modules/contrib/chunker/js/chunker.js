/**
 * @file
 * Javascript behaviours for chunker.
 */

(function ($) {
  /**
   * On load, trigger the renderer libraries if any.
   *
   * Not every library needs a trigger,
   * collapse.js is automatic;
   * accordion requires us to name an element;
   * Our own alpha-teaser needs to toggle a class onclick.
   */
  Drupal.behaviors.chunker = {
    attach: function (context, settings) {
      $(context).find(".section-alpha-teaser").once('alpha-teaser').each(function () {
        $(this).toggleClass('alpha-teaser-collapsed')
          .click(function () {
            $(this).toggleClass('alpha-teaser-collapsed');
          })

      });
      // Note that accordion DIES if the target is null.
      if ($(".accordion-wrapper", context).length) {
        $(".accordion-wrapper", context).accordion({
          icons: false,
          collapsible: true,
          // Usually defaults to the height of the longest pane. Do not want.
          autoHeight: false,
          heightStyle: "content"
        });
      }

      // Use the Elemental Paginate library.
      if ($(".ePaginate-wrapper", context).length) {
        $(".ePaginate-wrapper").ePaginate({
          items: 1,
          position: 'both',
          minimize: false,
        });
      }
    }
  };

})(jQuery);
