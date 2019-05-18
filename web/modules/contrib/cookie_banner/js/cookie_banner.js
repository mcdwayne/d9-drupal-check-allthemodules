// Cookie banner javascript file
(function ($) {

  Drupal.behaviors.cookie_banner = {
    attach: function (context, settings) {
      var cookie_name     = drupalSettings.cookie_banner.cookie_banner_name;
      var cookie_message  = drupalSettings.cookie_banner.cookie_banner_message;
      // PHP time is expressed in seconds, JS needs milliseconds.
      var cookie_duration = drupalSettings.cookie_banner.cookie_banner_duration * 1000;

      $('body').prepend(cookie_message);

      Drupal.cookie_banner.closeBanner(cookie_name, cookie_duration);

    }
  };

  Drupal.cookie_banner = {};

  Drupal.cookie_banner.closeBanner = function (name, time) {
    var $cookieBanner = $('#cookie-banner');
    if (document.cookie.indexOf(name) == -1) {
      Drupal.cookie_banner.setCookie(name, '1', time);
    } else {
      $cookieBanner.detach();
    }

    $cookieBanner.on('click', '.close', function () {
      $cookieBanner.detach();
    });
  };

  Drupal.cookie_banner.setCookie = function (name, value, time) {
    var expires = "";
    if (time) {
      var date = new Date();
      date.setTime(time);
      expires = "; expires=" + date.toGMTString();
    }
    document.cookie = name + "=" + value + expires + "; path=/";
  };

})(jQuery);
