/**
 * @file
 * Micro SSO behaviors.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.micro_sso = Drupal.behaviors.micro_sso || {};
  Drupal.behaviors.micro_sso.attach = function (context) {
    if (Drupal.behaviors.micro_sso.may()) {
      Drupal.behaviors.micro_sso.check();
    }
  };

  // Tell the browser to stop checking for a while.
  Drupal.behaviors.micro_sso.stop = function () {
    // Wait for 10 min.
    // @TODO make this configurable.
    var time = Math.floor(Date.now() / 1000) + 600;
    $.cookie('micro_sso', time);
  };

  // Can the browser attempt to login ?
  Drupal.behaviors.micro_sso.may = function () {
    var now = Math.floor(Date.now() / 1000);
    var stop = $.cookie('micro_sso');
    return !stop || stop < now;
  };

  /**
   * Check on the master if user can log in and the micro site is valid.
   *
   * Gets an uri with a token if user is logged in on the master and then uses
   * it on the micro site to log user in.
   */
  Drupal.behaviors.micro_sso.check = function () {
    $.ajax(drupalSettings.micro_sso.master, {
      'crossDomain': true,
      'method': 'POST',
      'data': {
        "source": window.location.origin
      },
      'xhrFields': {
        'withCredentials': true
      }
    }).done(Drupal.behaviors.micro_sso.login);
  };

  /**
   * Login the user on the micro site.
   */
  Drupal.behaviors.micro_sso.login = function (data) {
    if (data.status === 200 && data.login && data.login.uri && data.login.token) {
      $.ajax(data.login.uri, {
        'data': {
          "token": data.login.token,
          "destination": data.login.destination
        },
        'method': 'POST'
      }).done(Drupal.behaviors.micro_sso.finalize);
    }
    else {
      Drupal.behaviors.micro_sso.stop();
    }
  };

  /**
   * Reload the page in case of a successful login.
   */
  Drupal.behaviors.micro_sso.finalize = function (data) {
    // Don't retry before 10 min.
    Drupal.behaviors.micro_sso.stop();
    if (data.status === 200 && data.success === true) {
      if (data.destination) {
        window.location.href = data.destination;
      }
      else {
        window.location.reload();
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
