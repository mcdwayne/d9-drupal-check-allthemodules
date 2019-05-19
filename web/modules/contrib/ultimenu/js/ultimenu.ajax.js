/**
 * @file
 * Provides AJAX functionality for Ultimenu blocks.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.ultimenu = Drupal.ultimenu || {};

  /**
   * Ultimenu utility functions for the ajaxified links, including main menu.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The ultimenu HTML element.
   */
  function doUltimenuAjax(i, elm) {
    $(elm).off().on('mouseover click touchstart', '.ultimenu__link[data-ultiajax-trigger]', Drupal.ultimenu.triggerAjax);
  }

  /**
   * Attaches Ultimenu behavior to HTML element [data-ultiajax].
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ultimenuAjax = {
    attach: function (context) {

      // Modifies functionality for any of the ajaxified ultimenus.
      $(context).find('[data-ultiajax]').once('ultiajax').each(doUltimenuAjax);
    }
  };

})(jQuery, Drupal);
