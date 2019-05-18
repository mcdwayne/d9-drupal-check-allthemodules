/**
 * @file paragraphs-anchor.js
 *
 * Shows anchor link on hover.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /* global anchors */
  if (typeof anchors !== 'undefined') {
    anchors.options.placement = 'left';
    anchors.add('.paragraphs-anchor-link');
  }

}(jQuery, Drupal, drupalSettings));
