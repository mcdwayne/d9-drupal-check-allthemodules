/**
 * @file
 * Js for adjusting grid height of selectors.
 */

var $ = jQuery;
(function ($, Drupal, drupalSettings) {

  // Attaching events to elements.
  Drupal.gridheightnormalizer = Drupal.gridheightnormalizer || {};
  Drupal.behaviors.gridheightnormalizer = {
    attach: function (context, settings) {
      var selectors = settings.grid_selectors.split("\n");
      $.each(selectors, function (selector) {
        var grid_selector = selectors[selector];
        if ($(grid_selector).length > 0) {
          equalgridboxheight($(grid_selector));
        }
      });
    }
  };

   // Attaching the event on ajax callback.
  $(document).ajaxSuccess(function (data) {
    var selectors = settings.grid_selectors.split("\n");
    $.each(selectors, function (selector) {
      var grid_selector = selectors[selector];
      if ($(grid_selector).length > 0) {
        equalgridboxheight($(grid_selector));
      }
    });
  });

  /**
   * Function for set equal height of selector.
   */
  function equalgridboxheight(grid_selector) {
    var tallest = 0;
    grid_selector.each(function () {
      var thisHeight = $(this).height();
      if (thisHeight > tallest) {
          tallest = thisHeight;
      }
    });
    grid_selector.height(tallest);
  };
})(jQuery, Drupal, drupalSettings);
