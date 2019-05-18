/**
 * @file
 * Block settings summary behaviour.
 */

(function ($) {
  /**
   * Initiate Autocomplete js
   */
  Drupal.behaviors.addressAutocomplete = {
    attach: function (context, settings) {

      $('.address-autocomplete-input', context).once('initiate-autocomplete').each(function(){

        // The element to work with
        var autocompleteField = $(this);

        // Load Google Maps Javascript API library and ensure it's only loaded once
        if(!drupalSettings.addressAutocomplete.apiLoaded) {
          $.getScript('https://maps.googleapis.com/maps/api/js?key='+drupalSettings.addressAutocomplete.apiKey+'&libraries=places', function(){
            drupalSettings.addressAutocomplete.apiLoaded = true;
            initiateAutocomplete();
          });
        } else {
          initiateAutocomplete();
        }

        // Initiate Google Maps Autocomplete input
        function initiateAutocomplete() {

          var placeSearch,
          autocomplete,
          componentForm = {
            street_number: 'short_name',
            route: 'long_name',
            neighborhood: 'short_name',
            locality: 'long_name',
            administrative_area_level_1: 'short_name',
            country: 'short_name',
            postal_code: 'short_name'
          },
          address = {
            street_number: '.street-number',
            route: '.street',
            route_2: '.street2',
            sublocality_level_1: '.sublocality-level1',
            locality: '.city',
            administrative_area_level_1: '.state',
            country: '.country',
            postal_code: '.zip'
            // given_name: '.given-name',
            // family_name: '.family-name',
            // organization: '.organization'
          };

          autocomplete = new google.maps.places.Autocomplete(
              /** @type {!HTMLInputElement} */(autocompleteField[0]),
              {types: ['geocode']});

          // Resolve conflict when allowed countries is an object not an array
          var allowedCountries = ($.isArray(drupalSettings.addressAutocomplete.availableCountries) ? drupalSettings.addressAutocomplete.availableCountries : Object.values(drupalSettings.addressAutocomplete.availableCountries));
          // Set restrict to the list of available countries.
          if (allowedCountries.length) {
            autocomplete.setComponentRestrictions(
                {'country': allowedCountries});
          }
          autocomplete.addListener('place_changed', fillInAddress);

          if (location.protocol == 'https:' && navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
              var geolocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
              };
              var circle = new google.maps.Circle({
                center: geolocation,
                radius: position.coords.accuracy
              });
              autocomplete.setBounds(circle.getBounds());
            });
          }

          // Get wrapper
          var wrapper = autocompleteField.closest('.address-autocomplete-wrapper');
          wrapper.find('input.address-autocomplete-input').removeClass('address-autocomplete-component--hidden');
          // Hide all other address fields.
          for (var component in address) {
            var addressField = wrapper.find(address[component]);
            if (addressField.length) {
              wrapper.find('label[for="'+addressField.attr('id')+'"]').hide();
              addressField.hide();
            }
          }

          // TODO: Create apartment number field

          function fillInAddress() {
            // Fill initial address fields
            var place = autocomplete.getPlace();
            var country = wrapper.find('select.country').val();
            if (place && place.address_components) {
              // Get each component of the address from the place details
              // and fill the corresponding field on the form.
              for (var i = 0; i < place.address_components.length; i++) {
                var addressType = place.address_components[i].types[0];
                var gField = wrapper.find('input[data-name="g-'+ addressType.replace(/_/g, '-') +'"]');
                gField.val('');
                if (componentForm[addressType]) {
                  var value = place.address_components[i][componentForm[addressType]];
                  // The place.name == "StreetName StreetNumber".
                  value = addressType == 'route' ? place.name : value;
                  gField.val(value);
                  if (value.length) {
                    if (addressType == 'country') {
                      country = value;
                    }
                    else {
                      wrapper.find(address[addressType]).val(value);
                    }
                  }
                }
              }
            }
            // Initiates the ajax event providing appropriate address
            // components for a country chosen with Google Places API.
            wrapper.find('select.country').val(country).change();
          }

        }

      });
    }
  };
}(jQuery));
