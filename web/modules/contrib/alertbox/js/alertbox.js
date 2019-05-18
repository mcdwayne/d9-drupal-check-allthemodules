/**
 * @file alertbox.js
 *
 * Defines the behavior of the alertbox close button.
 */

(function ($, Drupal, drupalSettings) {

    "use strict";

    Drupal.behaviors.alertbox = {
        attach: function (context, settings) {
            var alertbox_id = '';
            var parent = '';
            var status = '';

            // If no cookies enabled, hide the button and return.
            if (!Drupal.alertbox.cookiesEnabled()) {
                $('.block-alertbox .hide-alertbox').hide();
                return;
            }

            // Decide either to show or hide the alertbox based on config and
            // status.
            $('.block-alertbox').each(function () {
                alertbox_id = $(this).attr('id');
                status = Drupal.alertbox.getCurrentStatus(alertbox_id);
                if (!status || !drupalSettings.alertbox.alertbox_allow_hide) {
                    $(this).show();
                }
            });

            // Action to take when clicking on the close button.
            $('.block-alertbox .hide-alertbox').on('click', function (e) {
                parent = $(this).parents('.block-alertbox');
                alertbox_id = parent.attr('id');
                Drupal.alertbox.setStatus(alertbox_id);
                parent.hide();
                e.preventDefault();
            });
        }
    }

    Drupal.alertbox = {};

    // Get the current cookie status of the block.
    Drupal.alertbox.getCurrentStatus = function (alertbox_id) {
        // not set - enable alertbox
        // 1 - disable alertbox
        return Drupal.alertbox.getCookie(alertbox_id);
    }

    // Set the cookie value for the block, setting the block status.
    Drupal.alertbox.setStatus = function (alertbox_id) {
        var date = new Date();
        date.setDate(date.getDate() + 100);
        document.cookie = alertbox_id + "=1" + ";expires=" + date.toUTCString() + ";path=" + drupalSettings.path.baseUrl;
    }

    // Copy of Drupal.comment.getCookie().
    Drupal.alertbox.getCookie = function (name) {
        var search = name + '=';
        var returnValue = '';
        var offset = -1;

        if (document.cookie.length > 0) {
            offset = document.cookie.indexOf(search);
            if (offset != -1) {
                offset += search.length;
                var end = document.cookie.indexOf(';', offset);
                if (end == -1) {
                    end = document.cookie.length;
                }
                returnValue = decodeURIComponent(document.cookie.substring(offset, end).replace(/\+/g, '%20'));
            }
        }

        return returnValue;
    };

    // Test if cookies are enabled.
    Drupal.alertbox.cookiesEnabled = function () {
        var cookieEnabled = !!(navigator.cookieEnabled);
        if (typeof navigator.cookieEnabled == "undefined" && !cookieEnabled) {
            document.cookie = "testcookie";
            cookieEnabled = (document.cookie.indexOf("testcookie") != -1);
        }
        return (cookieEnabled);
    }

})(jQuery, Drupal, drupalSettings);
