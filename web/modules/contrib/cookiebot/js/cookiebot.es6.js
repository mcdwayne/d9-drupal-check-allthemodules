/**
 * @file
 * Cookiebot functionality.
 */

((($, Drupal) => {
  Drupal.behaviors.cookiebot = {
    attach: function attach(context) {
      $('.cookiebot-renew', context).once().on('click', (event) => {
        event.preventDefault();

        if (typeof Cookiebot === 'undefined') {
          return;
        }

        Cookiebot.renew();
      });
    },
  };

  Drupal.cookiebot = {

    /**
     * Updates cookies for Cookiebot.
     *
     * We set our own cookies to be able to provide integration with other Drupal
     * modules, without relying on the cookies of Cookiebot, since those are not
     * part of the public API.
     */
    updateCookies() {
      const cookieNames = [
        'necessary',
        'preferences',
        'statistics',
        'marketing',
      ];

      $.each(cookieNames, (index, cookieName) => {
        if (Cookiebot.consent[cookieName] === true && $.cookie(`cookiebot-consent--${cookieName}`) !== '1') {
          $.cookie(`cookiebot-consent--${cookieName}`, '1', {
            path: '/',
          });
          return;
        }

        if (Cookiebot.consent[cookieName] === false && $.cookie(`cookiebot-consent--${cookieName}`) !== '0') {
          $.cookie(`cookiebot-consent--${cookieName}`, '0', {
            path: '/',
          });
        }
      });
    },
  };
})(jQuery, Drupal));

/* eslint-disable no-unused-vars, camelcase */

/**
 * The asynchronous callback when the user accepts the use of cookies.
 *
 * This is also called on every page load when cookies are already accepted.
 */
function CookiebotCallback_OnAccept() {
  Drupal.cookiebot.updateCookies();
}

/**
 * The asynchronous callback when the user declines the use of cookies.
 *
 * This is also called on every page load when cookies are already declined.
 */
function CookiebotCallback_OnDecline() {
  Drupal.cookiebot.updateCookies();
}

/* eslint-enable no-unused-vars, camelcase */
