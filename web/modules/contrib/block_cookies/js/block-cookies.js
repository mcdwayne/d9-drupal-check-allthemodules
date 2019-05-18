(function ($, Drupal, drupalSettings) {

  'use strict';

  function getCookie(cname) {
    let name = cname + '=';
    let ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return '';
  }

  function deleteAllCookies() {
    let cookies = document.cookie.split(';');
    for (let i = 0; i < cookies.length; i++) {
      let name = cookies[i].split('=')[0].trim();
      deleteCookie(name);
    }
  }

  function deleteCookie(name) {
    if (name !== 'cookie-agreed' && !(name.substr(0, 4) === 'SESS' && name.length === 36)) {
      document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
    }
  }

  const blockAllCookies = () => {
    if (!document.__defineGetter__) {
      Object.defineProperty(document, 'cookie', {
        get: function () {
          return '';
        },
        set: function () {
          return true;
        },
      });
    } else {
      document.__defineGetter__('cookie', function () {
        return '';
      });
      document.__defineSetter__('cookie', function () {
      });
    }
  };

  let cookie_agreed = getCookie('cookie-agreed');
  if (cookie_agreed === '0') {
    document.cookie = 'cookie-agreed=' + cookie_agreed;
    deleteAllCookies();
    Drupal.eu_cookie_compliance.showBanner = function () {
      return false;
    };
    blockAllCookies();
  }

})(jQuery, Drupal, drupalSettings);
