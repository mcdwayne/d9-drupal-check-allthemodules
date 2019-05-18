(function ($) {
  /**
   * Address autocomplete, powered by Loqate.
   *
   * @type {{attach: Drupal.behaviors.addressAutocomplete.attach}}
   */
  Drupal.behaviors.addressAutocomplete = {
    attach: function (context, settings) {
      var loqateOptions = settings.loqate.loqate;
      // Check the loqate options have been defined before continuing.
      if (typeof loqateOptions !== 'object') {
        return;
      }

      var initialClass = 'address-lookup--initial';
      var $addressLookup = $('.address-lookup', context);
      var fieldHideClass = 'address-lookup__field--hidden';

      $addressLookup.each(function () {
        var $lookupFields = $(this);
        var dataKey = $lookupFields.data('key');
        var $provinceField = $lookupFields.find('[name="' + dataKey + '[state_province]"]');
        var $regionField = $lookupFields.find('[name="' + dataKey + '[region]"]');
        var provinceType = $provinceField.data('option-type') === 'state_province_codes' ? 'Province' : 'ProvinceName';

        // Initial setup for Loqate.
        var loqateFields = [
          { element: 'search', field: '' },
          { element: dataKey + '[address]', field: 'Line1' },
          { element: dataKey + '[address_2]', field: 'Line2', mode: pca.fieldMode.POPULATE },
          { element: dataKey + '[city]', field: 'City', mode: pca.fieldMode.POPULATE },
          { element: dataKey + '[region]', field: 'ProvinceName', mode: pca.fieldMode.POPULATE },
          { element: dataKey + '[state_province]', field: provinceType, mode: pca.fieldMode.POPULATE },
          { element: dataKey + '[postal_code]', field: 'PostalCode' },
          { element: dataKey + '[country]', field: 'CountryName', mode: pca.fieldMode.COUNTRY }
        ];

        var control = new pca.Address(loqateFields, loqateOptions);
        // Listener for when an address is populated.
        control.listen('populate', function (address) {
          if (address.ProvinceName !== '' && $provinceField.find('option[value="' + address[provinceType] + '"]').length > 0) {
            // ProvinceName exists in the State/Province drop-down.
            $provinceField.closest('.address-lookup__field').show().removeClass(fieldHideClass);
            $regionField.closest('.address-lookup__field').hide().addClass(fieldHideClass);
          }
          else {
            // ProvinceName does not exist in the State/Province
            // drop-down, so use the region field instead.
            $provinceField.closest('.address-lookup__field').hide().addClass(fieldHideClass);
            $regionField.closest('.address-lookup__field').show().removeClass(fieldHideClass);
          }

          $lookupFields.removeClass(initialClass);
        });
      });
    }
  };
})(jQuery);
