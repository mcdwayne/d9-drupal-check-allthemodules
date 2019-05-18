/**
 * @file
 * Defines Javascript behaviors for the google adwords module.
 */

(function ($, Drupal, drupalSettings) {

    'use strict';

    /**
     * Behaviors for adding Tracking.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches adwords tracking processing
     */
    Drupal.behaviors.google_adwords_RegisterTracking = {
        attach: function (context) {

            var tracker = new Drupal.google_adwords.Tracker(drupalSettings.google_adwords.defaults);

            $.each(drupalSettings.google_adwords.trackings, function(index, tracking) {
                tracker.addTracking(tracking);
            });

        }
    }

    /**
     * Google AdWords Tracking handler
     */
    Drupal.google_adwords = {}

    Drupal.google_adwords.Tracker = function (defaults) {
        this.defaults = defaults;

        console.debug("ADWORDS:NEW",this);
    }
    Drupal.google_adwords.Tracker.prototype.addTracking = function (tracking) {
        console.debug("ADWORDS:TRACKING", tracking);
    }

})(jQuery, Drupal, drupalSettings);
