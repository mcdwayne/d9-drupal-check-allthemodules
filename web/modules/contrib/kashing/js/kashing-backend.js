 (function ($, Drupal) {

  $(document).ready(function () {
    'use strict';
        // amount update
    $('#kashing-new-form-amount').focusout(function () {

      var value = $(this).val();
      value = value.replace(/[^\d,.]/g, ''); // Remove letters

      // so we can use commas too

      if (value.indexOf(',') !== -1) {
        if ((value.match(/,/g) || []).length < 2 && value.indexOf('.') === -1) {
          var pos = value.lastIndexOf(',');
          value = value.substring(0, pos) + '.' + value.substring(pos + 1);
        }
          value = parseFloat(value.replace(/,/g, ''));
      }

      var valueDots = value.split('.');
      valueDots = valueDots.filter(Number);

      if (valueDots.length > 1) {
        value = valueDots[0] + '.' + valueDots[1];
      }

      if (value !== '' && Number(value) === value) {
        value = Number(value);
        value = value.toFixed(2);
      }

      $(this).val(value);

    });


    // block id generation
    $('#kashing-new-form-title').on('input', function () {

      var value = $(this).val();

      value = value.toLowerCase().replace(/[^a-zA-Z0-9]+/g, '_');

      $('#kashing-new-form-id').val(value);

    });
  });

 })(jQuery, Drupal);
