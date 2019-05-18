/**
 * @file
 * Consent: Never opt-in.
 */

(function (c) {

  c.given = false;
  c.userOptedIn = function () {
    return false;
  };
  c.doOptIn = function (category) {};

}(window.Consent));
