/**
 * @file simple_cookie_compliance.js
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.cookie_compliance = {
    attach: function (context, settings) {

      function setCookie(cname, cvalue, exseconds) {
        var d = new Date();
        d.setTime(d.getTime() + (exseconds * 1000));
        var expires = "expires="+d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
      }

      if (document.cookie.indexOf('simple_cookie_compliance=1') == -1) {
        $('body').prepend(settings.simple_cookie_compliance.template);

        $('.js-cookie-compliance__agree', context).click(function(event) {
            setCookie('simple_cookie_compliance', 1, settings.simple_cookie_compliance.expires);
            $('.cookie-compliance').remove();
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
