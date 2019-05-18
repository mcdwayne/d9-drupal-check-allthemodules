/**
 * @file
 * Consent: Base object initialization.
 */

(function (w) {

  w.Consent = {
    layerReady: false,
    given: null,
    category: 'optin_consent',
    elHasClass: function (el, className) {
      if (el.classList) {
        return el.classList.contains(className);
      }
      else {
        return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
      }
    },
    getCookie: function (name) {
      var nameEQ = name + '=';
      var ca = w.document.cookie.split(';');
      var i;
      var c;
      for (i = 0; i < ca.length; i++) {
        c = ca[i];
        while (c.charAt(0) === ' ') {
          c = c.substring(1, c.length);
        }
        if (c.indexOf(nameEQ) === 0) {
          return c.substring(nameEQ.length, c.length);
        }
      }
      return null;
    },
    userOptedIn: function () {
      var cookie;
      if (this.given === null) {
        this.given = false;
        cookie = this.getCookie('oil_data');
        if (typeof cookie === 'string') {
          cookie = JSON.parse(decodeURIComponent(cookie));
          if (cookie && cookie.opt_in) {
            this.given = true;
          }
        }
      }
      return this.given;
    },
    doOptIn: function (category) {
      if (!this.userOptedIn() && w.AS_OIL && w.AS_OIL.triggerOptIn) {
        this.category = category;
        w.AS_OIL.triggerOptIn();
      }
    },
  };

}(window));
