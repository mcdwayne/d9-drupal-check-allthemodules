/**
 * @file
 * Attaches ajax related functionalities.
 */

// Ajax related functionality is only attached if jQuery is defined. Library
// where this file is loaded doesn't depend on jQuery to not create hard
// dependency for it.
if (typeof jQuery !== 'undefined') {
  (function($, Drupal) {

    'use strict';

    /**
     * Callback function to detach loading animation from links with events.
     *
     * @param {Array} links
     *   The list of links.
     *
     * @return {Array}
     *   Returns new list of links.
     */
    Drupal.loading_animation.linkListAlterCallbacks.jquery = function(links) {
      var altered_links = [];
      for (var i = 0; i < links.length; i++) {
        if (typeof $._data(links[i], 'events') == 'undefined' || $.isEmptyObject($(links[i]).data('events'))) {
          altered_links.push(links[i]);
        }
      }

      return altered_links;
    };

    /**
     * Initialization for ajax related functionalities.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches the loading animation functionality to the ajax related
     *   functionalities.
     */
    Drupal.behaviors.loading_animation.ajax = {
      attach : function(context, settings) {
        // Show on form submit
        if (settings.loading_animation.show_on_form_submit) {
          // Only execute if no other js events are registered to prevent cases
          // where page is not being reloaded and layer does not close though.
          $(context).find("form" + subselectorSuffix).submit(function() {
            $(context).ajaxStop(function() {
              // Hide loading animation after ALL ajax events have finished
              Drupal.behaviors.loading_animation.loadingAnimation.hide();
            });
          });
        }
      }
    };

  })(jQuery, Drupal);
}



