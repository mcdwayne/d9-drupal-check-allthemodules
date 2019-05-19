(function ($) {
  Drupal.behaviors.splashifyBehavior.full_screen =
    function (context, settings) {
      var splash_height = $(window).height();

      $('#splashify', context).once('splashify_open').each(function () {
        var $splash = $(this);

        // Get the height of the div. Set it as a custom attribute.
        // Update the height to 0 and then show the div.
        $('body').css('margin-top', splash_height);
        $splash.attr('originalheight', splash_height + 'px');
        $splash.css('position', 'absolute');
        $splash.css('display', 'block');

        // Open the splash.
        openSplash($splash);

        // Track scroll position to destroy splash
        $(context).once('splashify_scroll').on('scroll.splash', function () {
          var $object = $(this);
          var scroll_position = $object.scrollTop();
          if (scroll_position >= splash_height) {
            $object.scrollTop(0);
            // Destroy the splash div...they scrolled passed it.
            var $body = $('body');
            $body.removeClass('splash-active');
            $body.css('margin-top', 0);
            $('#splashify', context).css('height', 0);
            $object.off('scroll.splash');
          }
        });
      });

      // Open the splash div.
      function openSplash($splash) {
        var $body = $('body');
        $body.addClass('splash-active');
        $body.css('margin-top', $splash.attr('originalheight'));
        $('.splash-wrapper').css('height', $splash.attr('originalheight'));
        window.scrollTo(0, 0);
      }

      // Close the splash div.
      function closeSplash() {
        var $body = $('body');
        $body.removeClass('splash-active');
        $body.css('margin-top', 0);
        $('.splash-wrapper').css('height', 0);
      }

    }

})(jQuery);
