/**
 * @file
 * Loaded on 403 pages to check if user is logged in on main language domain.
 *
 * If so, it logs in user on current website and reload page.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.i18n_sso = Drupal.behaviors.i18n_sso || {};
  Drupal.behaviors.i18n_sso.attach = function (context) {
    Drupal.behaviors.i18n_sso.initLogin();
  };

  /**
   * Calls get-token url on main domain (domain of the main language).
   *
   * Gets a token if user is logged in and then uses it on local domain to log
   * user in.
   */
  Drupal.behaviors.i18n_sso.initLogin = function () {
    $(drupalSettings.i18n_sso.wrapperSelector).once('i18n_sso').each(function () {
      var $div = $('<div id="sso-waiting">');
      $div.html(drupalSettings.i18n_sso.waiting);
      var $this = $(this);
      $this.prepend($div);
      Drupal.behaviors.i18n_sso.getToken();
    });
  };

  /**
   * Retrieves token on configured url and calls the useToken function.
   */
  Drupal.behaviors.i18n_sso.getToken = function () {
    $.ajax(drupalSettings.i18n_sso.ssoUrl, {
      crossDomain: true,
      data: {
        origin: window.location.origin
      },
      method: 'POST',
      xhrFields: {
        withCredentials: true
      }
    }).done(Drupal.behaviors.i18n_sso.useToken);
  };

  /**
   * Uses token on configured url.
   *
   * If result is "true", displays the message and reload the page.
   *
   * @param {object} data
   *   The data retrieved from the SSO URL endpoint.
   */
  Drupal.behaviors.i18n_sso.useToken = function (data) {
    $('#sso-waiting').html(data.message);

    if (data.token !== false) {
      $.ajax(drupalSettings.i18n_sso.ssoLogin, {
        method: 'POST',
        data: {
          token: data.token
        }
      }).done(function (data) {
        $('#sso-waiting').html(data.message);
        if (data.success === true) {
          window.location.reload();
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
