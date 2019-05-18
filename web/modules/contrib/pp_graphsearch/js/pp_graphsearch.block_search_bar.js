/**
 * @file
 *
 * Javascript functionalities for the search bar in a block.
 */
(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.pp_graphsearch_block_search_bar = {
    attach: function (context, settings) {
      $("div.pp-graphsearch-block-search-bar", context).once('pp_graphsearch_block_search_bar').each(function () {
        var input = $("input", this);

        // Search for free terms.
        //var search_types = input.data("search-type").split(" ");
        var search_types = drupalSettings.pp_graphsearch.search_type.split(" ");
        $(this).closest("form").submit(function (event) {
          // Check if searching for free terms is activated.
          if ($.inArray("free-term", search_types) !== -1) {
            window.location.href = drupalSettings.path.baseUrl.substr(0, -1) + drupalSettings.pp_graphsearch.page_path + '?search=' + input.val();
          }
          event.preventDefault();
        });

        // Clear the input field on focus.
        input.focus(function() {
          input.val("");
        });

        input.on('autocompleteselect', function(event, ui) {
          event.preventDefault();
          this.value = ui.item.label;
          var data = ui.item.value.split("|");
          window.location.href = drupalSettings.path.baseUrl.substr(0, -1) + drupalSettings.pp_graphsearch.page_path + '?uri=' + data[1];
          return false;
        });

        input.on('autocompleteclose', function(event, ui) {
          $(this).val("");
        });

        input.autocomplete( "option", "minLength", drupalSettings.pp_graphsearch.min_chars);

        // Set the custom popup menu for the autocomplete field.
        var autocomplete = input.data("ui-autocomplete");
        var data_item = "ui-autocomplete-item";
        if (typeof autocomplete === "undefined") {
          autocomplete = input.data("autocomplete");
          data_item = "item.autocomplete";
        }

        autocomplete._renderItem = function (ul, item_data) {
          var item = $("<a>" + item_data.label + "</a>");
          if (drupalSettings.pp_graphsearch.add_matching_label) {
            $("<div class='ui-menu-item-metadata'>")
              .append("<span class='ui-menu-item-metadata-label'>" + Drupal.t("Matching label") + ":</span>")
              .append("<span class='ui-menu-item-metadata-value'>" + item_data.matching_label + "</span>")
              .appendTo(item);
          }
          if (drupalSettings.pp_graphsearch.add_context) {
            $("<div class='ui-menu-item-metadata'>")
              .append("<span class='ui-menu-item-metadata-label'>" + Drupal.t("Context") + ":</span>")
              .append("<span class='ui-menu-item-metadata-value'>" + item_data.context + "</span>")
              .appendTo(item);
          }

          return $("<li>").data(data_item, item_data).append(item).appendTo(ul);
        };
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
