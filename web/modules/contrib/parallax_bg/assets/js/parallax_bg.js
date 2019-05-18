(function ($) {
  "use strict";

  /**
   * @type {{attach: Drupal.behaviors.initParallaxBackground.attach}}
   */
  Drupal.behaviors.initParallaxBackground = {
    attach: function (context, settings) {
      // Make sure this behavior is processed only if parallax is defined.
      if (typeof jQuery.fn.parallax === 'undefined') {
        return;
      }

      // Make sure this behavior is processed only if localScroll is defined.
      if (typeof jQuery.fn.localScroll === 'undefined') {
        return;
      }

      // Make sure this behavior is processed only if scrollTo is defined.
      if (typeof jQuery.fn.scrollTo === 'undefined') {
        return;
      }

      $.each(settings['parallax_bg'], function () {
        var element = this;

        $(context)
          .find(element.selector)
          .once('init-parallax-background')
          .each(function () {
            $(this).parallax(element.position, parseFloat(element.speed));
          });
      });
    }
  };

}(jQuery));
