(function ($) {
  Drupal.behaviors.splashifyBehavior = {
    attach: function (context, settings) {
      var jsmode = settings.splashify.mode,
        referrer = document.referrer + '',
        hostname = window.location.hostname + '',
        referrer_check = settings.splashify.refferer_check;

      // This updates the referer string by taking out the url parameter from the
      // url...which is included from google search results (as an example).
      if (referrer.indexOf('?') != -1) {
        referrer = referrer.substr(0, referrer.indexOf('?'));
      }
      // Stop the splash page from show up if on the splash page. Also prevent
      // the splash from showing up from internal links (dependent on the
      // referrer check settings).
      if ((referrer.search(hostname) != -1 && referrer_check)) {
        return;
      }

      if (typeof Drupal.behaviors.splashifyBehavior[jsmode] != 'undefined') {
        Drupal.behaviors.splashifyBehavior[jsmode](context, settings);
      }
    }
  }

})(jQuery);
