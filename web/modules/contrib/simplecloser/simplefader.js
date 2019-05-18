/**
 * @file
 * Javascript functions for Selector SimpleFader.
 */

 /*jslint devel: true, browser: true */
 /*global $,jQuery,Drupal,drupalSettings*/

(function ($, Drupal, drupalSettings) {
    "use strict";

    function simplefader_function(elements) {
        elements.forEach(function (element) {
            $(element).fadeOut("fast", function () {
                $(element).fadeIn(5000);
            });
        });
    }

    Drupal.behaviors.simplefader = {
        attach: function () {
            if (drupalSettings.simplefader) {
                var allelements = drupalSettings.simplefader.simplefader_selected_element;
                var elementsArray = allelements.split("\n");
                simplefader_function(elementsArray);
            }
        }
    };

}(jQuery, Drupal, drupalSettings));
