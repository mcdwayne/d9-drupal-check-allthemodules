/**
 * @file
 * Callback for jqueryJonthorntonTimepicker.
 */


(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.jQCloud = {
    attach: function (context, settings) {
      if (typeof settings.jQCloud !== 'undefined') {

        for (var key in settings.jQCloud) {
          if ({}.hasOwnProperty.call(settings.jQCloud, key)) {

            var data = settings.jQCloud[key];
            var $el = $('.' + key + ' .jqcloud-contents');

            $el.css({height: data.height});
            var options = {};
            if (typeof data.colors !== 'undefined') {
              options['colors'] = data.colors;
            }

            // Other settings.
            options['autoResize'] = data.auto_resize;
            options['shape'] = data.shape;
            options['delay'] = data.delay;

            $el.jQCloud(data.terms, options);
          }
        }
      }

    }
  };

}(jQuery, Drupal));
