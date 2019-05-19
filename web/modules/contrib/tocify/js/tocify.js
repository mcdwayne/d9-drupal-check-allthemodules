/**
 * @file
 * Defines Javascript behaviors for the tocify module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.nodeDetailsSummaries = {
    attach: function (context) {
      $('.table-of-contents').once().each(function (index) {
        $(this).tocify({
          theme: $(this).attr('data-theme'),
          context: $(this).attr('data-context'),
          selectors: $(this).attr('data-selectors'),
          showAndHide: $(this).attr('data-show-and-hide'),
          showEffect: $(this).attr('data-show-effect'),
          showEffectSpeed: $(this).attr('data-show-effect-speed'),
          hideEffect: $(this).attr('data-hide-effect'),
          hideEffectSpeed: $(this).attr('data-hide-effect-speed'),
          smoothScroll: $(this).attr('data-smooth-scroll'),
          scrollTo: $(this).attr('data-scroll-to'),
          showAndHideOnScroll: $(this).attr('data-show-and-hide-on-scroll'),
          highlightOnScroll: $(this).attr('data-highlight-on-scroll'),
          highlightOffset: $(this).attr('data-highlight-offset'),
          extendPage: $(this).attr('data-extend-page'),
          extendPageOffset: $(this).attr('data-extend-page-offset'),
          history: $(this).attr('data-history'),
          hashGenerator: $(this).attr('data-hash-generator'),
          highlightDefault: $(this).attr('data-highlight-default'),
          ignoreSelector: $(this).attr('data-ignore-selector'),
          scrollHistory: $(this).attr('data-scroll-history')
        }).parent().parent()
          .css('overflow', 'auto')
          .css('height', '80%');
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
