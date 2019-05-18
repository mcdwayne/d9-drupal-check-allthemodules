(function ($, Drupal) {

  Drupal.address_usps = (Drupal.address_usps || {});
  Drupal.address_usps.element_ignore_fields = '[type="submit"], [name*="given_name"], [name*="family_name"], [name*="organization"], .usps-ignore';

  /**
   * Checks if single address element have all filled fields.
   *
   * @param $element
   *  jQuery element.
   * @returns {boolean}
   */
  Drupal.address_usps.elementIsValid = function ($element) {
    var inputFields = $element.find(':input')
      .not(Drupal.address_usps.element_ignore_fields);
    var validFieldsCount = 0;
    $.each(inputFields, function (key, value) {
      if ($(value)[0].validity.valid === true) {
        validFieldsCount++;
      }
    });
    return validFieldsCount === inputFields.length;
  };

  Drupal.address_usps.fillElementWithValues = function ($element, values) {
    $element.find(':input').removeClass('error');
    $element.find('.messages.messages--error').remove();

    // Reset values on element's form excluding ignored fields.
    $element.find(':input')
      .not(Drupal.address_usps.element_ignore_fields)
      .val('');

    $.each(values, function (key, value) {
      $element.find('[name*="' + key + ']"]').val(value);
    });
  };


  /**
   * Just refill element with suggested values.
   */
  Drupal.AjaxCommands.prototype.addressUSPSSuggest = function (ajax, response, status) {
    Drupal.address_usps.fillElementWithValues($(response.selector), response.suggested_data);
  };


  /**
   * Prompt and refill element with suggested values.
   */
  Drupal.AjaxCommands.prototype.addressUSPSSuggestConfirm = function (ajax, response, status) {
    // If address is valid as it is then no need to show popup.
    if (response.replace_required === true) {
      var modal = $("#drupal-modal");
      modal.html(response.dialog_content);
      Drupal.dialog(modal, {
        modal: true,
        resizable: false,
        width: "30%",
        maxWidth: "80%",
        draggable: false,
        title: response.dialog_title,
        buttons: {
          "Convert": function () {
            Drupal.address_usps.fillElementWithValues($(response.selector), response.suggested_data);
            $(this).dialog('close');
          },
          "Cancel": function () {
            $(this).dialog("close");
          }
        }
      }).showModal();
    }
  };

  /**
   * Automatically fire AJAX on address_usps element change.
   */
  Drupal.behaviors.address_usps = {
    attach: function (context, drupalSettings) {
      var elements = (drupalSettings.address_usps || {}).elements || {};

      // Fire AJAX after any element input change if all mandatory element
      // fields was filled.
      $.each(elements, function (key, value) {
        var element = $(value, context);
        var inputFields = element.find(':input')
          .not(Drupal.address_usps.element_ignore_fields);

        inputFields.on('change', function (e) {
          if (Drupal.address_usps.elementIsValid(element)) {
            element.find('.address-usps-convert-button').click();
          }
        });

      });
    }
  };
})(jQuery, Drupal);
