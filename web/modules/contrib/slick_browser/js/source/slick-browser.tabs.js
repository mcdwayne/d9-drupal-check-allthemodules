/**
 * @file
 * Provides Slick Browser utilitiy functions.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Slick Browser utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} form
   *   The Entity Browser form HTML element.
   */
  function sbTabs(i, form) {
    var $form = $(form);
    var pos = $form.data('tabsPos');

    // It seems taking time to build JS tabs.
    if (pos) {
      window.setTimeout(function () {
        $('.eb-tabs', form).prependTo('#edit-' + pos);
      }, 800);
    }

    // Adds loading indicator whenever a tab is clicked.
    $form.on('click', '.eb-tabs a', Drupal.slickBrowser.loading);
  }

  /**
   * Attaches Slick Browser tabs behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickBrowserTabs = {
    attach: function (context) {
      $('.form--tabs', context).once('sbTabs').each(sbTabs);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $('.form--tabs', context).removeOnce('sbTabs');
      }
    }
  };

})(jQuery, Drupal);
