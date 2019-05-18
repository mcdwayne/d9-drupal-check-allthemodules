(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.omedaCustomersSettings = {
    attach: function attach(context) {

      // Handling of user mapping field display.
      $('.omeda-field-type', context).each(function () {
        $(this).change(function () {
          handleMappingFieldType(this);
        });
        handleMappingFieldType(this);
      });

      // Handling of Omeda demographic field.
      $('.omeda-demographic-field', context).each(function () {
        $(this).change(function () {
          updateDemographicValues(this);
        });
      });

    }
  };

  function updateDemographicValues(field) {
    var $mapping_field = $(field).closest('.mapping-field');
    if ($mapping_field.find('.omeda-field-type').val() === 'demographic') {
      var values = drupalSettings.omeda.demographic_values[$(field).val()];
      var values_converted = '';
      $.each(values, function (index, value) {
        values_converted += index + (value ? ' (' + value + ')' : '') + '<br />';
      });
      var $desc_field = $mapping_field.find('.omeda-demographic-values').closest('.form-item').find('.description');
      $desc_field.html(Drupal.t('Enter one value per line, in the pipe-separated format <em>Omeda Demographic field value</em>|<em>Drupal user field value</em>.') + '<br />' + Drupal.t('Possible demographic values:') + '<br />' + values_converted);
    }
  }

  function handleMappingFieldType(field) {
    var $mapping_field = $(field).closest('.mapping-field');
    var field_type = $(field).val();
    if (field_type === 'base') {
      // base field
      $mapping_field.find('.omeda-field').closest('.form-item').show();
      $mapping_field.find('.omeda-contact-type').closest('.form-item').hide();
      $mapping_field.find('.omeda-demographic-values').closest('.form-item').hide();
      $mapping_field.find('.omeda-demographic-field').closest('.form-item').hide();
    }
    else if (field_type === 'demographic') {
      // demographic field
      $mapping_field.find('.omeda-field').closest('.form-item').hide().find('input').val('');
      $mapping_field.find('.omeda-contact-type').closest('.form-item').hide();
      $mapping_field.find('.omeda-demographic-values').closest('.form-item').show();
      $mapping_field.find('.omeda-demographic-field').closest('.form-item').show();
      updateDemographicValues($mapping_field.find('.omeda-demographic-field'));
    }
    else {
      // contact field
      $mapping_field.find('.omeda-field').closest('.form-item').hide().find('input').val('');

      // Different contact types apply to different fields based on the first
      // number of the type.
      // @see https://jira.omeda.com/wiki/en/Standard_API_Constants_and_Codes
      var available_contact_prefixes = {address: '1', phone: '2', email: '3'};
      var contact_prefix_number = available_contact_prefixes[field_type];
      var options = '';
      $.each(drupalSettings.omeda.contact_types, function (value, label) {
        if (value.charAt(0) === contact_prefix_number) {
          if (value === $mapping_field.find('.omeda-contact-type').closest('.form-item').show().find('select').val()) {
            options += '<option selected="selected" value="' + value + '">' + label + '</option>';
          }
          else {
            options += '<option value="' + value + '">' + label + '</option>';
          }
        }
      });

      $mapping_field.find('.omeda-contact-type').closest('.form-item').show().find('select').html(options);
      $mapping_field.find('.omeda-demographic-values').closest('.form-item').hide();
      $mapping_field.find('.omeda-demographic-field').closest('.form-item').hide();
    }
  }

})(jQuery, Drupal, drupalSettings);
