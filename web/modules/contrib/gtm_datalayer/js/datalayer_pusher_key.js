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
  Drupal.behaviors.dataLayerPusherKey = {

    attach: function (context) {
      var getNestedObject = function (nestedObj, pathArr) {
        return pathArr.reduce(function (obj, key) {
          return obj && obj[key] !== 'undefined' ? obj[key] : undefined;
        }, nestedObj);
      };

      // Get tag key.
      var key = drupalSettings['datalayer_key'];

      // If "dataLayer" key exists, push the new datalayer tags.
      if (typeof dataLayer !== 'undefined' && typeof key !== 'undefined') {
        dataLayer.forEach(function (element) {
          var value = getNestedObject(element, key);
          if (typeof value !== 'undefined') {
            dataLayer.push(value);
          }
        });
      }
    }

  };

}(jQuery, Drupal, drupalSettings));
