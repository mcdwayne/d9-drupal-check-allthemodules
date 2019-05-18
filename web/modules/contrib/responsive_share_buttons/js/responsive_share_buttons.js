(function ($) {
  'use strict';
  Drupal.behaviors.rsb = {
    attach: function (context, settings) {

      if ($(window).width() > 699) {
        // Browsers with width > 699 get button slide effect.
        $('.block-responsive-share-buttons .share-inner-wrp li').hover(function () {
          var hoverEl = $(this);
          hoverEl.stop(true);
          hoverEl.animate({'margin-left': '0px'}, 'fast').addClass('visible');
        }, function () {
          var hoverEl = $(this);
          hoverEl.stop(true);
          hoverEl.animate({'margin-left': '-117px'}, 'fast').removeClass('visible');
        });
      }

      $('.block-responsive-share-buttons .button-wrap').click(function (event) {

        // Parameters for the Popup window.
        var winWidth = 650;
        var winHeight = 450;
        var winLeft = ($(window).width() - winWidth) / 2;
        var winTop = ($(window).height() - winHeight) / 2;
        var winOptions = 'width=' + winWidth + ',height=' + winHeight + ',top=' + winTop + ',left=' + winLeft;

        // Open Popup window and redirect user to share website.
        window.open($(this).attr('href'), 'Share This Link', winOptions);
        return false;
      });
    }
  };
})(jQuery);
