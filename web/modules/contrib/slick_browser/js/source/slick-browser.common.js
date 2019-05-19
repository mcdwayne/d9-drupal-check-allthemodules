/**
 * @file
 * Provides Slick Browser utilitiy functions.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Slick Browser utility functions.
   *
   * @namespace
   */
  Drupal.slickBrowser = Drupal.slickBrowser || {

    /**
     * Provides common Slick Browser utilities.
     *
     * @name sb
     *
     * @param {int} i
     *   The index of the current element.
     * @param {HTMLElement} elm
     *   Any slick browser HTML element.
     */
    sb: function (i, elm) {
      var $elm = $(elm);

      $('.slick__arrow button', elm).addClass('button');
      $('.slick__arrow', elm).addClass('button-group button-group--icon');

      $elm.on('click', '.button--wrap__mask', function () {
        $(this).parent().addClass('is-open');
        return false;
      });

      $('.button--remove', elm).on('click mousedown', function () {
        $(this).closest('.grid, .slide, .item-container').addClass('is-deleted');
      });

      $('.button--wrap__confirm, .button-wrap--confirm input', elm).on('mouseleave touchend', function () {
        $(this).parent().removeClass('is-open');
      });
    },

    /**
     * Fixes for hidden slick within details as otherwise broken.
     *
     * @name sbDetails
     *
     * @param {int} i
     *   The index of the current element.
     * @param {HTMLElement} elm
     *   Any details HTML element.
     */
    sbDetails: function (i, elm) {
      var $details = $(elm);
      if ($details.hasClass('sb--safe')) {
        return;
      }
      $details.find('.details-wrapper').addClass('visually-hidden');

      $('summary', elm).on('click', function () {
        if ($details.attr('open')) {
          $details.find('.details-wrapper').removeClass('visually-hidden');
          $details.addClass('sb--safe');
          return false;
        }
      });
    },

    /**
     * Reacts on item container button actions.
     *
     * @name itemContainer
     *
     * @param {int} i
     *   The index of the current element.
     * @param {HTMLElement} elm
     *   The item container HTML element.
     */
    itemContainer: function (i, elm) {
      $('.button', elm).on('mousedown.sbAction', function () {
        $(this).closest('.item-container').addClass('media--loading');
      });
    },

    /**
     * Jump to the top.
     *
     * @name jump
     *
     * @param {HTMLElement} id
     *   The slick widget HTML element ID.
     */
    jump: function (id) {
      if ($('#' + id).length) {
        $('html, body').stop().animate({
          scrollTop: $('#' + id).offset().top - 140
        }, 800);
      }
    },

    /**
     * Add loading indicator in replacement for the stone-aged thobber.
     *
     * @param {jQuery.Event} event
     *   The event triggered by an AJAX `mousedown` event.
     */
    loading: function (event) {
      $(event.currentTarget).closest('.js-form-managed-file, .form--sb').addClass('media--loading');
    },

    /**
     * Removed loading indicator.
     *
     * @param {bool} all
     *   If true, remove all loading classes.
     */
    loaded: function (all) {
      $('.js-form-managed-file').removeClass('media--loading');
      if (all) {
        $('.media--loading').removeClass('media--loading');
      }
    }
  };

  /**
   * Attaches Slick Browser common behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickBrowser = {
    attach: function (context) {
      var me = Drupal.slickBrowser;

      $('.sb', context).once('sb').each(me.sb);
      $('.sb .item-container', context).once('sbItem').each(me.itemContainer);
      $('.sb--details-hidden', context).once('sbDetails').each(me.sbDetails);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $('.sb .item-container', context).find('.button').off('.sbAction');
      }
    }
  };

})(jQuery, Drupal);
