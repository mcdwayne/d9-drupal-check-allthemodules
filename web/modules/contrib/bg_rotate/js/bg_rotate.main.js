/**
 * @file
 * Background rotate javascript.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.bg_rotate_admin = {

  };

  Drupal.behaviors.bg_rotate = {
    // Emulate PHP's srand function with date.
    dateKey: function (interval, max) {
      // Copy and extend Date prototype to enable week number.
      var nDate = Date;
      // Calculate week number.
      nDate.prototype.getWeek = function () {
        var oneJan = new nDate(this.getFullYear(), 0, 1);
        var week = Math.ceil((((this - oneJan) / 86400000) + oneJan.getDay() + 1) / 7);
        return week;
      };
      var date = new nDate();
      // Populate array with all interval values.
      var values = [
        date.getFullYear(),
        date.getMonth(),
        date.getWeek(),
        date.getDate(),
        date.getHours(),
        date.getMinutes(),
        date.getSeconds()
      ];
      // Slice array at proper place to get interval value.
      switch (interval) {
        case 'month': values = values.slice(0, 2);
          break;

        case 'week': values = values.slice(0, 3);
          break;

        case 'day': values = values.slice(0, 4);
          break;

        case 'hour': values = values.slice(0, 5);
          break;

        case 'minute': values = values.slice(0, 6);
          break;
      }
      // Concatinate values and get modulus of max value.
      var value = values.join('');
      value %= max;
      return parseInt(value) || 0;
    },
    randImage: function (urls, interval) {
      // Get array length.
      var arrayCount = urls.length;
      // Get array key based on interval.
      var arrayKey = this.dateKey(interval, arrayCount);
      // Fetch image url object from settings array.
      var images = urls[arrayKey];
      // Our breakpoints.
      var widths = Object.keys(images);
      // Get page width.
      var goal = window.innerWidth;
      // Get closest breakpoint to width.
      var closest = widths.reduce(function (prev, curr) {
        return (Math.abs(curr - goal) < Math.abs(prev - goal) ? curr : prev);
      });
      // Return closest random image size.
      return images[closest];
    },
    attach: function (context, settings) {
      var self = this;
      // Check if image rotate settings is present or abort.
      if (typeof settings.bg_rotate === 'undefined') {
        return;
      }
      // Get our configuration.
      var conf = settings.bg_rotate;
      // Get image based on window width and image styles.
      var image = self.randImage(conf.urls, conf.interval);
      // Check if there is an image.
      if (typeof image !== 'undefined') {
        // CSS properties.
        var css = {
          'background-image': 'url(' + image + ')',
          'background-repeat': conf.css.repeat,
          'background-position': conf.css.position,
          'background-attachment': conf.css.attachment,
          'background-size': conf.css.size
        };

        // Attach random image.
        $(conf.selector)
          .css(css)
          .addClass('bg_rotate-element');
      }
    }
  };
})(jQuery);
