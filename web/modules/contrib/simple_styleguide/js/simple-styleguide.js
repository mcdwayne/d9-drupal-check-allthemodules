/**
 * @file
 * Primary application javascript.
 *
 */

(function ($) {
  'use strict';
  Drupal.behaviors.simple_styleguide = {
    attach: function (context, settings) {
      jQuery('.simple-styleguide--view-sourecode').click(function () {
        jQuery(this).next('pre').toggle();
      });

      jQuery('.calculate').each(function () {
        var line_height = '<label>line-height:</label> ' + jQuery(this).find('.measure').css('line-height');
        var font_size = '<label>font-size:</label> ' + jQuery(this).find('.measure').css('font-size');
        var margin_bottom = jQuery(this).find('.measure').css('margin-bottom');
        var margin_top = jQuery(this).find('.measure').css('margin-top');
        var margin_right = jQuery(this).find('.measure').css('margin-right');
        var margin_left = jQuery(this).find('.measure').css('margin-left');
        var margin = '<label>margin:</label> ' + margin_top + ' ' + margin_right + ' ' + margin_bottom + ' ' + margin_left;

        var output = font_size + '<br/>' + line_height + '<br/>' + margin;

        jQuery(this).find('.info').html(output);
      });
    }
  };
})(jQuery);
