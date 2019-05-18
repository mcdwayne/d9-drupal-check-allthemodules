/**
 * @file
 * Handles link replacement for elf module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Open external links in a new window.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches a handler to a class with external link.
   */
  Drupal.behaviors.elf = {
    attach: function (context, settings) {
      $('a.elf-external', context).on('click', function () {
        window.open($(this).attr('href'));
        return false;
      });
    }
  }

})(jQuery, Drupal, drupalSettings);
