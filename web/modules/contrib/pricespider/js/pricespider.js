/**
 * @file
 * Contains Javascript logic for pricespider.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Attaches the priceSpider behavior to document.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *
   */

  Drupal.behaviors.priceSpider = {
    attach: function (context, settings) {
      rebindPriceSpider(context);
    },
  };

  /**
   * Excluding initial load, checks context for .ps-widget and
   * executes rebind if PriceSpider is available on window.
   *
   * @param {Node} context
   */
  function rebindPriceSpider(context) {
    if (context !== document) {
      if (typeof window.PriceSpider !== 'undefined') {
        if ($(context).find('.ps-widget')) {
          window.PriceSpider.rebind();
        }
      }
      else {
        throw new Error('Cannot find PriceSpider scripts on window');
      }
    }
  }
})(jQuery, Drupal);
