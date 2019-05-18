/**
 * @file
 * Provides ElevateZoomPlus loader.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * ElevateZoomPlus public methods.
   *
   * @namespace
   */
  Drupal.elevateZoomPlus = Drupal.elevateZoomPlus || {
    itemSelector: '.elevatezoomplus',
    defaults: drupalSettings.elevateZoomPlus || {}
  };

  /**
   * ElevateZoomPlus gallery item functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The .elevatezoomplus-wrapper HTML element.
   */
  function doZoomWrapper(i, elm) {
    var me = Drupal.elevateZoomPlus;
    var options = me.defaults;
    var $elm = $(elm);
    var $target = $(me.itemSelector, elm);
    var $slick = $('.slick--main', elm);
    var $slider = $('> .slick__slider', $slick);
    var dataConfig = $elm.data('elevatezoomplus') || {};
    var hasMedia = $('.media--video', elm).length;
    var initialZoom = $slick.data('initialZoom') || 0;

    // Bail out if we have no config available.
    if (!dataConfig) {
      return;
    }

    // Attach our behaviors.
    options = $.extend({}, options, dataConfig);

    if (hasMedia && Drupal.blazyBox) {
      Drupal.blazyBox.attach();
    }

    // Integrates with Slick with asNavFor.
    if ($slider.length) {
      // Cannot hook into Slick init, due to re-positioning.
      $target = $('.slide--' + initialZoom + ' ' + me.itemSelector, $slider);
      $target.ezPlus(options);

      $slider.on('afterChange.ez', function () {
        var $current = $('.slick-current ' + me.itemSelector, $slider);
        $current.ezPlus(options);
      });
    }
    else {
      // Integrates with Slick without asNavFor, Blazy Grid, Gridstack, etc.
      $target = $(me.itemSelector, elm);
      $target.ezPlus(options);
    }

    /**
     * Triggers video zoom on a click event.
     */
    function triggerZoom() {
      var $item = $(this);
      var media = $item.data('media') || false;
      var video = media && media.type === 'video';

      // Build own video fullscreen as ElevateZoomPlus is not for video.
      if (video && Drupal.blazyBox) {
        Drupal.blazyBox.open($item.parent().attr('href'));
      }
    }

    $('.media--switch--elevatezoomplus', elm).on('click.ez', triggerZoom);
    $elm.addClass('elevatezoomplus-wrapper--on');
  }

  /**
   * Attaches ElevateZoomPlus behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.elevateZoomPlus = {
    attach: function (context) {
      $('.elevatezoomplus-wrapper:not(.elevatezoomplus-wrapper--on)', context).once('elevatezoomplus-wrapper').each(doZoomWrapper);
    }
  };

}(jQuery, Drupal, drupalSettings));
