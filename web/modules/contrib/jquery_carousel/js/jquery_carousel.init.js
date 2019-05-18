(function ($, window) {

  "use strict";

  /**
   * Provide the default information for the Jquery Carousel.
   */
  Drupal.behaviors.jqueryCarousel = {
    attach: function (context, settings) {
      var jcarouselSettings = settings.jquery_carousel;
      var keys = [];
      for(var k in jcarouselSettings) {
        keys.push(k);
      }
      for (var i = 0; i < keys.length; i++) {
        var selector = '.' + keys[i];
        if ($(selector, context).length > 0) {
          $(selector, context).carousel(jcarouselSettings[keys[i]]);
        }
      }
    }
  };
})(jQuery, window);
