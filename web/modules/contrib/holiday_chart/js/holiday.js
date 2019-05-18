(function ($) {
  'use strict';
  Drupal.behaviors.holiday_chart = {
    attach: function (context, settings) {
      $(document).ready(function () {
        // holiday date click
        $('.single-box', context).once('holiday_chart').on('click', function () {
          var date_val = $(this).attr('id');
          var date_status = $(this).find('.status').html();
          var month = $(this).attr('data-month');
          var year = $(this).attr('data-year');

          $.ajax({
            type: "POST",
            url: drupalSettings.path.baseUrl + "holiday-update-date/" + date_val + '/' + date_status + '/' + month + '/' + year,
            cache: false,
            data: {},
            success: function (response) {
              console.log(response);
              if (response == 'W') {
                $('#' + date_val).removeClass('working').addClass('holiday');
                $('#' + date_val).find('.status').html('H');
              } else if (response == 'H') {
                $('#' + date_val).removeClass('holiday').addClass('working');
                $('#' + date_val).find('.status').html('W');
              }
            }
          });
        });
      });
    }
  };
}(jQuery));