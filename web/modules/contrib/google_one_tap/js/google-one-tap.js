/**
 * @file
 * Google One Tap behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.googleOneTap = {
    attach: function (context, settings) {
      if ($.cookie('google_one_tap.user_canceled') !== "true" || $.cookie('google_one_tap.user_canceled') === undefined) {
        window.onGoogleYoloLoad = (googleyolo) => {
          // The 'googleyolo' object is ready for use.
          googleyolo
            .hint(settings['google_one_tap'])
            .then(
              (credential) => {
            $.ajax({
            url: Drupal.url('google-one-tap/login?_format=json'),
            type: 'POST',
            data: credential,
            dataType: 'json',
            success: function (xssFilteredValue) {
              window.location.reload();
            },
            error: function (xssFilteredValue) {
              alert(Drupal.t('One Tap Sign-In Failed'));
              console.log(xssFilteredValue);
            }
          });
        },
          (error) =>
          {
            switch (error.type) {
              case "userCanceled":
                // The user closed the hint selector. Depending on the desired UX,
                // request manual sign up or do nothing.
                $.cookie('google_one_tap.user_canceled', true);
                break;
              case "noCredentialsAvailable":
                // No hint available for the session. Depending on the desired UX,
                // request manual sign up or do nothing.
                break;
              case "requestFailed":
                // The request failed, most likely because of a timeout.
                // You can retry another time if necessary.
                break;
              case "operationCanceled":
                // The operation was programmatically canceled, do nothing.
                break;
              case "illegalConcurrentRequest":
                // Another operation is pending, this one was aborted.
                break;
              case "initializationError":
                // Failed to initialize. Refer to error.message for debugging.
                break;
              case "configurationError":
                // Configuration error. Refer to error.message for debugging.
                break;
              default:
              // Unknown error, do nothing.
            }
          }
        );
        };
      }
    }
  };

} (jQuery, Drupal));
