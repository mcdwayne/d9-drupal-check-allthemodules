(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.pp_graphsearch = {
    attach: function () {

      var page = 0;
      var loading = false;
      var items_loaded = null;
      var filter_form = $("#pp-graphsearch-filters-form");
      var search_bar_form = $(".pp-graphsearch-search-bar");
      var facet_box_form = $("#pp-graphsearch-facet-box-form");
      var filter_operations = $("#filter-operations");
      var result_list = $("div.view-content");

      // Initialize the facet box.
      facetBox.init();

      // Set the submit event for filter form.
      filter_form.once("pp-graphsearch").each(function() {
        $(this).submit(function(event) {
          event.preventDefault();
          page = 0;
          loading = false;

          // Collect all data.
          var data = {filters: collectFilters(false)};

          // Call ajax callback function.
          search(data, page);
        });
      });

      // Set the change event for the filters.
      filter_form.find("input:not(.filter-reset, .facet-autocomplete), select").once("pp-graphsearch").each(function() {
        $(this).change(function() {
          // Remove the count value with the brackets.
          var label = $(this).next().text();
          label = label.slice(0, label.lastIndexOf("("));
          var facet = {
            type: "concept",
            field: $(this).attr("data-field"),
            value: $(this).val(),
            label: label
          };
          if ($(this).is(":checked")) {
            facetBox.add(facet);
          }
          else {
            facetBox.remove(facet);
          }
        });
      });

      // Set the change event for the tree display of filters.
      filter_form.find(".tree-open-close").once("pp-graphsearch").each(function() {
        $(this).each(function() {
          var sub_container = $(this).parent().next();
          if (sub_container.find("input:checked").length > 0) {
            $(this).removeClass('collapsed');
            sub_container.show();
          }
        });

        $(this).click(function() {
          var sub_container = $(this).parent().next();
          if ($(this).hasClass('collapsed')) {
            $(this).removeClass('collapsed');
            sub_container.slideDown(200);
          }
          else {
            $(this).addClass('collapsed');
            sub_container.slideUp(200);
          }
        });
      });

      // Set click event for filter reset button.
      filter_form.find(".filter-reset").once("pp-graphsearch").each(function() {
        $(this).click(function() { return reset(); });
      });

      // Hide or grey out empty fieldsets.
      filter_form.find(".form-checkboxes").once("pp-graphsearch").each(function() {
        var facet_fieldset = $(this).closest("fieldset");
        var facet_link = facet_fieldset.find("legend span");

        if ($(this).children('.form-item-container').length === 0) {
          if (drupalSettings.pp_graphsearch.hide_empty_facet) {
            facet_fieldset.parent().parent().parent().slideUp(300);
          }
          else {
            facet_link.addClass("greyed-out");
          }
        }
      });

      // Set the change event for the filters.
      filter_form.find("input.facet-autocomplete").once("pp-graphsearch").each(function() {
        $(this).focus(function() {
          $(this).val("");
        });

        $(this).on('autocompleteselect', function(event, ui) {
          var data = ui.item.value.split("|");
          facetBox.setCache({
            type: "concept",
            field: data[0],
            value: data[1],
            label: ui.item.label
          });
          search_bar_form.find(".search-bar-submit").click();
        });

        $(this).on('autocompleteclose', function(event, ui) {
          $(this).val("");
        });

        // Reapply the autocomplete behavior after the filters get updated.
        if (!$(this).hasClass('ui-autocomplete-input')) {
          $(this).autocomplete(Drupal.autocomplete.options);
        }

        $(this).autocomplete("option", "minLength", drupalSettings.pp_graphsearch.min_chars);

        // Set the custom popup menu for the autocomplete field.
        var autocomplete = $(this).data("ui-autocomplete");
        var data_item = "ui-autocomplete-item";
        if (typeof autocomplete === "undefined") {
          autocomplete = $(this).data("autocomplete");
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

      // Set click event for search bar submit button.
      search_bar_form.find(".search-bar-submit").once("pp-graphsearch").each(function() {
        $(this).click(function(event) {
          event.preventDefault();
          if (facetBox.isEmptyCache()) {
            var search_bar_text = search_bar_form.find("input.form-text");
            var value = search_bar_text.val();
            var facet = {
              type: "free-term",
              field: "dyn_txt_content",
              value: value.trim(),
              label: value.trim()
            };
            search_bar_text.autocomplete("close").val("");
          }
          else {
            facet = facetBox.getCache();
            facetBox.clearCache();
          }
          if (facet.label !== "") {
            facetBox.add(facet);
          }
        });
      });

      // Set click event for search bar reset button.
      search_bar_form.find(".search-bar-reset").once("pp-graphsearch").each(function() {
        $(this).click(function() { return reset(); });
      });

      // Set focus event for the search bar text field.
      search_bar_form.find("input.form-text").once("pp-graphsearch").each(function() {
        $(this).focus(function() {
          $(this).val("");
        });

        $(this).on('autocompleteselect', function(event, ui) {
          var data = ui.item.value.split("|");
          facetBox.setCache({
            type: "concept",
            field: data[0],
            value: data[1],
            label: ui.item.label
          });
          search_bar_form.find(".search-bar-submit").click();
        });

        $(this).on('autocompleteclose', function(event, ui) {
          $(this).val("");
        });

        $(this).autocomplete( "option", "minLength", drupalSettings.pp_graphsearch.min_chars);

        // Set the custom popup menu for the autocomplete field.
        var autocomplete = $(this).data("ui-autocomplete");
        var data_item = "ui-autocomplete-item";
        if (typeof autocomplete === "undefined") {
          autocomplete = $(this).data("autocomplete");
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

      // Set the submit event for facet box form.
      facet_box_form.once("pp-graphsearch").each(function() {
        $(this).submit(function(event) {
          event.preventDefault();
          loading = false;

          // Collect all data.
          var data = {
            filters: collectFilters(true),
            title: facet_box_form.find('#edit-title').val(),
            time_interval: facet_box_form.find('#edit-time-interval').val()
          };

          // Call ajax callback function.
          saveFilter(data);
        });
      });

      // Set click event for facet box form save button.
      facet_box_form.find(".facet-box-form-save").once("pp-graphsearch").each(function() {
        $(this).click(function(event) {
          event.preventDefault();
          facetBox.submitFilterForm();
        });
      });

      // Set click event for search bar save button.
      filter_operations.find(".add").once("pp-graphsearch").each(function() {
        $(this).click(function(event) {
          facetBox.triggerFilterForm();
        });
      });

      filter_operations.find(".show").once("pp-graphsearch").each(function() {
        $(this).click(function(event) {
          if (facetBox.filters().is(":hidden")) {
            showFilters();
          }
          else {
            facetBox.hideFilters();
          }
        });
      });

      // Set click event for similar documents link.
      result_list.find(".similar-more").once("pp-graphsearch").each(function() {
        var uri = $(this).attr('data-uri');
        $(this).click(function(event) {
          event.preventDefault();
          var container = $(this).next();
          $(this).toggleClass('container-open');

          // If the container is empty, then get similars
          // else open or close the container
          if (container.html() == "") {
            getSimilars(uri, $(this).next());
          } else {
            if (container.is(':visible')) {
              container.slideUp();
            } else {
              container. slideDown();
            }
          }
        });
      });

      // Set click event for "show all tags"
      result_list.find(".tags-more").once("pp-graphsearch").each(function() {
        $(this).click(function() {
          var show_tags_area = $(this).parent();
          show_tags_area.removeClass("tags-show");
          show_tags_area.attr("title", "");
          $(this).addClass("hidden");
          show_tags_area.find(".tags-rest").fadeIn();
        });
      });

      // Add the trend chart if data is available
      if ($("#edit-trends").children().length) {
        plotData("trends-placeholder", $.parseJSON($("#edit-trends").find(".float-chart-values").text()));
        useTooltipp("trends-placeholder");
      }

      // Set the infinity scroll event.
      $("body").once("pp-graphsearch").each(function() {
        if ($("div.view-pp-graphsearch-list").hasClass("views-load-more")) {
          $(window).scroll(function() {
            var winTop        = $(window).scrollTop(),
              winHeight     = $(window).height(),
              docHeight     = $(document).height(),
              footerHeight  = $("footer").height(),
              scrollTrigger = 1;

            if (items_loaded == null) {
              items_loaded = $(".view-pp-graphsearch-list>div.view-content").children().length;
            }

            if ((items_loaded >= drupalSettings.pp_graphsearch.items_per_page) && ((winTop / (docHeight - winHeight - footerHeight)) >= scrollTrigger)) {
              loadInfinity();
            }
          });
        }
      });

      // Starts the search with the given filters.
      var search = function(data, page) {
        $.getJSON(drupalSettings.path.baseUrl + "pp-graphsearch/get-results/" + getConfigurationSetId() + "/" + page, data, function(data) {
          // Show result list.
          var area_div = $(".view-pp-graphsearch-list").parent();
          area_div.children(".view-pp-graphsearch-list").replaceWith(data.list.content);
          items_loaded = $(data.list.content).find("div.view-content").children().length;

          // Show the new filter.
          var new_filter_form = $(data.filter);
          new_filter_form.find(".form-checkboxes").each(function() {
            var facet_id = this.id.substr(5);
            var facet_fieldset = $("#edit-pp-graphsearch-fieldset-" + facet_id);
            var facet_link = facet_fieldset.find("a.fieldset-title");
            var checkboxes = $("#edit-" + facet_id);
            checkboxes.empty().append($(this).html());

            if ($(this).children('.form-item-container').length === 0) {
              if (drupalSettings.pp_graphsearch.hide_empty_facet) {
                facet_fieldset.parent().parent().parent().slideUp(300);
              }
              else {
                facet_link.parent().addClass("greyed-out");
                facet_link.unbind("click").click(function () {
                  return false;
                });
              }
            }
            else {
              if (drupalSettings.pp_graphsearch.hide_empty_facet) {
                facet_fieldset.parent().parent().parent().slideDown(300);
              }
              else {
                facet_link.parent().removeClass("greyed-out");
                facet_link.unbind("click").click(function () {
                  var fieldset = facet_fieldset.get(0);
                  // Don't animate multiple times.
                  if (!fieldset.animating) {
                    fieldset.animating = true;
                    Drupal.toggleFieldset(fieldset);
                  }
                  return false;
                });
              }
              if (typeof(Drupal.behaviors.uniform) !== "undefined") {
                checkboxes.find("input").uniform();
              }
              if (checkboxes.find("input:checked").length > 0 && facet_fieldset.hasClass("collapsed")) {
                facet_fieldset.find("a.fieldset-title").trigger("click");
              }
            }
          });

          // Update the RSS button if required.
          if (data.list.hasOwnProperty('rss_button') && area_div.children(".rss-link").length > 0) {
            area_div.children(".rss-link").replaceWith(data.list.rss_button);
          }

          // Update the results count.
          if (data.list.hasOwnProperty('results_count') && area_div.children(".pp-graphsearch-results-count").length > 0) {
            area_div.children(".pp-graphsearch-results-count").replaceWith(data.list.results_count);
          }

          // Add the date values
          var from_date = new_filter_form.find("#edit-date-from input").val();
          $("#edit-date-from").find("input").val(from_date);
          var to_date = new_filter_form.find("#edit-date-to input").val();
          $("#edit-date-to").find("input").val(to_date);

          // Add the trends-chart if enabled.
          if (new_filter_form.find("#edit-trends-wrapper").length > 0) {
            var trends = $("#edit-trends-wrapper");
            trends.empty();
            if (new_filter_form.find("#edit-trends-wrapper").children().length > 0) {
              trends.append(new_filter_form.find("#edit-trends-wrapper").html());
            }
          }
          Drupal.behaviors.pp_graphsearch.attach();
          Drupal.behaviors.semanticConnectorConceptDestinations.attach();

          filter_form.find(".tree-open-close").each(function() {
            var sub_container = $(this).parent().next();
            if (sub_container.find("input:checked").length > 0) {
              $(this).removeClass('collapsed');
              sub_container.show();
            }
          });
        });
      };

      // Saves the filters for the logged in user.
      var saveFilter = function(data) {
        $.get(drupalSettings.path.baseUrl + "pp-graphsearch/search-filters/save/" + getConfigurationSetId(), data, function(data) {
          if (data == 'saved') {
            facetBox.hideFilterForm();
          }
          else {
            facet_box_form.find(".error").html(data).slideDown();
          }
        });
      };

      // Shows all the saved filters for the logged in user as a list.
      var showFilters = function() {
        $.get(drupalSettings.path.baseUrl + "pp-graphsearch/search-filters/get/" + getConfigurationSetId(), function(data) {
          facetBox.showFilters(data);
          facet_box_form.find(".search-filter-title").each(function() {
            $(this).bind("click", function() { searchFilter(this); });
          });
          facet_box_form.find(".search-filter-remove").each(function() {
            $(this).click(function() { removeFilter(this); });
          });
        });
      };

      // Starts a search from a saved filter in the filter list.
      var searchFilter = function(item) {
        // Reset the all filters.
        facetBox.hideAll(false);
        facetBox.facets = [];
        filter_form.find('input[name="date_from[date]"]').val("");
        filter_form.find('input[name="date_to[date]"]').val("");

        var filters = $(item).data("filter");
        $.each(filters.filters, function(index, facet) {
          if (facet.field == "date-from" || facet.field == "date-to") {
            filter_form.find('input[data-field="' + facet.field + '"]').val(facet.value);
          }
          else {
            facetBox.facets.push(facet);
          }
        });
        facetBox.showAll(true);
        facetBox.hideFilters();
        facetBox.hideFilterForm();

        var data = {filters: collectFilters(false)};
        search(data, 0);
      };

      // Deletes a saved filter from the logged in user.
      var removeFilter = function(item) {
        var data = {sfid: $(item).data("sfid")};
        $.get(drupalSettings.path.baseUrl + "pp-graphsearch/search-filters/delete/" + getConfigurationSetId(), data, function(data) {
          if (data == "deleted") {
            $(item).parent().fadeOut(function() {
              $(this).remove();
            });
            if ($(item).parent().siblings().length == 0) {
              facetBox.hideFilters();
            }
          }
          else {
            $(item).html(data).addClass("messages error");
          }
        });
      };

      // Gets the similar documents from a given document URI.
      var getSimilars = function(uri, container) {
        $.get(drupalSettings.path.baseUrl + "pp-graphsearch/get-similars/" + getConfigurationSetId(), {uri:uri}, function(data) {
          $(container).hide().html(data).slideDown();
          Drupal.behaviors.pp_graphsearch.attach(data);
        });
      };

      // The load infinity function.
      var loadInfinity = function() {
        if (!loading) {
          loading = true;

          // Collect all data and increase page number.
          page += 1;
          var data = {
            filters: collectFilters(false)
          };

          // Call ajax callback function.
          $.getJSON(drupalSettings.path.baseUrl + "pp-graphsearch/get-results/" + getConfigurationSetId() + "/" + page, data, function(data) {
            // Add new list items.
            items_loaded = $(data.list.content).find("div.view-content").children().length;
            if (items_loaded > 0) {
              $('.view-pp-graphsearch-list>div.view-content').append($(data.list.content).find('div.view-content').html());
              loading = false;
              Drupal.behaviors.pp_graphsearch.attach(data);
            }
          });
        }
      };

      // The reset function.
      var reset = function() {
        filter_form.find('input[name="date_from[date]"]').val("");
        filter_form.find('input[name="date_to[date]"]').val("");
        filter_form.find('select[name="date_from[date]"]').val("");
        facetBox.clear();
        return false;
      };

      // Collects the filter data.
      var collectFilters = function(withLabel) {
        // Collect the selected filters.
        var $form = $("#pp-graphsearch-filters-form"), filters = [];

        // Get all selected facets (concepts and free terms)
        facetBox.facets.forEach(function(facet) {
          if (withLabel) {
            filters.push({field:facet.field, value:facet.value, label:facet.label, type:facet.type});
          }
          else {
            filters.push({field:facet.field, value:facet.value});
          }
        });

        // From - To text fields
        $form.find('input[name="date_from[date]"], input[name="date_to[date]"]').each(function () {
          if ($(this).val().length) {
            filters.push({field:$(this).attr("data-field"), value:$(this).val()});
          }
        });
        // Select date format
        $form.find('select[name="date_from[date]"]').each(function () {
          var date_length = $(this).val().length;
          if (date_length) {
            if (date_length > 4) {
              filters.push({field:$(this).attr("data-field"), value:$(this).val()});
            }
            else {
              filters.push({field:$(this).attr("data-field"), value:$(this).val() + "-01-01"});
              filters.push({field:"date-to", value:$(this).val() + "-12-31"});
            }
          }
        });

        return filters;
      };

      // Gets the PoolParty GraphSearch configuration set id from the DOM
      var getConfigurationSetId = function() {
        return $('.view-pp-graphsearch-list').attr('data-id');
      };

      // Insert a clear:both style after right area
      $(".block-pp-graphsearch .pp-graphsearch-area-right").once("pp-graphsearch").each(function() {
        $('<div style="clear:both;" />').insertAfter($(this));
      });

      // Exposed filter adaptions
      $(".block-pp-graphsearch").find(".views-exposed-form").find(".views-widget-filter-date-from").once("pp-graphsearch").each(function() {
        $(this).prepend($(this).find("label"));
      });
    }
  };

  var facetBox = {
    facets: [],
    cache: {},

    container: function() {
      return $("#facet-container");
    },

    box: function() {
      return this.container().children(".facet-box").first();
    },

    form: function() {
      return this.container().children(".facet-box-form").first();
    },

    filters: function() {
      return this.container().children(".search-filters").first();
    },

    operations: function() {
      return $("#filter-operations");
    },

    emptybox: function() {
      return this.container().children(".facets-empty").first();
    },

    /* Initializes the facetBox on start up. */
    init: function() {
      if (drupalSettings.pp_graphsearch.hasOwnProperty("facet_box") && drupalSettings.pp_graphsearch.facet_box.length) {
        this.facets = [];
        drupalSettings.pp_graphsearch.facet_box.forEach(function (facet) {
          facetBox.facets.push(facet);
          facetBox.show(facet, false);
        });
        drupalSettings.pp_graphsearch.facet_box = [];
      }
      if (this.facets.length > 0) {
        this.operations().children(".add").show();
        this.emptybox().hide();
      }
      else {
        this.emptybox().show();
      }
    },

    /* Adds a new facet to the facetBox. */
    add: function(facet) {
      facet.label = facet.label.trim();
      var search_types = drupalSettings.pp_graphsearch.search_type.split(" ");
      if (!$.inFacetBox(facet, this.facets) && $.inArray(facet.type, search_types) != -1) {
        this.facets.push(facet);
        this.show(facet, true);
        this.submit();
      }
    },

    /* Removes a facet from the facetBox. */
    remove: function(facet) {
      var item;
      if ($.type(facet.field) !== "undefined") {
        item = this.box().find('.' + facet.type + '[data-value="' + facet.value + '"]');
      }
      else {
        item = facet;
        facet = {
          type: item.hasClass("concept") ? 'concept' : 'free-term',
          field: item.data("field"),
          value: item.data("value"),
          label: item.children().first().text()
        };
      }
      this.facets = $.removeFromFacetBox(facet, this.facets);
      this.hide(item, true);
      this.submit();
    },

    /* Clears the facetBox. */
    clear: function() {
      this.facets = [];
      this.box().children().each(function() {
        facetBox.hide($(this), true);
      });
      this.submit();
    },

    /* Shows a facet in the facetBox. */
    show: function(facet, animate) {
      var item_label = $("<div>").addClass("facet-label").html(facet.label);
      var item = $("<a>").addClass("facet-item " + facet.type).click(function (event) {
        event.preventDefault();
        facetBox.remove($(this));
      });
      item.attr("data-value", facet.value);
      item.attr("data-field", facet.field);
      item.append(item_label);
      this.box().append(item);
      if (animate) {
        // Switch from 0 to 1 facet.
        if (this.facets.length == 1) {
          this.emptybox().hide();
          this.operations().children(".add").fadeIn();
        }
        item.hide().fadeIn();
      }
      else {
        // Switch from 0 to 1 facet.
        if (this.facets.length == 1) {
          this.emptybox().hide();
          this.operations().children(".add").show();
        }
      }
    },

    /* Hides a facet in the facetBox. */
    hide: function(facet, animate) {
      var item;
      if ($.type(facet.field) !== "undefined") {
        item = this.box().find('.' + facet.type + '[data-value="' + facet.value + '"]');
      }
      else {
        item = facet;
      }
      if (animate) {
        item.fadeOut(function() {
          // Removing the last facet.
          if (facetBox.facets.length == 0) {
            facetBox.emptybox().show();
          }
          this.remove();
        });
        if (facetBox.facets.length == 0) {
          this.operations().children(".add").fadeOut();
        }
      }
      else {
        // Removing the last facet.
        if (this.facets.length == 0) {
          this.emptybox().show();
          this.operations().children(".add").hide();
        }
        item.remove();
      }
    },

    /* Shows all facets in the facetBox. */
    showAll: function(animate) {
      this.facets.forEach(function(facet) {
        facetBox.show(facet, animate);
      });
    },

    /* Hides all facet in the facetBox. */
    hideAll: function(animate) {
      this.facets.forEach(function(facet) {
        facetBox.hide(facet, animate);
      })
    },

    /* Starts the search with the selected filters. */
    submit: function() {
      this.hideFilterForm();
      this.hideFilters();
      $("#pp-graphsearch-filters-form").submit();
    },

    /* Saves one facet into the cache. */
    setCache: function(facet) {
      this.cache = facet;
    },

    /* Returns the cached facet. */
    getCache: function() {
      return this.cache;
    },

    /* Clears the cache. */
    clearCache: function() {
      this.cache = {};
    },

    /* Returns true if the cache is empty otherwise false. */
    isEmptyCache: function() {
      return $.isEmptyObject(this.cache);
    },

    triggerFilterForm: function() {
      if (this.form().is(":hidden")) {
        this.showFilterForm();
      }
      else {
        this.hideFilterForm();
      }
    },

    showFilterForm: function() {
      this.form().slideDown();
      this.operations().children(".add").html(Drupal.t("Close form"));
    },

    hideFilterForm: function() {
      this.form().slideUp(function() {
        $(this).find(".error").html("").hide();
      });
      this.operations().children(".add").html(Drupal.t("Add to my filters"));
    },

    submitFilterForm: function() {
      $("#pp-graphsearch-facet-box-form").submit();
    },

    showFilters: function(filters) {
      this.filters().html(filters);
      this.filters().slideDown();
      this.operations().children(".show").html(Drupal.t("Close my filters"));
    },

    hideFilters: function() {
      this.filters().slideUp();
      this.operations().children(".show").html(Drupal.t("Show my filters"));
    }

  };


  $.inFacetBox = function(facet, facets) {
    var arr = $.grep(facets, function(item) {
      return (item.type == facet.type && item.value == facet.value);
    });
    return arr.length;
  };

  $.removeFromFacetBox = function(facet, facets) {
    return $.grep(facets, function(item) {
      return (item.type != facet.type || item.value != facet.value);
    });
  };

})(jQuery, Drupal, drupalSettings);
