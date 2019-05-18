/**
 * @file
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Registers behaviours related to Bynder oAuth login process.
     */
    Drupal.behaviors.bynderOAuth = {
        attach: function () {
            $('.oauth-link').once('bynder-oauth').click(function (event) {
                event.preventDefault();
                var login_window = window.open('/bynder-oauth', 'bynder_login');
                var pollTimer = window.setInterval(function () {
                    if (login_window.closed !== false) {
                        $('body').prepend('<div class="overlay-throbber"><div class="throbber-spinner"></div></div></div>');
                        window.clearInterval(pollTimer);
                        $('.oauth-reload').click();
                    }
                }, 200);
            });
        }
    };

}(jQuery, Drupal));
