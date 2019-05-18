/**
 * @file
 * Adds common javascript functionality.
 *
 * Note that this file does not stand alone, and is included as an inline
 * script along with the dynamic tests, experiences, and conditions.
 */

// Cookie functions for getting and setting abjs cookies.
var abCookies = {
  getCookie: function (sKey) {
    'use strict';
    if (!sKey) {
      return null;
    }
    var abKey = abjs.cookiePrefix + sKey;
    return decodeURIComponent(document.cookie.replace(new RegExp('(?:(?:^|.*;)\\s*' + encodeURIComponent(abKey).replace(/[\-\.\+\*]/g, '\\$&') + '\\s*\\=\\s*([^;]*).*$)|^.*$'), '$1')) || null;
  },
  setCookie: function (sKey, sValue) {
    'use strict';
    var abKey = abjs.cookiePrefix + sKey;
    document.cookie = encodeURIComponent(abKey) + '=' + encodeURIComponent(sValue) + '; max-age=' + abjs.cookieLifetime * 24 * 60 * 60 + abjs.cookieDomain + '; path=/' + abjs.cookieSecure;
    return true;
  }
};

var i;
var j;
var fracArray;

// Convert condition and experience function strings into real functions,
// and convert experience fraction strings into numbers.
for (i = 0; i < abjs.tests.length; i++) {
  for (j = 0; j < abjs.tests[i].conditions.length; j++) {
    abjs.tests[i].conditions[j] = new Function(abjs.tests[i].conditions[j] + '\r\n');
  }
  for (j = 0; j < abjs.tests[i].experiences.length; j++) {
    abjs.tests[i].experiences[j].script = new Function(abjs.tests[i].experiences[j].script + '\r\n');
    if (abjs.tests[i].experiences[j].fraction.match('/')) {
      fracArray = abjs.tests[i].experiences[j].fraction.split('/');
      abjs.tests[i].experiences[j].fraction = fracArray[0] / fracArray[1];
    }
    abjs.tests[i].experiences[j].fraction = isNaN(1 * abjs.tests[i].experiences[j].fraction) ? 0 : 1 * abjs.tests[i].experiences[j].fraction;
  }
}

// First, for each test, check if all conditions evaluate to true. If any
// condition evaluates to false, remove that test from the abjs.tests array.
for (i = 0; i < abjs.tests.length; i++) {
  for (j = 0; j < abjs.tests[i].conditions.length; j++) {
    if (!abjs.tests[i].conditions[j]()) {
      abjs.tests.splice(i, 1);
      i--;
      break;
    }
  }
}

// For each test that passses all conditions, determine the experience for this
// user.
for (i = 0; i < abjs.tests.length; i++) {
  // First, check if a cookie exists for this test by checking the cookie's name.
  // If so, the value of the cookie is the index of the experience that this
  // user should have.
  if (abCookies.getCookie(abjs.tests[i].name)) {
    for (j = 0; j < abjs.tests[i].experiences.length; j++) {
      if (abCookies.getCookie(abjs.tests[i].name) === abjs.tests[i].experiences[j].name) {
        abjs.tests[i].activeExperience = j;
        break;
      }
    }
  }
  // If a cookie does not yet exist for this test, generate a random number to
  // determine what experience this user should have by comparing the random
  // number to the fractions assigned for each experience. Set a cookie for
  // this test and experience.
  else {
    var randomNum = Math.random();
    var fractionSum = 0;
    for (j = 0; j < abjs.tests[i].experiences.length; j++) {
      if (randomNum >= fractionSum && randomNum < fractionSum + abjs.tests[i].experiences[j].fraction) {
        abCookies.setCookie(abjs.tests[i].name, abjs.tests[i].experiences[j].name);
        abjs.tests[i].activeExperience = j;
        break;
      }
      fractionSum += abjs.tests[i].experiences[j].fraction;
    }
  }
}

// Run all experience scripts for this user.
for (i = 0; i < abjs.tests.length; i++) {
  if (typeof abjs.tests[i].activeExperience !== 'undefined') {
    abjs.tests[i].experiences[abjs.tests[i].activeExperience].script();
  }
}
