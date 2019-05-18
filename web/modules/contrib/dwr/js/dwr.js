/**
 * @file
 * Attaches behaviors for the date_week_range module.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.dateWeekRangeDatePicker = {
    attach: function (context, settings) {
      // TODO:
      // Skip if week element is supported by the browser.
      // if (Modernizr.inputtypes.week === true) {
      //   return;
      // }

      var date;
      var parts;
      var startDate;
      var endDate;
      var fieldNameID;
      var startDateFieldName;
      var endDateFieldName;
      var statusDateFieldName;

      fieldNameID = '*[id^="' + settings.date_week_range.fieldName + '"]';
      startDateFieldName = '*[id^="' + settings.date_week_range.valueFieldName + '"]';
      endDateFieldName = '*[id^="' + settings.date_week_range.endValueFieldName + '"]';
      statusDateFieldName = '*[id^="' + settings.date_week_range.statusID + '"]';
      var $weekPicker = $(fieldNameID + ' .week-picker');

      var selectCurrentWeek = function () {
        window.setTimeout(function () {
          $weekPicker.find('.ui-datepicker-current-day a').addClass('ui-state-active');
        }, 1);
      };

      // Set initial values, when available
      startDate = new Date();
      date = $(startDateFieldName).val();
      // Default date is in Y-m-d format.
      if (date.match(/[0-9]{4}-[0-9]{2}-[0-9]{2}/)) {
        parts = date.split('-');
        startDate = new Date(parts[0], parts[1] - 1, parts[2]);
      }

      $weekPicker.datepicker({
        showOtherMonths: true,
        selectOtherMonths: true,
        closeText: Drupal.t('Done'),
        prevText: Drupal.t('Prev'),
        nextText: Drupal.t('Next'),
        currentText: Drupal.t('Today'),
        monthNames: [Drupal.t('January', {}, {context: 'Long month name'}), Drupal.t('February', {}, {context: 'Long month name'}), Drupal.t('March', {}, {context: 'Long month name'}), Drupal.t('April', {}, {context: 'Long month name'}), Drupal.t('May', {}, {context: 'Long month name'}), Drupal.t('June', {}, {context: 'Long month name'}), Drupal.t('July', {}, {context: 'Long month name'}), Drupal.t('August', {}, {context: 'Long month name'}), Drupal.t('September', {}, {context: 'Long month name'}), Drupal.t('October', {}, {context: 'Long month name'}), Drupal.t('November', {}, {context: 'Long month name'}), Drupal.t('December', {}, {context: 'Long month name'})],
        monthNamesShort: [Drupal.t('Jan'), Drupal.t('Feb'), Drupal.t('Mar'), Drupal.t('Apr'), Drupal.t('May'), Drupal.t('Jun'), Drupal.t('Jul'), Drupal.t('Aug'), Drupal.t('Sep'), Drupal.t('Oct'), Drupal.t('Nov'), Drupal.t('Dec')],
        dayNames: [Drupal.t('Sunday'), Drupal.t('Monday'), Drupal.t('Tuesday'), Drupal.t('Wednesday'), Drupal.t('Thursday'), Drupal.t('Friday'), Drupal.t('Saturday')],
        dayNamesShort: [Drupal.t('Sun'), Drupal.t('Mon'), Drupal.t('Tue'), Drupal.t('Wed'), Drupal.t('Thu'), Drupal.t('Fri'), Drupal.t('Sat')],
        dayNamesMin: [Drupal.t('Su'), Drupal.t('Mo'), Drupal.t('Tu'), Drupal.t('We'), Drupal.t('Th'), Drupal.t('Fr'), Drupal.t('Sa')],
        dateFormat: 'dd/mm/yy',
        firstDay: 1, // Start on Monday
        isRTL: 0,
        defaultDate: startDate,

        onSelect: function (dateText, inst) {
          var date = $weekPicker.datepicker('getDate');
          var firstDay = inst.settings.firstDay;
          startDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay() + firstDay);
          endDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay() + firstDay + 6);

          var dateFormat = inst.settings.dateFormat || $.datepicker._defaults.dateFormat;
          var startDateText = $.datepicker.formatDate(dateFormat, startDate, inst.settings);
          var endDateText = $.datepicker.formatDate(dateFormat, endDate, inst.settings);
          var weekText = $.datepicker.iso8601Week(startDate);

          $(statusDateFieldName).text(Drupal.t('Week @week: @start - @end', {'@week': weekText, '@start': startDateText, '@end': endDateText}));

          $(startDateFieldName).val($.datepicker.formatDate('yy-mm-dd', startDate, inst.settings));
          $(endDateFieldName).val($.datepicker.formatDate('yy-mm-dd', endDate, inst.settings));

          selectCurrentWeek();
        },

        beforeShowDay: function (date) {
          var cssClass = '';
          if (date >= startDate && date <= endDate) {
            cssClass = 'ui-datepicker-current-day';
          }
          return [true, cssClass];
        },

        onChangeMonthYear: function (year, month, inst) {
          selectCurrentWeek();
        }
      });

      // Initialize week select
      if (date.length > 0) {
        $weekPicker.find('.ui-datepicker-current-day').click();
      }

      // Mouse events
      var $datepicker = $weekPicker.find('.ui-datepicker');

      $datepicker.on('mousemove', 'tr', function () {
        $(this).find('td a').addClass('ui-state-hover');
      });
      $datepicker.on('mouseleave', 'tr', function () {
        $(this).find('td a').removeClass('ui-state-hover');
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
