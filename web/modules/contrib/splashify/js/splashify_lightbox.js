(function ($) {
  Drupal.behaviors.splashifyBehavior.lightbox =
    function (context, settings) {
      var url = settings.splashify.url;

      $.colorbox({
        transition: 'elastic',
        iframe: true,
        href: url,
        width: settings.splashify.width,
        height: settings.splashify.height
      });
    }

})(jQuery);
