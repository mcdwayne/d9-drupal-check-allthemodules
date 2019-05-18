/**
 * @file openinbound.js
 */
(function ($, Drupal) {

    "use strict";

    /**
     * Registers behaviours related to view widget.
     */

    Drupal.behaviors.OpenInboundTracker = {
        attach: function (context, settings) {
            //alert(settings.openinbound.tracking_id);
            var openinbound_script = document.createElement('script');
            openinbound_script.src = 'https://api.openinbound.com/tracker.js?tracking_id=' + settings.openinbound.tracking_id;
            document.body.appendChild(openinbound_script);
        }
    };

}(jQuery, Drupal));