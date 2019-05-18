/**
 * @file
 * Provides some configurations for the tablesorter.
 */

var pp_taxonomy_manager_table_interval;

(function ($) {
  Drupal.behaviors.pp_taxonomy_manager  = {
    attach: function () {

      // Show/Hide additional fields if a checkbox is enabled/disabled
      $("#pp-taxonomy-manager-taxonomy-export-form, #pp-taxonomy-manager-taxonomy-import-form").bind("state:visible", function(e) {
        if(e.trigger) {
          $(e.target).closest(".form-item, .form-submit, .form-wrapper")[e.value ? "slideDown" : "slideUp"]();
          e.stopPropagation();
        }
      });

      // Make the project tables sortable if tablesorter is available.
      if ($.isFunction($.fn.tablesorter)) {
        $("table#pp-taxonomy-manager-configurations-table").tablesorter({
          widgets: ["zebra"],
          widgetOptions: {
            zebra: ["odd", "even"]
          },
          sortList: [[0, 0]],
          headers: {
            3: { sorter: false },
            4: { sorter: false }
          }
        });

        $("table#pp-taxonomy-manager-synced-table").tablesorter({
          widgets: ["zebra"],
          widgetOptions: {
            zebra: ["odd", "even"]
          },
          sortList: [[1, 1], [0, 0]],
          headers: {
            3: { sorter: "text" }
          }
        });

        $("table#pp-taxonomy-manager-interconnection-table").tablesorter({
          widgets: ["zebra"],
          widgetOptions: {
            zebra: ["odd", "even"]
          },
          sortList: [[1, 1], [0, 0]]
        });

        pp_taxonomy_manager_table_interval = setInterval(function(){
          if($("table#pp-taxonomy-manager-interconnection-table").is(':visible')) {
            $("table#pp-taxonomy-manager-interconnection-table").trigger('applyWidgets');
            clearInterval(pp_taxonomy_manager_table_interval);
          }
        }, 50);

        $("table#pp-taxonomy-manager-suggest-concept-config-list-table").tablesorter({
          widgets: ["zebra"],
          widgetOptions: {
            zebra: ["odd", "even"]
          },
          sortList: [[0, 0]],
          headers: {
            3: {sorter: false},
            6: {sorter: false}
          }
        });

        $("table#pp-taxonomy-manager-suggested-concepts-table").tablesorter({
          widgets: ["zebra"],
          widgetOptions: {
            zebra: ["odd", "even"]
          },
          sortList: [[0, 0]]
        });
      }

      if ($("form#pp-taxonomy-manager-add-form").length > 0) {
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
