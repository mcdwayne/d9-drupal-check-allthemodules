/**
 * @file
 * Callback for Radios to Select.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.radiosToSelect = {
    attach: function (context) {
      $('.form-radios-to-slider', context).each(function () {
        $(this).radiosToSlider({
          animation: $(this).data('animation'),
          fitContainer: $(this).data('fit-container')
        });
      });
    }
  };

}(jQuery));
