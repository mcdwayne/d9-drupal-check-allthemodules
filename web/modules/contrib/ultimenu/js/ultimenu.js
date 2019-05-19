/**
 * @file
 * Provides mobile toggler for the the Ultimenu main block.
 */

(function ($, Drupal, drupalSettings, window, document) {

  'use strict';

  var _hidingTimer = void 0;
  var _waitingTimer = void 0;
  var _isExpanded = 'is-ultimenu-expanded';
  var _isActive = 'is-ultimenu-canvas--active';
  var _isHiding = 'is-ultimenu-canvas--hiding';
  var _ultimenuMain = '[data-ultimenu="main"]';
  var $body = $('body');
  var $hamburger = $('[data-ultimenu-button]');

  Drupal.ultimenu = Drupal.ultimenu || {
    documentWidth: 0,
    doResizeMain: function () {
      var me = this;
      var width = $(document).width();

      // Do not cache the selector, to avoid incorrect classes with its cache.
      if ($('[data-ultimenu-button]').is(':hidden')) {
        $body.removeClass(_isExpanded + ' ' + _isActive);
      }
      else {
        $body.addClass(_isActive);
      }

      if (width !== me.documentWidth) {
        return;
      }

      if ($('[data-ultimenu-button]').is(':hidden')) {
        $body.removeClass(_isExpanded + ' ' + _isActive);
      }
      else if (width !== me.documentWidth) {
        $body.addClass(_isExpanded);

        if ($('[data-ultimenu-button]').is(':visible')) {
          $body.addClass(_isActive);
        }
      }

      me.documentWidth = width;
    },

    triggerAjax: function (e) {
      // Use currentTarget, not target, to also works for mobile caret.
      var $trigger = $(e.currentTarget);
      var $li = $trigger.closest('.has-ultimenu');
      var $ajax = $li.find('.ultimenu__ajax');

      var cleanUp = function () {
        // Removes attribute to prevent this event from firing again.
        $trigger.removeAttr('data-ultiajax-trigger');
      };

      // The AJAX link will be gone on successful AJAX request.
      if ($ajax.length) {
        // Hover event can fire many times, prevents from too many clicks.
        if (!$ajax.attr('ultiajaxHit')) {
          $ajax.click();

          $ajax.attr('data-ultiajax-hit', 1);
          $ajax.addClass('visually-hidden');
        }

        // This is the last resort while the user is hovering over menu link.
        // If the AJAX link is still there, an error likely stops it, or
        // the AJAX is taking longer time than 1.5 seconds. In such a case,
        // _waitingTimer will re-fire the click event, yet on interval now.
        // At any rate, Drupal.Ajax.ajaxing manages the AJAX requests.
        window.clearTimeout(_waitingTimer);
        _waitingTimer = window.setTimeout(function () {
          $ajax = $li.find('.ultimenu__ajax');
          if ($ajax.length) {
            $ajax.click();
            $ajax.removeClass('visually-hidden');
          }
          else {
            cleanUp();
          }
        }, 1500);

        e.preventDefault();
      }
      else {
        cleanUp();
      }
    },

    triggerClickHamburger: function (e) {
      if (e.target === this) {
        $hamburger.trigger('click');
      }
    },

    doClickHamburger: function (e) {
      e.preventDefault();

      var $button = $(this);
      var $offCanvas = $('.is-ultimenu-canvas-off');

      $body.toggleClass(_isExpanded);
      $button.toggleClass('is-ultimenu-button-active');

      Drupal.ultimenu.closeFlyout();

      // Cannot use transitionend as can be jumpy affected by child transitions.
      if (!$body.hasClass(_isExpanded) && $offCanvas.length) {
        window.clearTimeout(_hidingTimer);
        $body.addClass(_isHiding);
        _hidingTimer = window.setTimeout(function () {
          $body.removeClass(_isHiding);
        }, 600);
      }
    },

    closeFlyout: function () {
      $('.is-ultimenu-item-expanded', _ultimenuMain).removeClass('is-ultimenu-item-expanded');
      $(_ultimenuMain + ' .ultimenu__link').removeClass('is-ultimenu-active');
      $('.ultimenu__flyout:visible').slideUp();
    },

    doClickCaret: function (e) {
      e.preventDefault();

      var $caret = $(this);
      var hoverable = $('[data-ultimenu-button]').is(':hidden');

      // If hoverable for desktop, one at a time click should hide flyouts.
      // We let regular mobile toggle not affected, to avoid jumping accordion.
      if (hoverable) {
        Drupal.ultimenu.closeFlyout();
      }

      // Toggle the current flyout.
      $caret.closest('li').toggleClass('is-ultimenu-item-expanded');
      $caret.parent().toggleClass('is-ultimenu-active');
      $caret.parent().next('.ultimenu__flyout')
        .not(':animated').slideToggle();
    },

    onResize: function (c, t) {
      window.onresize = function () {
        window.clearTimeout(t);
        t = window.setTimeout(c, 200);
      };
      return c;
    }

  };

  /**
   * Ultimenu utility functions for the main menu only.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The ultimenu HTML element.
   */
  function doUltimenu(i, elm) {
    var me = Drupal.ultimenu;
    var $elm = $(elm);
    var settings = drupalSettings.ultimenu || {};
    var $backdrop = $('.is-ultimenu-canvas-backdrop');
    var $offCanvas = $('.is-ultimenu-canvas-off');

    // Applies to the main Ultimenu.
    if ($elm.data('ultimenu') === 'main') {
      // Allowes hard-coded CSS classes to not use this.
      if (settings && (settings.canvasOff && settings.canvasOn)) {
        if (!$offCanvas.length) {
          $(settings.canvasOff).addClass('is-ultimenu-canvas-off');
        }

        $(settings.canvasOn).addClass('is-ultimenu-canvas-on');
      }

      // Prepends our backdrop before the main off-canvas element.
      $offCanvas = $('.is-ultimenu-canvas-off');
      if (!$backdrop.length && $offCanvas.length) {
        $offCanvas.before('<div class="is-ultimenu-canvas-backdrop" />');
      }
    }

    // Applies to other Ultimenus.
    $('.caret', elm).once('ultimenu-caret').click(me.doClickCaret);
    $body.off().on('click.ultimenuBackdrop', '.is-ultimenu-canvas-backdrop', me.triggerClickHamburger);
  }

  /**
   * Attaches Ultimenu behavior to HTML element [data-ultimenu].
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ultimenu = {
    attach: function (context) {
      var me = Drupal.ultimenu;

      // Modifies functionality for the Ultimenu menus.
      $('[data-ultimenu]', context).once('ultimenu').each(doUltimenu);

      // Reacts on clicking Ultimenu hamburger button.
      $hamburger.once('ultimenu-button').click(me.doClickHamburger);

      // Reacts on resizing Ultimenu.
      me.onResize(me.doResizeMain.bind(me))();
    }
  };

})(jQuery, Drupal, drupalSettings, this, this.document);
