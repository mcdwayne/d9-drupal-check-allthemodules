/**
 * @file calendar_systems_bef.js
 *
 * Provides jQueryUI Datepicker integration with Better Exposed Filters.
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.calendarSystemsBef = {
    attach: function(context, settings) {
      var $context = $(context);
      // Check for and initialize datepickers
      var befSettings = drupalSettings.better_exposed_filters;
      if (befSettings && befSettings.datepicker && befSettings.datepicker_options) {
        $context.find('.bef-datepicker').once('calendar-systems-picker').each(function () {
          var sett = {
            autoClose: true,
            format: 'Y-m-d'.replace('Y', 'YYYY').replace('m', 'MM').replace('d', 'DD'),
            position: "auto",
            onlySelectOnDate: true,
            calendarType: "persian",
            calendar: {
              persian: {
                locale: "fa"
              }
            },
            timePicker: {
              enabled: false
            },
            initialValueType: 'persian',
            initialValue: false,
            calendarType: 'persian',
          };
          var pd = $(this).persianDatepicker(sett);
        });
      }

    }
  };
}) (jQuery, Drupal, drupalSettings);

