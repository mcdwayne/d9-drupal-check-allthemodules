/**
 * @file
 * Monitoring sensor overview behaviors.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Filters the monitoring sensor overview table by input search filters.
   *
   * Text search input:   input.table-filter-text
   * Select sensor type:  select.table-filter-select-sensor-type
   * Select category:     select.table-filter-select-category
   * Target table:        input.table-filter-text[data-table]
   * Source text:         .table-filter-text-source, .table-filter-category, .table-filter-sensor-type
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.tableFilterByText = {
    attach: function (context, settings) {
      var $input = $('input.table-filter-text').once('table-filter-text');
      var $select_category = $('select.table-filter-select-category').once('table-filter-select-category');
      var $select_sensor_type = $('select.table-filter-select-sensor-type').once('table-filter-select-sensor-type');
      var $table = $($input.attr('data-table'));
      var $rows;
      var $details;

      function filterSensorList(e) {
        var category_value = $select_category.val().toLowerCase();
        var sensor_type_value = $select_sensor_type.val().toLowerCase();
        var input_value = $input.val().toLowerCase();

        function showSensorRow(index, row) {
          var $row = $(row);
          var $category_sources = $row.find('.table-filter-category');
          var $sensor_type_sources = $row.find('.table-filter-sensor-type');
          var $input_value_sources = $row.find('.table-filter-text-source');

          if (category_value && $category_sources.text().toLowerCase() !== category_value) {
            $row.hide();
            return;
          }
          if (sensor_type_value && $sensor_type_sources.text().toLowerCase() !== sensor_type_value) {
            $row.hide();
            return;
          }
          if (input_value && $input_value_sources.text().toLowerCase().indexOf(input_value) == -1) {
            $row.hide();
            return;
          }
          $row.show();
        }

        // Filter if the length of the input field is longer than 2 characters
        // or an option is selected.
        if (category_value || sensor_type_value || input_value.length >= 2) {
          $rows.each(showSensorRow);
        }
        else {
          $rows.show();
          $details.attr('open', false);
        }
      }

      if ($table.length) {
        $rows = $table.find('tbody tr');
        $input.on('keyup', filterSensorList);
        $select_category.on('change', filterSensorList);
        $select_sensor_type.on('change', filterSensorList);
        $details = $table.find('details');
      }
    }
  };

}(jQuery, Drupal));
