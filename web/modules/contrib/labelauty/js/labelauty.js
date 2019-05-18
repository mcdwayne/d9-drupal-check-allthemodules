/**
 * @file
 * Javascript file for the Labelauty module.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.labelauty = {
    attach: function (context) {
      $('.labelauty-widget:not(.labelauty, .hidden, :hidden)', context).each(function () {
        $(this).siblings('label').addClass('labelauty-label');
        $(this).labelauty({
          class: 'labelauty',
          same_width: true
        });
      });

      $('.labelauty-form .labelauty-element-hide:not(.labelauty, .hidden, :hidden)', context).each(function () {
        $(this).siblings('label').addClass('labelauty-label');
        $(this).labelauty({
          class: 'labelauty',
          same_width: true,
          label: false
        });
      });

      $('.labelauty-form .labelauty-element:not(.labelauty, .hidden, :hidden)', context).each(function () {
        $(this).siblings('label').addClass('labelauty-label');
        $(this).labelauty({
          class: 'labelauty',
          same_width: true
        });
      });
    }
  };
}(jQuery));
