/**
 * @file
 * JavaScript behaviors for GTM dataLayer module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Registers behaviours related to GTM dataLayer.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.dataLayerPusher = {

    attach: function (context) {
      var tags = drupalSettings['datalayer_tags'];

      // If "dataLayer" exists, push the new datalayer tags.
      if (typeof dataLayer !== 'undefined' && typeof tags !== 'undefined') {
        dataLayer.push(tags);
      }
    }

  };

}(jQuery, Drupal, drupalSettings));
