(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.oomphParagraphsAnimation = {
    attach: function (context) {
      // Watch elements onscroll and trigger some events
      // http://codetheory.in/change-active-state-links-sticky-navigation-scroll/
      var animated = $(
        '.node__content .oomph-animated.oomph-animated__initial',
        context
      );
      var scrollTimeout;

      $(window).on('scroll', function () {
        if (scrollTimeout) {
          clearTimeout(scrollTimeout); // clear the timeout, if one is pending
          scrollTimeout = null;
        }
        // low timeout though, to keep the animation triggers smooth
        // Even with a low timeout, if someone scrolls continuously without
        // stopping, they won't see the animations until they stop scrolling
        scrollTimeout = setTimeout(scrollEvents, 10);
      });

      var scrollEvents = function () {
        var currentPosition = $(this).scrollTop();

        // Trigger animations when articles come into the viewport
        animated.each(function () {
          // Subtracting some from topOffset because otherwise, the element animates
          // too late in the scroll, resulting in something that feels laggy
          var top = $(this).offset().top;
          var windowHeight = $(window).height();
          var topOffset = top - (windowHeight * .75);

          if (currentPosition >= topOffset) {
            $(this).addClass('oomph-animated__active');
          }
        });
      }; // end scrollEvents
    }
  };
})(jQuery, Drupal);
