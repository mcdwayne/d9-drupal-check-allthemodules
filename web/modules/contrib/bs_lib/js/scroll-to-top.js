/**
 * @file
 * Scroll to top JS.
 */
(function ($) {

  'use strict';

  /**
   * bsLibScrollToTop behaviour.
   *
   * JS logic partially inspired by https://github.com/CodyHouse/back-to-top
   * project.
   */
  Drupal.behaviors.bsLibScrollToTop = {
    attach: function (context, settings) {
      var scrolling = false,
        $html = $('html'),
        $body = $('body');

      $('.bs-lib-scroll-to-top', context).once('bs-lib-scroll-to-top').each(function () {
        var $this = $(this),
          offset = $this.data('offset'),
          offsetOpacity = $this.data('offset_opacity'),
          offsetTop = null,
          opacity = false,
          $positionFixedDisableElement = $($this.data('position_fixed_disable_element')),
          parentTop = null,
          isFixed = $this.hasClass('bs-lib-scroll-to-top--fixed');

        if ($positionFixedDisableElement.length == 0) {
          $positionFixedDisableElement = false;
        }

        function checkScrollToTop() {
          var windowTop = window.scrollY || document.documentElement.scrollTop;
          if (windowTop > offset) {
            $this.addClass('bs-lib-scroll-to-top--show');
            if (windowTop < offsetOpacity) {
              if (!opacity) {
                opacity = true;
                $this.addClass('bs-lib-scroll-to-top--fade-out');
              }
            }
            else if (opacity) {
              opacity = false;
              $this.removeClass('bs-lib-scroll-to-top--fade-out');
            }

            // Disabled fixed positioning when target element is reached.
            if ($positionFixedDisableElement) {
              offsetTop = $this.offset().top;
              parentTop = $positionFixedDisableElement.offset().top;
              if (isFixed && offsetTop >= parentTop) {
                isFixed = false;
                $this.removeClass('bs-lib-scroll-to-top--fixed');
              }
              else if (!isFixed && offsetTop + $this.get(0).getBoundingClientRect().height > windowTop + window.innerHeight) {
                isFixed = true;
                $this.addClass('bs-lib-scroll-to-top--fixed');
              }
            }
          }
          else {
            opacity = false;
            $this.removeClass('bs-lib-scroll-to-top--show bs-lib-scroll-to-top--fade-out')
          }
          scrolling = false;
        }

        if (isFixed) {
          // Update scroll to top visibility on scrolling.
          window.addEventListener('scroll', function() {
            if (!scrolling) {
              scrolling = true;
              (!window.requestAnimationFrame) ? setTimeout(checkScrollToTop, 250) : window.requestAnimationFrame(checkScrollToTop);
            }
          });

          checkScrollToTop();
        }

        $this.click(function () {
          // https://stackoverflow.com/a/26681608
          var $root = $html.scrollTop() ? $html : $body;
          $root.animate({ scrollTop: 0 }, $this.data('duration'), $this.data('easing'));
          return false;
        });
      });
    }
  };

})(jQuery);
