/**
 * @file
 */

(function ($, Drupal, drupalSettings) {

    "use strict";

    $(document).ready(function () {
        var settings = drupalSettings.okta_saml_login;
        var config = drupalSettings.okta_saml_config;
        var redirectUrl = settings.redirect_url;
        var oktaSignIn = new OktaSignIn(config);

        oktaSignIn.renderEl(
            { el: '#okta-login-container' },
            function (res) {
                if (res.status === 'SUCCESS') { res.session.setCookieAndRedirect(redirectUrl); }
            }
        );
    });

})(jQuery, Drupal, drupalSettings);