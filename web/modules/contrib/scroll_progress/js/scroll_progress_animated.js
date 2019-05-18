(function ($) {
  'use strict';
  Drupal.behaviors.scroll_progress_animated = {
    attach: function (context, settings) {
      var color = drupalSettings.scroll_progress_color;
      var text = '<svg viewBox="0 0 100.6 107.6" id="star-svg"><path fill="none" stroke="' + color + '" stroke-width="5" id="star-path" d="M43.7,65.8L19.9,83.3c-2.9,1.9-5.1,3.2-7.9,3.2c-5.7,0-10.5-5.1-10.5-10.8c0-4.8,3.8-8.2,7.3-9.8l27.9-12L8.8,41.4c-3.8-1.6-7.3-5.1-7.3-9.8c0-5.7,5.1-10.5,10.8-10.5c2.9,0,4.8,1,7.6,3.2l23.8,17.4l-3.2-28.2c-1-6.7,3.5-12,9.8-12c6.3,0,10.8,5.1,9.8,11.7L57,41.8l23.8-17.4c2.9-2.2,5.1-3.2,7.9-3.2c5.7,0,10.5,4.8,10.5,10.5c0,5.1-3.5,8.2-7.3,9.8L63.9,53.8l27.9,12c3.8,1.6,7.3,5.1,7.3,10.1c0,5.7-5.1,10.5-10.8,10.5c-2.5,0-4.8-1.3-7.6-3.2L57,65.8l3.2,28.2c1,6.7-3.5,12-9.8,12c-6.3,0-10.8-5.1-9.8-11.7L43.7,65.8z"/></svg>';
      $('body').append(text);

      // Get a reference to the <path>
      var path = document.querySelector('#star-path');

      // Get length of path... ~577px in this case
      var pathLength = path.getTotalLength();

      // Make very long dashes (the length of the path itself)
      path.style.strokeDasharray = pathLength + ' ' + pathLength;

      // Offset the dashes so the it appears hidden entirely
      path.style.strokeDashoffset = pathLength;

      // Jake Archibald says so
      // https://jakearchibald.com/2013/animated-line-drawing-svg/
      path.getBoundingClientRect();

      // When the page scrolls...
      window.addEventListener('scroll', function (e) {
        // What % down is it?
        // https://stackoverflow.com/questions/2387136/cross-browser-method-to-determine-vertical-scroll-percentage-in-javascript/2387222#2387222
        var scrollPercentage = (document.documentElement.scrollTop + document.body.scrollTop) / (document.documentElement.scrollHeight - document.documentElement.clientHeight);
        // Length to offset the dashes
        var drawLength = pathLength * scrollPercentage;
        // Draw in reverse
        path.style.strokeDashoffset = pathLength - drawLength;
        // When complete, remove the dash array, otherwise shape isn't quite sharp
        // Accounts for fuzzy math
        if (scrollPercentage >= 0.99) {
          path.style.strokeDasharray = 'none';
        }
        else {
          path.style.strokeDasharray = pathLength + ' ' + pathLength;
        }
      });
    }
  };
}(jQuery));
