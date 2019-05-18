/**
 * @file
 * Dropbutton feature.
 */
(function ($, Drupal) {

  'use strict';

  $('#edit-preview')
    .hide();
  $('#edit-clipboard-list')
    .on('change', function () {
      // $('#edit-preview').hide();
      var selected_val = $('option:selected', this)
        .text();
      if (selected_val === '- None -') {
        $('#edit-code')
          .val('');
      } else {
        $('#edit-code')
          .val(selected_val);
      }
    });

  $('#edit-clipboard-list option')
    .hover(function (e) {
      var $target = $(e.target);
      if ($target.is('option')) {
        var selected_val = $target.text();
        if (selected_val !== '- None -') {
          $('#edit-preview')
            .show();
          $('#edit-preview')
            .text(selected_val);
        }
      }
    });

  $('#edit-clipboard-list')
    .focusout(function () {
      $('#edit-preview')
        .hide();
    });

})(jQuery, Drupal);
