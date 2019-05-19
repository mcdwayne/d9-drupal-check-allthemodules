(function ($, Drupal) {

  'use strict';

  /**
   * Attach calendars picker on Taarikh date elements
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   */
  Drupal.behaviors.taarikh = {
    attach: function (context, settings) {
      var $context = $(context);

      $context.find('input[data-taarikh-date-format]').once('taarikhPicker').each(function () {
        var $input = $(this);
        var calendarPickerSettings = {
          calendar: $.calendars.instance('fatimid_astronomical'),
          firstDay: $input.data('taarikhFirstDay'),
          showSpeed: 'fast'
        };

        // The date format is saved in PHP style, we need to convert to jQuery
        // datepicker.
        var dateFormat = $input.data('taarikhDateFormat');
        calendarPickerSettings.dateFormat = dateFormat
          .replace('y', 'yy')
          .replace('Y', 'yyyy')
          .replace('m', 'mm')
          .replace('d', 'dd')
          .replace('j', 'd')
          .replace('l', 'DD')
          .replace('n', 'm')
          .replace('F', 'MM');

        $input.calendarsPicker(calendarPickerSettings);
      });
    }
  };

})(jQuery, Drupal);
