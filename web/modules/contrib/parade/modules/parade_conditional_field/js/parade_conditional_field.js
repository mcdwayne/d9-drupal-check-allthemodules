(function ($, Drupal) {

Drupal.behaviors.paradeConditionalFields = {
  attach: function (context, settings) {
    // AJAX is not updating settings.paradeConditionalFields correctly.
    var conditionalFields = settings.paradeConditionalFields || "undefined";
    if (conditionalFields === "undefined") {
      return;
    }

    /**
     * Set value depends on type (radio or other)
     *
     * @param element_selector
     * @param value
     */
    function setValueForField(element_selector, value) {
      if ($(element_selector + "[value='" + value + "']").attr("type") === "radio") {
        $(element_selector + "[value='" + value + "']").prop("checked", true);
      }
      else {
        $(element_selector).val(value);
      }
    }

    /**
     * @param condition.event.data.field_name.on_values
     * @param condition.field_name.dependents
     */
    function onChangeParadeField(event) {
      var elm = $(this);
      var value_set = false;
      var default_value_set = false;
      var elm_value = elm.val();
      var match = elm_value.match(/ \((.*)\)$/);
      if (elm.hasClass("form-autocomplete") && match) {
        elm_value = match[1];
      }
      var first_parent_wrapper_id = elm.closest(event.data.wrapper).attr("id");
      $.each(conditionalFields[event.data.bundle], function (id, condition) {
        if (condition[event.data.field_name] && !value_set) {
          // Value was changed to value which is in 'on_values' - we should trigger.
          if (elm_value in condition[event.data.field_name].on_values) {
            value_set = true;
            $.each(condition[event.data.field_name].dependents, function (d_field, d_data) {
              // Set value(s).
              var element_selector = "#" + first_parent_wrapper_id + " :input[name*='[" + d_field + "]']";
              if (typeof d_data.values != "undefined" && typeof d_data.values[0] != "undefined") {
                setValueForField(element_selector, d_data.values[0]);
              }
              // Set enabled selectable value(s).
              if (typeof d_data.options != "undefined") {
                $("#" + first_parent_wrapper_id + " :input[name*='[" + d_field + "]']" + " option").each(function () {
                  var option = $(this);
                  if (d_data.options.length === 0 || option.val() in d_data.options || option.val() == "_none") {
                    option.show();
                  }
                  else {
                    option.hide();
                    if (option.val() == $(element_selector).val()) {
                      setValueForField(element_selector, "_none");
                    }
                  }
                });
              }
            });
          }
          // Set default values/show all options.
          else {
            if (!default_value_set) {
              $.each(condition[event.data.field_name].dependents, function (d_field, d_data) {
                default_value_set = true;
                var element_selector = "#" + first_parent_wrapper_id + " :input[name*='[" + d_field + "]']";
                if (d_field == "parade_view_mode") {
                  setValueForField(element_selector, "default");
                }
                if (d_field == "parade_color_scheme") {
                  $(element_selector + " option").show();
                }
              });
            }
          }
        }
      });
    }

    $.each(conditionalFields, function (bundle, conditions) {
      $.each(conditions, function (id, condition) {
        $.each(condition, function (field_name, condition_data) {
          var wrapper = ".paragraphs-wrapper-bundle-" + bundle;
          $(wrapper + " :input[name*='[" + field_name + "]']", context).on("change", {bundle: bundle, wrapper: wrapper, field_name: field_name}, onChangeParadeField).trigger("change");
          // Hide view mode selector.
          $(wrapper + " .field--type-view-mode-selector.field--name-parade-view-mode").hide();
        });
      });
    });
  }
};

})(jQuery, Drupal);
