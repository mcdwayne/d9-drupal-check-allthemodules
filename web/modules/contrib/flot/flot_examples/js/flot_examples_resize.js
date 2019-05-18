/**
 * @file
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var placeholder = $('#placeholder');
      // The plugin includes a jQuery plugin for adding resize events to any
      // element.  Add a callback so we can display the placeholder size.
      placeholder.resize(function () {
        $('.message').text('Placeholder is now '
          + $(this).width() + 'x' + $(this).height()
          + ' pixels');
      });
      $('.demo-container').resizable({
        maxWidth: 900,
        maxHeight: 500,
        minWidth: 450,
        minHeight: 250
      });
    }
  };
}(jQuery, Drupal));
