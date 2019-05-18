/**
 * @file
 * Override the select handler for autocomplete.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  var original_select_handler = Drupal.autocomplete.options.select;

  Drupal.autocomplete.options.select = function (event, ui) {
    // Get the target as a jQuery object.
    var $target = $(event.target);
    // Initialize an empty value field object.
    var $value_field;

    // If the friendly autocomplete ID isn't present, use the original handler.
    if (!$target.attr('friendly-autocomplete-id')) {
      return original_select_handler(event, ui);
    }

    // Get the friendly autocomplete ID from the field.
    var autocomplete_id = $target.attr('friendly-autocomplete-id');
    // Try to find the associated value field.
    $value_field = $('input[friendly-autocomplete-id=' + autocomplete_id + '][' + autocomplete_id + '=value]');
    if ($value_field !== null && $value_field.length > 0) {
      // If the value field was found, put the value and label in their
      // respective fields.
      $target.val(ui.item.label);
      $value_field.val(ui.item.value);
      // Remove the dirty attribute since it is no longer dirty.
      $value_field.removeAttr('friendly-autocomplete-dirty');
      // Blur the target field to prevent further input.
      $target.blur();
    }
    else {
      // If the value field was not found, default to the original handler.
      return original_select_handler(event, ui);
    }

    // Return false to tell jQuery UI that we've filled in the value already.
    return false;
  };

  Drupal.behaviors.friendlyAutocompleteHelper = {
    attach: function (context) {
      // Get the context as a jQuery object.
      var $context = $(context);
      // Disable browser autocomplete on all fields.
      $context.find('input[friendly-autocomplete-id]').attr('autocomplete', 'off');
      // When the label field is focused, store the current values as an
      // attribute on the value field.
      $context.find('input[friendly-autocomplete-id]').focus(function () {
        // Get the friendly autocomplete ID from the field.
        var autocomplete_id = $(this).attr('friendly-autocomplete-id');
        // If this is the value field, do nothing.
        if ($(this).attr(autocomplete_id) == 'value') {
          return;
        }
        // Get the related value field jQuery object.
        var $value_field = $context.find('input[friendly-autocomplete-id=' + autocomplete_id + '][' + autocomplete_id + '=value]');
        // Only store the dirty attribute if it does not already exist.
        if (!$value_field.attr('friendly-autocomplete-dirty')) {
          // Store the current values as a JSON string.
          var previous_values = JSON.stringify({
            'label': $(this).val(),
            'value': $value_field.val(),
          });
          // Put that JSON string in an attribute on the value field.
          $value_field.attr('friendly-autocomplete-dirty', previous_values);
          // Empty the value field.
          $value_field.val('');
        }
        // Otherwise, trigger the search on whatever was entered.
        else {
          $(this).trigger('input');
        }
      });
      // When the label field is blurred, if the previous values attribute
      // exists, restore those values.
      $context.find('input[friendly-autocomplete-id]').blur(function () {
        // Get the friendly autocomplete ID from the field.
        var autocomplete_id = $(this).attr('friendly-autocomplete-id');
        // If this is the value field, do nothing.
        if ($(this).attr(autocomplete_id) == 'value') {
          return;
        }
        // Get the related value field jQuery object.
        var $value_field = $context.find('input[friendly-autocomplete-id=' + autocomplete_id + '][' + autocomplete_id + '=value]');
        // If there are no values to restore, do nothing.
        if (!$value_field.attr('friendly-autocomplete-dirty')) {
          return;
        }
        // If the label field was emptied, empty the value field, too.
        if ($(this).val() == '') {
          $value_field.val('');
        }
        // Otherwise, restore the previous values.
        else {
          // Get the previous values out of the attribute.
          var previous_values = JSON.parse($value_field.attr('friendly-autocomplete-dirty'));
          // If the previous values were empty or if the previous label does not
          // match the current label, do nothing.
          if (!previous_values.value || previous_values.label !== $(this).val()) {
            return;
          }
          // Remove the dirty attribute.
          $value_field.removeAttr('friendly-autocomplete-dirty');
          // Restore the previous values to both the label and value fields.
          $(this).val(previous_values.label);
          $value_field.val(previous_values.value);
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
