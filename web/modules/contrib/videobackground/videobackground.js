/**
 * @file
 * Provides a video display inculded css selectors.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.videobackground = {
    attach: function (context, settings) {
      var include = settings.video_background.include;
      $(include, context).each(function (index, value) {
        // Get video path.
        var videPath = settings.video_background.path;
        // Set options.
        var options = settings.video_background.options;
        Drupal.theme('videoBackgroundTrigger', include, videPath, options);
      });
    }
  };

  Drupal.theme.videoBackgroundTrigger = function (targetSelectors, videPath, object) {
    var options = [];
    var bg = [];
    var arr = [];
    $.each(object, function (index, value) {
      options.push(index + ':' + value);
    });
    $.each(videPath, function (index, value) {
      if (typeof (value) !== 'undefined' && value) {
        arr = value.split('-');
        arr.splice(0, 1);
        arr = arr.join('-');
        bg.push(index + ':' + arr); 
      }
    });
    $(targetSelectors).addClass('videobackground-holder');
    return $(targetSelectors).attr({
      'data-vide-bg': bg,
      'data-vide-options': options
    });
  };

})(jQuery, Drupal);

