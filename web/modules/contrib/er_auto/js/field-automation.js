/**
 * @file
 * Provide automation functionality for Entity Reference Automation module.
 */

(function ($, Drupal) {
  "use strict";

  /**
   * Check if an form field has the value set in the form.
   *
   * @param {string} field Drupal Field ID to check.
   * @param {string} value Field value to check if set.
   *
   * @return {boolean} weather the requested field has the requested value.
   */
  function checkFieldValue(field, value) {
    // Convert Drupal field id to css format.
    var target_css_id = "#edit-" + field.replace(/_/g, "-");
    var $target = $(target_css_id);
    if ($target.length) {
      for (var ti = 0; ti < $target.length; ti++) {
        var t = $target[ti];
        for( var c = 0; c < t.classList.length; c++) {
          // For relevant classes on the field, check its value.
          switch (t.classList[c]){
            case "form-select":
              return $(t).find('option[value="' + value + '"]').prop("selected");
            case "form-checkboxes":
              return $(t).find('input[type="checkbox"][value="' + value + '"]').prop("checked");
            case "form-radios":
              return $(t).find('input[type="radio"][value="' + value + '"]').prop("checked");
          }
        }
      }
    }
    return false;
  }

  /**
   * Get the list of fields the provide automation for a field and value.
   *
   * @param {string} target_field Drupal field id of target field.
   * @param {string} target_value Value of field to find providers for.
   * @param {string} excluded Value to exclude from provider list in the format
   *   "<field_id>:<value>".
   * @param {boolean} active Filter the list of providers to only those who are
   *   currently set in the form.
   *
   * @return {array} List of providers in the format "<field_id>:<value>" that
   *   would cause the target_field to have the target_value via automation.
   */
  function getProviderList(target_field, target_value, excluded, active) {
    var providers = [];
    var automation_data = drupalSettings.er_auto.automation
    // Interate over provider fields and values.
    for (var field in automation_data) {
      for (var value in automation_data[field]) {
        // Skip excluded provider
        var provider = field + ":" + value;
        if (provider === excluded) {
          continue;
        }
        // Check that the input automates the field
        if (automation_data[field][value][target_field] != "undefined") {
          // If filtering for active check that the value is set.
          if ((!active || checkFieldValue(field, value))) {
            // If the value is in the array, add the provider.
            if ($.inArray(target_value, automation_data[field][value][target_field]) >= 0) {
              providers.push(provider);
            }
          }
        }
      }
    }
    return providers;
  }

  /**
   * Apply automation to based on source field ID and values.
   *
   * @param ({string} source_id Drupal Field ID for source values.
   * @param {array} values Array of selected values for form element group
   *   corresponding to the Drupal Field.
   * @param {string} action add|remove directive for automation. Determines if
   *   this function shoud add or remove form elements based on the configured
   *   automations. Default is "add".
   */
  function automate(source_id, values, action) {
    var automation = drupalSettings.er_auto.automation[source_id];
    for (var v = 0; v < values.length; v++) {
      if (!isNaN(values[v])) {
        // Convert numbers, represented as stings, to actual numbers.
        values[v] = parseInt(values[v])
      }
      for (var target_id in automation[values[v]]) {
        // Convert Drupal field id to css format.
        var target_css_id = "#edit-" + target_id.replace(/_/g, "-");
        // Find the field container for the target, and set values.
        var $target = $(target_css_id);
        for (var ti = 0; ti < $target.length; ti++) {
          var t = $target[ti];
          for( var c = 0; c < t.classList.length; c++) {
            // For relevant classes on the field, handle updating values.
            switch (t.classList[c]) {
              case "form-select":
                // Update option values.
                for (var tv_ind = 0; tv_ind < automation[values[v]][target_id].length; tv_ind++) {
                  var target_value = automation[values[v]][target_id][tv_ind];
                  if (action === "remove" && getProviderList(target_id, target_value, source_id + ":" + values[v], true).length == 0) {
                    $(t).find('option[value="' + target_value + '"]').prop("selected", false);
                  } else {
                    $(t).find('option[value="' + target_value + '"]').prop("selected", "selected");
                  }
                }
                break;

              // Radio buttons and Checkboxes are almost the same, except for
              // the input type. However, only the last value set to the radio
              // group will remain.
              case "form-checkboxes":
              case "form-radios":
                // Determine input type, the default is checkbox.
                var input_type = "checkbox";
                if (t.classList[c] === "form-radios") {
                  input_type = "radio";
                }
                // Update Inputs.
                for (var tv_ind = 0; tv_ind < automation[values[v]][target_id].length; tv_ind++) {
                  var target_value = automation[values[v]][target_id][tv_ind];
                  if (action === "remove" && getProviderList(target_id, target_value, source_id + ":" + values[v], true).length == 0) {
                    $(t).find('input[type="' + input_type + '"][value="' + target_value + '"]').prop("checked", false);
                  } else {
                    $(t).find('input[type="' + input_type + '"][value="' + target_value + '"]').prop("checked", "checked");
                  }
                }
                break;
            }
          }
          $(t).change();
          if ($(t).data("chosen") != undefined) {
            $(t).trigger("chosen:updated");
          }
        }
      }
    }
  }

  /**
   * Attaches the automation to source fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   For each automation field add change handlers to allow automation actions
   *   to be added to the relevant page elements.
   */
  Drupal.behaviors.erAutoSource = {
    attach: function (context, settings) {
      // Find erAutoSource elements set during page generation
      $(context).find('.erAutoSource').once('erAutoSource').each(function () {
        var fieldType = $(this).data("er-auto-field-type");
        var fieldId = $(this).data("er-auto-field-id");

        // Bind action for relevant field type.
        switch (fieldType) {
          case "select":
            var select = $(this).find("select");
            // Save initial state as last.
            var lastState = select.val();

            // Bind to select.
            select.change(function (){
              var values = $(this).val();
              // Calculate change in values and run automation.
              var values_removed = $(lastState).not(values).get();
              var values_added = $(values).not(lastState).get();
              if (values_added) {
                automate(fieldId, values_added, "add");
              }
              if (values_removed) {
                automate(fieldId, values_removed, "remove");
              }
              // Save last state.
              lastState = values;
            });
            break;

          // Radio buttons and Checkboxes are almost the same, except for the
          // input type.
          case "radios":
          case "checkboxes":
            // Determine input type, the default is checkbox.
            var input_type = "checkbox";
            if (fieldType === "radios") {
              input_type = "radio";
            }

            // Convert Drupal field id to css format.
            var field_css_id = "#edit-" + fieldId.replace(/_/g, "-");
            var wrapper = $(this).find(field_css_id);

            // Bind to input fields of the correct type.
            wrapper.find('input[type="' + input_type + '"]').change(function (){
              // Run automation based on state of input.
              if ($(this).prop("checked")) {
                automate(fieldId, [$(this).val()]);
              }else {
                automate(fieldId, [$(this).val()], "remove");
              }
            });
            break;
        }
      });
    }
  };
})(jQuery, Drupal);
