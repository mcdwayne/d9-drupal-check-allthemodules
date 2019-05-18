/**
 * @file
 * Contains Drupal behavior(s) to initialize HeadroomJS.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.headroomjs = {
    attach: function (context, settings) {
      if (typeof drupalSettings.headroomjs !== 'undefined' && $(drupalSettings.headroomjs.selector).length !== 0) {
        $(drupalSettings.headroomjs.selector).once('init').headroom({
          offset: drupalSettings.headroomjs.offset,
          tolerance: drupalSettings.headroomjs.tolerance,
          classes: {
            initial: drupalSettings.headroomjs.initial_class,
            pinned: drupalSettings.headroomjs.pinned_class,
            unpinned: drupalSettings.headroomjs.unpinned_class,
            top: drupalSettings.headroomjs.top_class,
            not_top: drupalSettings.headroomjs.not_top_class
          }
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);