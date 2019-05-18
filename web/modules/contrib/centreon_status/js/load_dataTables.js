/**
 * @file
 * Contains load_dataTables.js.
 */

(function ($) {
  $('#centreon_status').DataTable({
    "order": [[1, "asc"]],
    "info": false,
    "searching": false,
    "paging": false,
  });
})(jQuery);
