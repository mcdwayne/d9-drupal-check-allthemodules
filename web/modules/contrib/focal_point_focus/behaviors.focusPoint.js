(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.focusPoint = {
    attach: function (context, settings) {

      $('.focuspoint', context).once('focuspointfocus').each(function () {
        $(this).focusPoint({
          throttleDuration: 100
        }).adjustFocus();
      });

    }
  };
})(jQuery, Drupal);
