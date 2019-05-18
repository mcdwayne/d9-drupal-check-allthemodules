/**
 * @file
 * Consent awareness for Advertising entities.
 */

(function (adEntity, document) {

  adEntity.helpers.getCookie = function (name) {
    var nameEQ = name + '=';
    var ca = document.cookie.split(';');
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
  };

  adEntity.usePersonalization = function () {
    var settings = adEntity.settings;
    var consent;
    var cookie;
    var current_value;
    var matched = false;
    var length;
    var i;
    if (!settings.hasOwnProperty('p13n') || (settings.p13n !== true)) {
      return false;
    }
    if (!settings.hasOwnProperty('consent')) {
      return false;
    }
    consent = settings.consent;
    if (typeof consent.method !== 'string') {
      return false;
    }
    if (consent.method === 'disabled') {
      return true;
    }
    if (!(typeof consent.cookie === 'object')) {
      return false;
    }
    cookie = consent.cookie;
    if (!cookie.hasOwnProperty('name') || !cookie.hasOwnProperty('operator') || !cookie.hasOwnProperty('value')) {
      return false;
    }
    if (typeof cookie.value === 'string') {
      cookie.value = [cookie.value];
    }
    length = cookie.value.length;

    current_value = adEntity.helpers.getCookie(cookie.name);
    if (typeof current_value !== 'string') {
      matched = false;
    }
    else if (cookie.operator === 'e') {
      matched = true;
    }
    else {
      for (i = 0; i < length; i++) {
        switch (cookie.operator) {
          case '==':
            /* eslint eqeqeq: [0, "always"] */
            if (current_value == cookie.value[i]) {
              matched = true;
            }
            break;
          case '>':
            if (current_value > cookie.value[i]) {
              matched = true;
            }
            break;
          case '<':
            if (current_value < cookie.value[i]) {
              matched = true;
            }
            break;
          case 'c':
            if (!(current_value.indexOf(cookie.value[i]) < 0)) {
              matched = true;
            }
            break;
        }
        if (matched) {
          break;
        }
      }
    }
    switch (consent.method) {
      case 'opt_in':
        return matched;
      case 'opt_out':
        return !matched;
      default:
        return false;
    }
  };

}(window.adEntity, window.document));
