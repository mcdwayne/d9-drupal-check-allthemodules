(function ($) {
  Drupal.behaviors.logmanSearch = {
    attach: function (context, settings) {
      // Adjust the value of time field in date_to and date_from fields
      // in the watchdog and apache log search.
      // Set the from time value to 00:00:00 in nothing is entered.
      // Set the to  time value to 23:59:59 in nothing is entered.
      $('input[name="date_from[date]"]').change(function () {
        if ($('input[name="date_from[time]"]').val() == '') {
          $('input[name="date_from[time]"]').val('00:00:00');
        }
      });
      $('input[name="date_to[date]"]').change(function () {
        if ($('input[name="date_to[time]"]').val() == '') {
          $('input[name="date_to[time]"]').val('23:59:59');
        }
      });
    }
  };
}(jQuery));
