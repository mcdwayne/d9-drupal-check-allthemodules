/**
 * @file accordion.js
 *
 * Initialize accordion effect.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  $('.accordion > .field__items').each(function () {
    $(this).accordion({
      collapsible: true
    });
  });
}(jQuery, Drupal, drupalSettings));
