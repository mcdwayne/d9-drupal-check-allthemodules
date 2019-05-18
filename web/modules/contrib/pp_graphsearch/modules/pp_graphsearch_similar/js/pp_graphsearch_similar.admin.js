(function ($) {
  Drupal.behaviors.pp_graphsearch_similar  = {
    attach: function () {
      // Make the project tables sortable if tablesorter is available.
      if ($.isFunction($.fn.tablesorter)) {
        $("table#pp-graphsearch-similar-configurations-table").tablesorter({
          widgets: ["zebra"],
          widgetOptions: {
            zebra: ["odd", "even"]
          },
          sortList: [[0, 0]],
          headers: {
            3: { sorter: false }
          }
        });
      }

      if ($("form#pp-graphsearch-similar-add-form, form#pp-graphsearch-similar-edit-form").length > 0) {
        $('#edit-load-connection').change(function() {
          var connection_value = (jQuery(this).val());
          if (connection_value.length > 0) {
            var connection_details = connection_value.split('|');
            jQuery('#edit-server-title').val(connection_details[0]);
            jQuery('#edit-url').val(connection_details[1]);
            jQuery('#edit-username').val(connection_details[2]);
            jQuery('#edit-password').val(connection_details[3]);
          }
          return false;
        });
      }
    }
  };
})(jQuery);
