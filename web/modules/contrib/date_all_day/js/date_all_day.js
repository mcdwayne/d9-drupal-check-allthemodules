/**
 * @file
 * Contains date_all_day.js.
 */

(function ($, Drupal, window, document) {
  'use strict';

  // Datetime Range All Day.
  Drupal.behaviors.date_all_day = {
    attach: function (context, settings) {
      var start_field = $('[name$="[value_all_day]"]');
      var end_field = $('[name$="[end_value_all_day]"]');

      start_field.change(function () {
        changeCheckbox(this, 'start');
      });
      end_field.change(function () {
        changeCheckbox(this, 'end');
      });
      changeCheckbox(start_field.get(0), 'start');
      changeCheckbox(end_field.get(0), 'end');

      function changeCheckbox(item, type){
        var $this = $(item);
        var name_attr = $this.attr('name');

        if (type == 'start') {
          var key = '[value_all_day]';
          var time_key = '[value][time]';
          var time_value = '00:00:00';
        }
        else if (type == 'end') {
          var key = '[end_value_all_day]';
          var time_key = '[end_value][time]';
          var time_value = '23:59:59';
        }
        else {
          throw 'type parameter should have "start" or "end" values';
        }

        var time_name_attr = name_attr.replace(key, time_key);

        var time_field = $('[name="' + time_name_attr + '"]');
        if ($this.is(':checked')) {
          time_field.val(time_value);
          time_field.hide();
        }
        else {
          time_field.show();
        }
      }

    }
  };
})(jQuery, Drupal, this, this.document);
