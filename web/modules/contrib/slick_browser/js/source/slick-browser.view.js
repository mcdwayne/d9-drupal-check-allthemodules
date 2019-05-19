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
   * @param {HTMLElement} view
   *   The view HTML element.
   */
  function sbView(i, view) {
    var $view = $(view);

    /**
     * Build the grid info extracted from exisiting elements.
     *
     * @param {int} j
     *   The index of the current element.
     * @param {HTMLElement} grid
     *   The grid HTML element.
     */
    function buildGridInfo(j, grid) {
      var $grid = $(grid);

      // @todo fault proof.
      var $clone = $grid.find('.views-field:not(.views-field-entity-browser-select, .views-field--preview, .views-field--grid-hidden)').clone();

      $clone.each(function () {
        var $field = $(this);
        if ($field.find('.media, img, iframe').length) {
          $field.empty();
        }
      });

      // Remove the views-field class to avoid CSS override.
      $clone.removeClass('views-field').addClass('views-field--cloned');
      // Add a button to toggle the grid info.
      if (!$('.button-group--grid', grid).length) {
        $grid.append('<div class="button-group button-wrap button-group--grid"><button class="button button--select" type="button">&#43;</button><button class="button button--info" type="button">?</button></div>');
      }

      // Append the new .grid__info element.
      if (!$('.grid__info', grid).length) {
        $grid.append('<div class="grid__info visible-grid" />');
        $('.grid__info', grid).html($clone);
      }
    }

    /**
     * Build plain thumbnails for complex rendered entity for lits/table view.
     *
     * @param {int} j
     *   The index of the current element.
     * @param {HTMLElement} media
     *   The media HTML element.
     */
    function buildThumbnail(j, media) {
      var $media = $(media);
      var thumb = $media.data('thumb');

      if (thumb && !$('.media__thumbnail', media).length) {
        $media.append('<img src="' + thumb + '" alt="' + Drupal.t('Thumbnail') + '" class="media__thumbnail visible-list">');
        $media.addClass('media--list');
      }
    }

    /**
     * Toggle the grid info.
     *
     * @param {jQuery.Event} event
     *   The event triggered by a `click` event.
     */
    function toggleGridInfo(event) {
      event.preventDefault();
      event.stopPropagation();

      $(this).closest('.grid').toggleClass('is-info-active');
    }

    // Add a contextual class that Slick browser is active.
    $view.closest('html').addClass('sb-html');

    // Pass the grid info into .grid__content.
    $view.find('.grid__content').each(buildGridInfo);

    // Replaces complex rendered entity with plain thumbnails for table view.
    $view.find('.media:not(.media--switch)').each(buildThumbnail);

    // After AJAX pager, add sb__main class to view parent element.
    if ($view.parent('div').length) {
      $view.closest('form').find('> div:not(.sb__header, .sb__footer)').addClass('sb__main');
    }

    // Events.
    $view.on('click.sbGridInfo', '.button--info', toggleGridInfo);
  }

  /**
   * Attaches Slick Browser view behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickBrowserView = {
    attach: function (context) {
      $('.view--sb', context).once('sbView').each(sbView);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $('.view--sb', context).removeOnce('sbView').off('.sbGridInfo');
      }
    }
  };

})(jQuery, Drupal);
