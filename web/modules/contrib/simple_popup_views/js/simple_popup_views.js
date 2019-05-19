(function ($, Drupal) {

  'use strict';

  $('.spv_on_hover')
    .hover(
      function () {
        $(this)
          .parent('.spv-popup-wrapper')
          .children('.spv-popup-content')
          .show();
      },
      function () {
        $(this)
          .parent('.spv-popup-wrapper')
          .children('.spv-popup-content')
          .hide();
      }
    );

  $('.spv_on_click')
    .click(function () {
      $(this)
        .parent('.spv-popup-wrapper')
        .children('.spv-popup-content')
        .show();
      return false;
    });

  $('.spv_close')
    .click(function () {
      $(this)
        .parent('.spv-popup-content')
        .hide();
    });
})(jQuery, Drupal);
