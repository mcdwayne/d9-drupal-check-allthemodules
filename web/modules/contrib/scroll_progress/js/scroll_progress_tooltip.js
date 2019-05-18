(function ($) {
  'use strict';
  Drupal.behaviors.flag_progress_js = {
    attach: function (context, settings) {
      var color = drupalSettings.scroll_progress_color;
      var content = '<div id="scroll-progress-flag"><div class="scroll-progress-flag-inner-one"><div class="scroll-progress-flag-inner-two"></div><span class="scroll-progress-triangle"></span></div></div>';
      $('body').append(content);
      $('.scroll-progress-flag-inner-two').css('background', color);
      $('.scroll-progress-triangle').css('border-left', '10px solid ' + color);

      scroll_progress_make_tooltip();
      $(window).scroll(function () {
        scroll_progress_make_tooltip();
      });

      function scroll_progress_make_tooltip() {
        var perc = $(window).scrollTop() / ($(document).height() - $(window).height());
        $('.scroll-progress-flag-inner-two').html(Math.round(perc * 100) + '%');
        if ((perc * 100) > 0) {
          $('#scroll-progress-flag').fadeIn();
        }
        else {
          $('#scroll-progress-flag').fadeOut();
        }
      }
    }
  };
}(jQuery));
