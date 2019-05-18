(function ($) {
  Drupal.behaviors.pp_graphsearch  = {
    attach: function () {

      // Show/Hide additional fields if a checkbox is enabled/disabled
      $("#pp-graphsearch-configuration-set-form, #pp-graphsearch-sync-form").bind("state:visible", function(e) {
        if(e.trigger) {
          $(e.target).closest(".form-item, .form-submit, .form-wrapper")[e.value ? "slideDown" : "slideUp"]();
          e.stopPropagation();
        }
      });

      // Show/Hide additional information to a selected value if available.
      $("#edit-time-filter").change(function() {
        $("#edit-date-info")[$(this).val() == "from_to_textfields" ? 'slideDown' : 'slideUp']();
      });
      $("#edit-add-trends").change(function() {
        $("#edit-trends-info")[$(this).is(":checked") ? 'slideDown' : 'slideUp']();
      });

      // Make the project tables sortable if tablesorter is available.
      if ($.isFunction($.fn.tablesorter)) {
        $("table#pp-graphsearch-configurations-table").tablesorter({
          widgets: ["zebra"],
          widgetOptions: {
            zebra: ["odd", "even"]
          },
          sortList: [[0, 0]],
          headers: {
            5: { sorter: false }
          }
        });
        $("table#pp-graphsearch-sync-table").tablesorter({
          widgets: ["zebra"],
          widgetOptions: {
            zebra: ["odd", "even"]
          },
          sortList: [[0, 0]],
          headers: {
            2: { sorter: false }
          }
        });
      }

      if ($("form#pp-graphsearch-add-form, form#pp-graphsearch-edit-form").length > 0) {
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
