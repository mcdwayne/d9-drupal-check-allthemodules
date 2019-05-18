/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.bootstrap_datetime = {
    attach: function (context, settings) {

      // Setting the current language for the calendar.
      var language = drupalSettings.path.currentLanguage;

      $(context).find('input[data-bootstrap-date-time]').once('datePicker').each(function () {
        var input = $(this);

        // Get widget Type.
        var widgetType = input.data('bootstrapDateTime');

        // Get hour format - 12 or 24.
        var hourFormat = input.data('hourFormat');
        var timeFormat = (hourFormat === '12h') ? 'YYYY-MM-DD hh:mm' : 'YYYY-MM-DD  HH:mm';

        // Get excluded dates.
        var excludeDates = '';
        if (typeof input.data('excludeDate') != 'undefined') {
          excludeDates = input.data('excludeDate').split(',');
        }

        // Get disabled days.
        var disabledDays = input.data('disableDays');

        // Get minute granularity
        var allowedTimes = input.data('allowTimes');

        // If field widget is Date Time.
        if (widgetType === 'datetime') {
          $("#" + input.attr('id')).datetimepicker({
            useCurrent: false,
            format:timeFormat,
            daysOfWeekDisabled:disabledDays,
            disabledDates:excludeDates,
            showTodayButton:true,
            locale:language,
            showClose: true,
            calendarWeeks:true,
            stepping:allowedTimes,
          });
        }

        // If field widget is Date only.
        else {
          $("#" + input.attr('id')).datetimepicker({
            useCurrent: false,
            format:'YYYY-MM-DD',
            daysOfWeekDisabled:disabledDays,
            disabledDates:excludeDates,
            showTodayButton:true,
            locale:language,
            showClose: true,
            calendarWeeks:true,
          });
        }
      });
    },
  };

})(jQuery, Drupal, drupalSettings);
