(function ($) {
  Drupal.behaviors.AddressAlgoliaAutocomplete = {
    attach: function (context, settings) {

      // Only interfere if we have a field name exposed from the backend.
      if (drupalSettings.address_algolia) {
        field_name = drupalSettings.address_algolia.field_name;
        var placesAutocomplete = places({
          container: document.querySelector('#edit-' + field_name + '-0-address-line1'),
          type: 'address',
          templates: {
            value: function(suggestion) {
              return suggestion.name;
            }
          }
        });
        placesAutocomplete.on('change', function resultSelected(e) {
          document.querySelector('#edit-' + field_name + '-0-address-line2').value = e.suggestion.administrative || '';
          document.querySelector('#edit-' + field_name + '-0-locality').value = e.suggestion.city || '';
          document.querySelector('#edit-' + field_name + '-0-postal-code').value = e.suggestion.postcode || '';
        });
      }

    }
  };
})(jQuery);
