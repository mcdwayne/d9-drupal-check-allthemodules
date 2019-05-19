/**
 * @file
 * Provides Slick Browser view utilitiy functions.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Slick Browser utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} switcher
   *   The switcher HTML element.
   */
  function sbViewSwitch(i, switcher) {
    var $switcher = $(switcher);
    var $form = $switcher.closest('.form--sb');
    var $container = $form.length ? $form : $switcher.closest('.view');
    var $head = $('.sb__header');
    var $slick = $container.find('.slick:first');
    var slicked = $slick.length && $('.slick__slider', $slick).hasClass('slick-initialized');
    var $firstGrid = $container.find('.sb__grid:first');
    var classes = $firstGrid.attr('class');

    /**
     * Build the fake table header like.
     */
    function buildTableHeader() {
      var $content = $('.view-content', $container);

      // Faking table header for the list view.
      if ($container.find('.grid').length && !$('.view-list--header', $container).length) {
        var $grid = $firstGrid.find('.grid:first .grid__content');
        var $cloned = $grid.clone();

        $cloned.detach().insertBefore($content);
        $cloned.wrapAll('<div class="view-list view-list--header grid" />').once();

        // Extracts the views-label to be the fake table header.
        $cloned.find('.views-field').each(function () {
          var $item = $(this);
          var txt = $item.find('.views-label').text();

          $item.empty().text(txt);
        });
      }
    }

    /**
     * Switch the view display.
     *
     * @param {jQuery.Event} event
     *   The event triggered by a `click` event.
     */
    function switchView(event) {
      event.preventDefault();

      var $btn = $(event.currentTarget);
      var target = $btn.data('target');
      var $view = $('.view--sb');

      $btn.closest('.button-group').find('.button').removeClass('is-active');
      $btn.addClass('is-active');

      if (target && $view.length) {
        $('.is-info-active').removeClass('is-info-active');
        $view.removeClass('view--sb-grid view--sb-list');
        $view.addClass('view--sb-' + target);

        $view.find('.sb__grid').attr('class', target === 'list' ? 'sb__grid' : $switcher.data('classes'));

        // Manually refresh positioning of slick as the layout changes.
        if (slicked) {
          $('.slick__slider', $container)[0].slick.refresh();
        }
      }
    }

    // Store original classes for the switcher.
    $switcher.data('classes', classes);

    // Build the fake table header.
    buildTableHeader();

    // If the switcher is embedded inside EB, append it to the form header.
    if ($head.length) {
      $head.find('.sb__viewswitch, .slick__arrow').remove();

      $switcher.addClass('sb__viewswitch--header').appendTo($head);

      // With views_infinite_scroll, slick are multiple instances in a page.
      if (slicked) {
        $slick.find('.slick__arrow').prependTo('.sb__viewswitch--header');
      }
    }

    // The switcher can live within, or outside view, when EB kicks in.
    $('.button', switcher).on('click.sbSwitch', switchView);

    // Makes the active button active.
    $('#sb-viewswitch', $container).find('.button--view.is-active').click();
  }

  /**
   * Attaches Slick Browser view behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickBrowserViewSwitch = {
    attach: function (context) {
      $(context).find('.sb__viewswitch').once('sbViewSwitch').each(sbViewSwitch);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $(context).find('.sb__viewswitch').removeOnce('sbViewSwitch');
      }
    }
  };

})(jQuery, Drupal);
