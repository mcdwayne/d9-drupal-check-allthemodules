/**
 * @file
 * JavaScript for paragraph type background.
 */

(function ($) {
  'use strict';
  $.stellar({
    // Set scrolling to be in either one or both directions
    horizontalScrolling: false,
    verticalScrolling: true,

    // Set the global alignment offsets
    horizontalOffset: 0,
    verticalOffset: 0,

    // Refreshes parallax content on window load and resize
    responsive: true,

    // Select which property is used to calculate scroll.
    // Choose 'scroll', 'position', 'margin' or 'transform',
    // or write your own 'scrollProperty' plugin.
    scrollProperty: 'scroll',

    // Select which property is used to position elements.
    // Choose between 'position' or 'transform',
    // or write your own 'positionProperty' plugin.
    positionProperty: 'position',

    // Enable or disable the two types of parallax
    parallaxBackgrounds: true,
    parallaxElements: false,

    // Hide parallax elements that move outside the viewport
    hideDistantElements: true,

    // Customise how elements are shown and hidden
    hideElement: function($elem) { $elem.hide(); },
    showElement: function($elem) { $elem.show(); }
  });

  $.stellar.positionProperty.position = {
    setTop: function($element, newTop, originalTop) {
      $element.css('top', newTop);
    },
    setLeft: function($element, newLeft, originalLeft) {
      $element.css('left', newLeft);
    }
  };

  function setCorrectBGSize() {
    $('.paragraph--type--parallax > .bg-image').each(function () {
      var imgWidth = $(this).data('naturalWidth');
      var windowWidth = window.innerWidth;

      if (imgWidth < windowWidth) {
        $(this).css('background-size', '100% auto');
      }
      else {
        $(this).css('background-size', imgWidth + 'px auto');
      }
    });
  }

  $(document).ready(setCorrectBGSize);
  $(window).on('resize', setCorrectBGSize);

})(jQuery);
