/**
 * @file
 * JS to init ReadRemaining with the proper settings.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.readremaining = {
    attach: function (context, settings) {

      var config = settings.readremaining;
      // Check if user filled out valid selector.
      if ($('html').find(config.selector).length) {
        $(config.selector).readRemaining({
          // Delay before showing the indicator.
          showGaugeDelay: config.show_gauge_delay,
          // Show the gauge initially, even before the user scroll.
          showGaugeOnStart: config.show_gauge_on_start,
          // Will replace %m and %s with minutes and seconds.
          timeFormat: config.time_format,
          // Only show time if is lower than x minutes (multiplied to seconds).
          maxTimeToShow: config.max_time_to_show,
          // Only show time if is higher than x seconds.
          minTimeToShow: config.min_time_to_show,
          // The element where the gauge will append to. If left to an empty
          // string, the container will be the same scrolling element.
          gaugeContainer: config.gauge_container,
          // 'append' or 'prepend' as required by style.
          insertPosition: config.insert_position,
          // Enable the console logs. For testing only.
          verboseMode: config.verbose_mode,
          // Optional, the element that define the visible scope for the gauge.
          // If left to an empty string, the gauge will be visible all along.
          gaugeWrapper: config.gauge_wrapper,
          // Distance between the top of the gaugeWrapper and the point where the
          // gauge will start to appear. Some designs require this.
          topOffset: config.top_offset,
          // Distance between bottom border where the box will appear and the
          // bottom of the element.
          bottomOffset: config.bottom_offset
        });
      }
    }
  };
})(jQuery, Drupal);
