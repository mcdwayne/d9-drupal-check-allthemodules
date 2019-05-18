(function ($) {
  'use strict';
  Drupal.behaviors.circular_progress_js = {
    attach: function (context, settings) {
      var color = drupalSettings.scroll_progress_color;
      var content = '<div class="circular-progress-indicator"><svg><g><circle cx="0" cy="0" r="18" stroke="black" class="animated-circle" transform="translate(50,50) rotate(-90)"/></g><g><circle cx="0" cy="0" r="28" transform="translate(50,50) rotate(-90)"/></g></svg><div class="circular-progress-count">0%</div></div>';
      $('body').append(content);
      $('.circular-progress-count').css('color', color);
      $('svg .animated-circle').css('stroke', color);
      var $circ = $('.animated-circle');
      var $progCount = $('.circular-progress-count');

      $(window).scroll(function () {
        var perc = $(window).scrollTop() / ($(document).height() - $(window).height());
        if (perc > 0) {
          $('.circular-progress-indicator').fadeIn();
        }
        else {
          $('.circular-progress-indicator').fadeOut();
        }
        updateProgress(perc);
      });

      function updateProgress(perc) {
        var circle_offset = 114 * perc;
        $circ.css({'stroke-dashoffset': 126 - circle_offset});
        $progCount.html(Math.round(perc * 100) + '%');
      }
    }
  };
}(jQuery));
