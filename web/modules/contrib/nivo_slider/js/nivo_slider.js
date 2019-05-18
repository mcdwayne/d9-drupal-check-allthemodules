/**
 * @file
 * Attaches the behaviors for the Nivo Slider module.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.nivoSlider = {
    attach: function (context, settings) {
      // Initialize the slider
      $('#slider').nivoSlider({
        'effect': drupalSettings.nivo_slider.effect, // Specify sets like: 'fold,fade,sliceDown'
        'slices': drupalSettings.nivo_slider.slices, // For slice animations
        'boxCols': drupalSettings.nivo_slider.boxCols, // For box animations
        'boxRows': drupalSettings.nivo_slider.boxRows, // For box animations
        'animSpeed': drupalSettings.nivo_slider.animSpeed, // Slide transition speed
        'pauseTime': drupalSettings.nivo_slider.pauseTime, // How long each slide will show
        'startSlide': drupalSettings.nivo_slider.startSlide, // Set starting Slide (0 index)
        'directionNav': drupalSettings.nivo_slider.directionNav, // Next & Prev navigation
        'directionNavHide': drupalSettings.nivo_slider.directionNavHide, // Only show on hover
        'controlNav': drupalSettings.nivo_slider.controlNav, // 1,2,3... navigation
        'controlNavThumbs': drupalSettings.nivo_slider.controlNavThumbs, // Use thumbnails for Control Nav
        'pauseOnHover': drupalSettings.nivo_slider.pauseOnHover, // Stop animation while hovering
        'manualAdvance': drupalSettings.nivo_slider.manualAdvance, // Force manual transitions
        'prevText': drupalSettings.nivo_slider.prevText, // Prev directionNav text
        'nextText': drupalSettings.nivo_slider.nextText, // Next directionNav text
        'randomStart': drupalSettings.nivo_slider.randomStart, // Start on a random slide
        'beforeChange': drupalSettings.nivo_slider.beforeChange, // Triggers before a slide transition
        'afterChange': drupalSettings.nivo_slider.afterChange, // Triggers after a slide transition
        'slideshowEnd': drupalSettings.nivo_slider.slideshowEnd, // Triggers after all slides have been shown
        'lastSlide': drupalSettings.nivo_slider.lastSlide, // Triggers when last slide is shown
        'afterLoad': drupalSettings.nivo_slider.afterLoad // Triggers when slider has loaded
      });
    }
  };

}(jQuery));
