/**
 * @file
 * Initialize Snowflakes On/Off button.
 */

(function ($) {
  'use strict';

  $('.snowflakes-onoff').on('click', function(e) {
    e.preventDefault();
    $('.snowflakes').toggleClass('hidden');
  });

})(jQuery);
