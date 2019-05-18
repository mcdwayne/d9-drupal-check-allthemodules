/**
 * @file
 * Javascript and jQuery functions.
 */

(function($, Drupal, drupalSettings) {
   'use strict';
  Drupal.behaviors.jquery_view_ticker = {
    attach: function(context, settings) {
      $('.jquery-view-ticker').ticker({
        random:        drupalSettings.jQueryviewtricker.random, // Whether to display ticker items in a random order
        itemSpeed:     drupalSettings.jQueryviewtricker.itemSpeed,  // The pause on each ticker item before being replaced
        cursorSpeed:   drupalSettings.jQueryviewtricker.cursorSpeed,    // Speed at which the characters are typed
        pauseOnHover:  drupalSettings.jQueryviewtricker.pauseOnHover,  // Whether to pause when the mouse hovers over the ticker
        finishOnHover: drupalSettings.jQueryviewtricker.finishOnHover,  // Whether or not to complete the ticker item instantly when moused over
        fade:          drupalSettings.jQueryviewtricker.fade,  // Whether to fade between ticker items or not
        fadeInSpeed:   drupalSettings.jQueryviewtricker.fadeInSpeed,   // Speed of the fade-in animation
        fadeOutSpeed:  drupalSettings.jQueryviewtricker.fadeOutSpeed    // Speed of the fade-out animation
    });
    }
  };
})(jQuery, Drupal, drupalSettings);
