/**
 * @file
 * Provides Scroll Depth Analytics.
 *
 * Analytics tracking on page scrolling.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.scroll_depth_analytics = {
    attach: function (context, drupalSettings) { 
      if ($.scrollDepth) {
        var elements_to_track = [];
		console.log(drupalSettings.scroll_depth_analytics.scroll_elements);
		var lines = drupalSettings.scroll_depth_analytics.scroll_elements.split(/\n/);
        for (var i=0; i < lines.length; i++) {
        // only push this line if it contains a non whitespace character.
        if (/\S/.test(lines[i])) {
          elements_to_track.push($.trim(lines[i]));
        }
      }
		jQuery.scrollDepth({
		  minHeight: 200,
          elements: elements_to_track,
          percentage: true,
          eventHandler: function(data) {
			console.log(data);
            ga('send', 'event', data.eventCategory, data.eventAction, data.eventLabel, data.eventValue);
          }
        });

       
      }
    }
  };
})(jQuery);
