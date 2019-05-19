/**
 * @file
 * Apply the Trunk8 jQuery plugin.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.TrunkeightFormatter = {
    attach: function (context, settings) {
      $.each($(context).find('[data-truncate="true"]'), function () {
        var $el = $(this);
        $el.trunk8({
          fill: $el.attr('data-truncate-fill'),
          lines: $el.attr('data-truncate-lines')
        });
      });
    }
  };
})(jQuery, Drupal);
