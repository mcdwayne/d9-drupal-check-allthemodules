/**
 * @file commandbar.js
 *
 * Defines the behavior of the Drupal administration commandbar.
 */
(function ($, Drupal, drupalSettings) {

"use strict";

Drupal.commandbar = Drupal.commandbar || {};


/**
 * Make autocomplete matches that have a .jump class jump directly to their
 * path when selected.
 */
Drupal.behaviors.commandbar = {
  attach: function (context, settings) {
    $('.commandbar-bar-form').once('commandbar', function () {

      // When a result with a class of .jump is clicked, direct to associated URL.
      $('.commandbar-bar-form').on('click', '.jump', function () {
        window.location = $(this).attr('data-url');
      });

      // Jump to associated URL when 'enter' is pressed and the data-url attribute exists.
      $('.commandbar-bar-form').on('keyup', '#edit-command', function (e) {
        if (!e) {
          e = window.event;
        }
        if (e.keyCode == 13) {
          if ($('.commandbar-bar-form .selected').find('.jump').attr('data-url')) {
            window.location = $('.selected').find('.jump').attr('data-url');
          }
        }
      });
    });
  }
};


}(jQuery, Drupal, drupalSettings));