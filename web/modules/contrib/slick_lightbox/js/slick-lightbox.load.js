/**
 * @file
 * Provides SlickLightbox loader.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * SlickLightbox utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The SlickLightbox gallery HTML element.
   */
  function doSlickLightbox(i, elm) {
    var boxSettings = drupalSettings.slickLightbox.lightbox || {};
    var slickSettings = drupalSettings.slickLightbox.slick || {};
    var itemSelector = '[data-slick-lightbox-trigger], .slick-lightbox-trigger';
    var $triggers = $(itemSelector, elm);

    // @todo remove when the library provides index argument to its initSlick().
    $(elm).on('click.slbox', itemSelector, function () {
      slickSettings.initialSlide = $(this).data('delta');
    });

    // Initializes slick with video supports.
    function initSlick(modalElement) {
      var media;
      var $box;
      var $media;
      var $player;
      var $slide;
      var $slick = $('.slick-lightbox-slick', modalElement);
      var $slides = $slick.children();

      /**
       * Trigger the media close.
       */
      function closeOut() {
        // Clean up any pause marker at slider container.
        $slick.removeClass('is-paused');

        if ($slick.find('.is-playing').length) {
          $slick.find('.is-playing').removeClass('is-playing').find('.media__icon--close').click();
        }
      }

      /**
       * Trigger pause on slick instance when playing a video.
       */
      function pause() {
        $slick.addClass('is-paused').slick('slickPause');
      }

      /**
       * Build out the media player.
       *
       * @param {int} i
       *   The index of the current element.
       * @param {HTMLElement} box
       *   The gallery item HTML element which triggers the lightbox.
       */
      function buildOutMedia(i, box) {
        $box = $(box);
        $slide = $($slides[i]);
        $player = $('.media--player', $slide);
        media = $box.data('media');

        if (media) {
          $slide.addClass('slick-slide--' + media.type);

          if ($box.data('boxUrl') && !$player.length) {
            // @todo replace when Blazy branches have it.
            // @todo $media = Drupal.theme('blazyMedia', {el: box});
            $media = Drupal.theme('slickLightboxMedia', {el: $box});
            $slide.find('.slick-lightbox-slick-img').replaceWith($media);

            Drupal.attachBehaviors($player[0]);
          }
        }
      }

      // Initializes slick.
      if (!$slick.hasClass('slick-initialized')) {
        $($triggers).each(buildOutMedia);

        $slick.slick(slickSettings);

        $slick.on('afterChange.slbox', closeOut);
        $slick.on('click.slbox', '.media__icon--close', closeOut);
        $slick.on('click.slbox', '.media__icon--play', pause);
      }
    }

    var options = {
      // Prevents clicking a video player button from closing the lightbox.
      // @todo re-enable when the library provides a fix for this.
      closeOnBackdropClick: false,
      itemSelector: itemSelector,
      caption: function (target, info) {
        var $caption = $(target).next('.litebox-caption');
        return $caption.length ? $caption.html() : '';
      },
      src: function (target) {
        var $target = $(target);
        return $target.data('boxUrl') || $target.attr('href');
      },
      slick: initSlick
    };

    var events = {
      'show.slickLightbox': function () {
        // Prevents media player with aspect ratio from being squeezed.
        $('.slick-slide--video .slick-lightbox-slick-item-inner').removeAttr('style');

        // Overrides closeOnBackdropClick as otherwise clicking video play
        // button closes the entire lightbox.
        // @todo remove when the library fixes this.
        $(elm.slickLightbox.$modalElement).on('click.slbox', '.slick-lightbox-slick-item', function (e) {
          if (e.target === this) {
            $('.slick-lightbox-close').click();
          }
        });
      }
    };

    // Initializes slick lightbox.
    var boxOptions = boxSettings ? $.extend({}, options, boxSettings) : options;
    $(elm).slickLightbox(boxOptions).on(events);
    $(elm).addClass('slick-lightbox-gallery--on');
  }

  /**
   * Theme function for a lightbox video.
   *
   * @param {Object} settings
   *   An object containing the link element which triggers the lightbox.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   *
   * @todo replace with Drupal.theme.blazyMedia() when Blazy branches have it.
   */
  Drupal.theme.slickLightboxMedia = function (settings) {
    var $elm = settings.el;
    var media = $elm.data('media');
    var alt = $('img', $elm).length ? $('img', $elm).attr('alt') : '';
    var pad = Math.round(((media.height / media.width) * 100), 2);
    var boxUrl = $elm.data('boxUrl');
    var embedUrl = $elm.attr('href');
    var html;

    html = '<div class="media-wrapper media-wrapper--inline" style="width:' + media.width + 'px">';
    html += '<div class="media media--switch media--player media--ratio media--ratio--fluid" style="padding-bottom: ' + pad + '%">';
    html += '<img src="' + boxUrl + '" class="media__image media__element" alt="' + Drupal.t(alt) + '"/>';
    html += '<span class="media__icon media__icon--close"></span>';
    html += '<span class="media__icon media__icon--play" data-url="' + embedUrl + '" data-autoplay="' + embedUrl + '"></span>';
    html += '</div></div>';

    return html;
  };

  /**
   * Attaches Slick Lightbox gallery behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickLightbox = {
    attach: function (context) {
      $('[data-slick-lightbox-gallery], .slick-lightbox-gallery:not(.slick-lightbox-gallery [data-slick-lightbox-gallery])', context).once('slick-lightbox-gallery').each(doSlickLightbox);
    }
  };

})(jQuery, Drupal, drupalSettings);
