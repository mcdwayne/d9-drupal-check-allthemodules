/**
 * @file reference_swiper.field.js
 *
 * Contains the behavior, that initializes Swiper on fields using the reference
 * swiper field formatter.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.referenceSwiper = Drupal.referenceSwiper || {

      /**
       * Holds all Swiper instances on a page.
       */
      swiperInstances: {},

      /**
       * Provides function parameters for callable parameters.
       */
      callableParameters: {
        'onInit': ['swiper'],
        'onSlideChangeStart': ['swiper'],
        'onSlideChangeEnd': ['swiper'],
        'onSlideNextStart': ['swiper'],
        'onSlideNextEnd': ['swiper'],
        'onSlidePrevStart': ['swiper'],
        'onSlidePrevEnd': ['swiper'],
        'onTransitionStart': ['swiper'],
        'onTransitionEnd': ['swiper'],
        'onTouchStart': ['swiper', 'event'],
        'onTouchMove': ['swiper', 'event'],
        'onTouchMoveOpposite': ['swiper', 'event'],
        'onSliderMove': ['swiper', 'event'],
        'onTouchEnd': ['swiper', 'event'],
        'onClick': ['swiper', 'event'],
        'onTap': ['swiper', 'event'],
        'onDoubleTap': ['swiper', 'event'],
        'onImagesReady': ['swiper'],
        'onProgress': ['swiper', 'progress'],
        'onReachBeginning': ['swiper'],
        'onReachEnd': ['swiper'],
        'onDestroy': ['swiper'],
        'onSetTranslate': ['swiper', 'translate'],
        'onSetTransition': ['swiper', 'transition'],
        'onAutoplay': ['swiper'],
        'onAutoplayStart': ['swiper'],
        'onAutoplayStop': ['swiper'],
        // 'onLazyImageLoad': ['swiper', 'slide', 'image'],
        // 'onLazyImageReady': ['swiper', 'slide', 'image'],
        'onPaginationRendered': ['swiper', 'paginationContainer'],
        'paginationBulletRender': ['swiper', 'index', 'className'],
        'paginationFractionRender': ['swiper', 'currentClassName', 'totalClassName'],
        'paginationProgressRender': ['swiper', 'progressbarClass'],
        'paginationCustomRender': ['swiper', 'current', 'total']
      },

      /**
       * Array that holds the parameter keys that need to be parsed as JSON.
       */
      jsonParameters: [
        'breakpoints',
        'fade',
        'cube',
        'coverflow',
        'flip'
      ],

      /**
       * Processes the parameters coming as JSON string with JS commands.
       *
       * JSON gets parsed and JS statements get wrapped in an anonymous function.
       * This is necessary to enable entering of those parameters in textfields
       * on the option set UI.
       *
       * @param parameterSetKey
       *   Key for a parameter option set coming from the server side.
       */
      prepareParameters: function (parameterSetKey) {
        // Parse parameters in JSON format in case there are any.
        Drupal.referenceSwiper.jsonParameters.forEach(function (element) {
          if (drupalSettings.referenceSwiper.parameters[parameterSetKey][element] !== undefined) {
            drupalSettings.referenceSwiper.parameters[parameterSetKey][element] = JSON.parse(
              drupalSettings.referenceSwiper.parameters[parameterSetKey][element]
            );
          }
        });

        // Wrap configured callback and function statements in an anonymous function.
        $.each(Drupal.referenceSwiper.callableParameters, function (index, value) {
          if (drupalSettings.referenceSwiper.parameters[parameterSetKey][index] !== undefined) {
            var constructorArgs = value;
            constructorArgs.push(drupalSettings.referenceSwiper.parameters[parameterSetKey][index]);
            drupalSettings.referenceSwiper.parameters[parameterSetKey][index] = Function.apply(
              null,
              constructorArgs
            );
          }
        });
      }

    };

  /**
   * Register behavior that initializes Swiper instances.
   *
   * The created instances are stored in Drupal.referenceSwiper.swiperInstances
   * and may be accessed by any other module's library by depending on the
   * reference_swiper/reference_swiper.field library.
   */
  Drupal.behaviors.referenceSwiperField = {
    attach: function (context) {
      $('.swiper-container', context).once('reference-swiper').each(function () {
        var parameterSetKey = $(this).data('swiper-param-key');

        // Callbacks, functions and JSON parameters need some preparation.
        Drupal.referenceSwiper.prepareParameters(parameterSetKey);

        // Initiate the swiper instance and store it in the global object. That
        // way other modules may access instances and work with them.
        Drupal.referenceSwiper.swiperInstances[parameterSetKey] = new Swiper(
          $(this)[0],
          drupalSettings.referenceSwiper.parameters[parameterSetKey]
        );
      });
    }
  };

}(jQuery, Drupal, drupalSettings));
