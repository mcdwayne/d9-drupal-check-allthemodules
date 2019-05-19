(function ($) {
  Drupal.behaviors.splashifyBehavior.redirect =
    function (context, settings) {
      var url = settings.splashify.url;

      window.location.replace(url);
    }

})(jQuery);
