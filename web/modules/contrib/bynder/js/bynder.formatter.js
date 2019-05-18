/**
 * @file
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Registers behaviours related to Bynder field formatter.
     */
    Drupal.behaviors.BynderFormatter = {
        attach: function () {
            $('.usage-image').tooltip({
                tooltipClass: "bynder-tooltip",
                open: function (event, ui) {
                    ui.tooltip.hover(
                        function () {
                            $(this).fadeTo("slow", 0.5);
                        });
                }
            });
        }
    };

}(jQuery, Drupal));

