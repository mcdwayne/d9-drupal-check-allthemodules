(function ($) {
  Drupal.behaviors.splashifyBehavior.window =
    function (context, settings) {
      var url = settings.splashify.url;

      window.open(url, 'splash', settings.splashify.size);
    }

})(jQuery);
