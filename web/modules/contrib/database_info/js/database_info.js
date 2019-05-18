/**
 * @file
 * Filter table feature.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.filterTbales = {
    attach: function (context, settings) {
      $(document).ready(function () {
        $('#list-wrapper').once().prepend("<div class='filter-box'><span><label>Enter Table Name:</label></span><input type='text' class='table-filter' placeholder='Find tables'></div>");
        $('.table-filter').on('keyup', function () {
          var value = $(this).val().toLowerCase();
          $('.list-tables .db-table').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
          });
        });
      });
    }
  };

})(jQuery, Drupal);
