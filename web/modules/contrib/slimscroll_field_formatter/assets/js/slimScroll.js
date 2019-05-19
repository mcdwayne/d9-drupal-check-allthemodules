/**
 * @file
 * Contains the definition of the behaviour slimScroll.
 */

(function ($, Drupal, drupalSettings) {
    'use strict';

    /**
     * Attaches the slimScroll Behaviour.
     */
    Drupal.behaviors.slimScroll = {
        attach: function (context, settings) {

        if (typeof drupalSettings.slimscroll != 'undefined') {
          var scrollvars = drupalSettings.slimscroll.view;
          var margin = 'margin-'+scrollvars.position;
          jQuery('.slimScroll-wrapper').slimScroll({
            height: scrollvars.height+'px',
            size: scrollvars.size+'px',
            position: scrollvars.position,
            color: scrollvars.color,
            alwaysVisible: scrollvars.alwaysVisible ? true : false,
            railVisible: scrollvars.railVisible ? true : false,
            railColor: scrollvars.railColor,
            railOpacity: scrollvars.railOpacity,
            wheelStep: scrollvars.wheelStep,
            allowPageScroll: scrollvars.allowPageScroll ? true : false,
            disableFadeOut: scrollvars.disableFadeOut ? false : true,
        }).css({margin: '12px'});
          
        }
     }
    };
})(jQuery, Drupal, drupalSettings);