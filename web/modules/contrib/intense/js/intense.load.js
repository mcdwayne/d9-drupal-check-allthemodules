/**
 * @file
 * Provides Intense loader.
 *
 * Intense video full screen requires Blazy 2.x.
 */

(function ($, Drupal, _db, window) {

  'use strict';

  /**
   * Intense utility functions.
   */
  Drupal.intense = Drupal.intense || {
    $box: null,
    $gallery: null,
    $prev: null,
    $next: null,
    $arrow: null,
    $triggers: [],
    clickedIndex: 0,
    currentIndex: 0,
    totalCount: 0,
    intenseTimer: null,

    buildOut: function () {
      var me = this;

      if (me.$box !== null) {
        $('body').addClass('is-intensed');
        me.$box.addClass('intensed');

        if (!$('.intensed__arrow', me.$box).length) {
          me.$box.append(Drupal.theme('intense'));
        }

        me.$arrow = $('.intensed__arrow', me.$box);
        me.$prev = $('.intensed__prev', me.$box);
        me.$next = $('.intensed__next', me.$box);

        me.$box.on('click.ib', '.intensed__prev', me.goPrev);
        me.$box.on('click.ib', '.intensed__next', me.goNext);

        me.$prev[me.currentIndex === 0 ? 'addClass' : 'removeClass']('is-hidden');
        me.$next[me.currentIndex === me.totalCount - 1 ? 'addClass' : 'removeClass']('is-hidden');

        me.$box.click(me.closeOut);
      }
    },

    /**
     * Toggles the box.
     *
     * @param {bool} visible
     *   True if should be visible, else false.
     */
    toggle: function (visible) {
      var me = Drupal.intense;

      $('body')[visible ? 'addClass' : 'removeClass']('is-intensed');

      // We have no events from the library, so have to use a delay.
      window.setTimeout(function () {
        me.$box = $('body > figure:last');

        if (visible) {
          me.buildOut();
        }
      }, 140);
    },

    navigate: function () {
      var me = Drupal.intense;
      $('img', me.$box).click();

      _db.trigger(me.$triggers[me.currentIndex], 'click');
      me.toggle(true);
    },

    goPrev: function () {
      var me = Drupal.intense;
      me.currentIndex--;

      me.navigate();
    },

    goNext: function () {
      var me = Drupal.intense;
      me.currentIndex++;

      me.navigate();
    },

    closeOut: function () {
      var me = Drupal.intense;
      me.toggle(false);
    },

    /**
     * Gets the current clicked item index.
     *
     * @param {HTMLElement} item
     *   The link item HTML element.
     *
     * @return {Int}
     *   The current clicked item index.
     */
    getIndex: function (item) {
      var me = this;
      var i = 0;
      $(me.$triggers).each(function (idx, elm) {
        if (elm === item) {
          i = idx;
          return false;
        }
      });
      return i;
    }
  };

  /**
   * Theme function for intense arrows.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.intense = function () {
    var html;

    html = '<div class="intensed__arrow">';
    html += '<button class="intensed__prev" data-role="none">' + Drupal.t('Previous') + '</button>';
    html += '<button class="intensed__next" data-role="none">' + Drupal.t('Next') + '</button>';
    html += '</div>';

    return html;
  };

  /**
   * Intense utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The Intense gallery HTML element.
   */
  function doIntense(i, elm) {
    var me = Drupal.intense;
    var $elm = $(elm);
    var hasMedia = $('.media--video', elm).length;

    me.$gallery = $elm;
    me.$triggers = elm.querySelectorAll('[data-intense-trigger], .intense');
    me.totalCount = me.$triggers.length;

    // Attaches Blazy video full screen.
    if (hasMedia && Drupal.blazyBox) {
      Drupal.blazyBox.attach();
    }

    // Intensify images.
    new Intense(me.$triggers);

    /**
     * Intense utility functions.
     *
     * @param {Event} e
     *   The event triggering the Intense.
     */
    function triggerIntense(e) {
      // The e.target points to an IMG, and e.currentTarget (jQuery) to A tag.
      var $link = $(this);
      var media = $link.data('media') || false;
      var video = media && media.type === 'video';

      // Build own video fullscreen as Intense is not for video.
      if (video && Drupal.blazyBox) {
        Drupal.blazyBox.open($link.attr('href'));
      }

      e.preventDefault();

      // We have no events from the library, so have to use a delay.
      window.clearTimeout(me.intenseTimer);
      me.intenseTimer = window.setTimeout(function () {
        me.clickedIndex = me.currentIndex = me.getIndex($link[0]);

        me.toggle(true);
      }, 100);
    }

    $elm.on('click.ib', '[data-intense-trigger], .intense', triggerIntense);
  }

  /**
   * Attaches Intense behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.intense = {
    attach: function (context) {
      // Supports module or custom gallery with a CSS class.
      $('[data-intense-gallery]:not(.intense-gallery [data-intense-gallery]), .intense-gallery', context).once('intense-gallery').each(doIntense);
    }
  };

})(jQuery, Drupal, dBlazy, this);
