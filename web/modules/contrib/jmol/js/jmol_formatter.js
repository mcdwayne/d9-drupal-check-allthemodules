/**
 * @file
 * Produces a Jmol object using the full-featured library.
 *
 * All Jmol objects in the current page are rendered by this one script.
 */

(function ($, Drupal, drupalSettings, Jmol) {
  'use strict';
  Drupal.behaviors.jmol = {
    attach: function () {
    }
  };
}(jQuery, Drupal, drupalSettings, Jmol));
