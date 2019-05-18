(function ($) {
  'use strict';
  Drupal.behaviors.scrolljs = {
    attach: function (context, settings) {
      var color = drupalSettings.scroll_progress_color;
      $('body').append('<div class="scroll-progress-bottom"><div class="bottom-progress-tooltip">0</div><span class="scroll-progress-bottom-triangle"></span></div>');
      var scrollPercent = 100 * $(window).scrollTop() / ($(document).height() - $(window).height());
      $('.scroll-progress-bottom').css('width', scrollPercent + '%');
      $('.scroll-progress-bottom').css('background-color', color);
      $('.scroll-progress-bottom').css('box-shadow', '0 0 8px ' + color);
      $('.bottom-progress-tooltip').css('background-color', color);
      $('.scroll-progress-bottom-triangle').css('border-top', '11px solid ' + color);
      $(window).scroll(function () {
        var scrollPercent = 100 * $(window).scrollTop() / ($(document).height() - $(window).height());
        $('.scroll-progress-bottom').css('width', scrollPercent + '%');
        $('.bottom-progress-tooltip').html(Math.round(scrollPercent) + '%');
        if (scrollPercent > 0) {
          $('.scroll-progress-bottom').fadeIn();
        }
        else {
          $('.scroll-progress-bottom').fadeOut();
        }

      });
    }
  };
}(jQuery));
