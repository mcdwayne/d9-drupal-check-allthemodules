/**
 * @file
 * Paragraphs Collection overview behaviors.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Filters the overview table by input search filters.
   *
   * Target table:        .table-filter[data-table]
   * Text search input:   input.table-filter-text
   * Group search select: select.table-filter-group-select
   * Source text:         .table-filter-text-source
   * Source group:        .table-filter-group-source
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.tableFilterByText = {
    attach: function (context, settings) {
      var $filters = $('.table-filter').once('table-filter');
      var $table = $($filters.attr('data-table'));
      var $text_input = $('input.table-filter-text').once('table-filter-text');
      var $group_select = $('select.table-filter-group-select').once('table-filter-group-select');
      var $rows;

      function filterItemList() {
        var group_value;
        if (typeof $group_select.val() !== 'undefined') {
          group_value = $group_select.val().toLowerCase();
        }
        var text_value = $text_input.val().toLowerCase();

        function showItemRow(index, row) {
          var $row = $(row);
          var $group_sources = $row.find('.table-filter-group-source');
          var $text_sources = $row.find('.table-filter-text-source');
          var group_array = $group_sources.map(function() {
            return $(this).text().toLowerCase();
          }).get();

          if (group_value && group_array.indexOf(group_value) == -1) {
            $row.hide();
            return;
          }
          if (text_value && $text_sources.text().toLowerCase().indexOf(text_value) == -1) {
            $row.hide();
            return;
          }
          $row.show();
        }

        $rows.each(showItemRow);
      }

      if ($table.length) {
        $rows = $table.find('tbody tr');
        $text_input.on('keyup', filterItemList);
        $group_select.on('change', filterItemList);
      }
    }
  };

}(jQuery, Drupal));
