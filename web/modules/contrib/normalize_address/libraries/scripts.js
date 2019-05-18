(function (Drupal, $, window) {

  // To understand behaviors, see https://www.drupal.org/node/2269515
  Drupal.behaviors.normalize_address = {
    attach: function (context, settings) {

      var apiKey = drupalSettings.normalize_address.normalize_address.api_key;
      var countryСode = drupalSettings.normalize_address.normalize_address.country_code;

      if (apiKey.length < 2 || countryСode.length < 2) {
        $('.form-item-field-property-address-0-normalized-address-full')
          .append('<div class="google-cant-recognize error" style="color: red;">You need to add Google API key and Country Code to configuration form. Please visit <a href="/admin/config/services/normalize_address">this page</a></div>');
      }

      $('#edit-field-property-address-0-normalized-address-full')
        .focusin(function () {
          $('.google-cant-recognize').remove();
        });

      $('#edit-field-property-address-0-normalized-address-full')
        .focusout(function () {

          if($(this).val().length > 5) {
            var full_address = $(this).val().replace(',', '+');

            $.ajax({
              type: "GET",
              url: 'https://maps.googleapis.com/maps/api/geocode/json?address=' + full_address + '+' + countryСode + ',&key=' + apiKey,
              success: function (data) {
                if (data['status'] == 'OK') {

                  var route = '';
                  var street_number = '';
                  var locality = '';
                  var administrative_area_level_1 = '';
                  var country = ' CA';
                  var postal_code = '';

                  $.each(data['results'][0]['address_components'], function (index, object) {

                    var name = object.types[0];

                    if (name == 'route') {
                      route = object.long_name;
                      $("#edit-field-property-address-0-normalized-address-street-address")
                        .val(route);
                    }
                    if (name == 'street_number') {
                      street_number = object.long_name
                      $("#edit-field-property-address-0-normalized-address-building-number")
                        .val(street_number);
                    }
                    if (name == 'locality') {
                      locality = object.long_name;
                      $("#edit-field-property-address-0-normalized-address-city")
                        .val(locality);
                    }
                    if (name == 'administrative_area_level_1') {
                      administrative_area_level_1 = object.short_name;
                      $("#edit-field-property-address-0-normalized-address-province")
                        .val(administrative_area_level_1);
                    }
                    if (name == 'country') {
                      country = object.short_name;
                    }
                    if (name == 'postal_code') {
                      postal_code = object.long_name;
                      $("#edit-field-property-address-0-normalized-address-postal-code")
                        .val(postal_code);
                    }
                  });

                  $('#edit-field-property-address-0-normalized-address-full')
                    .val(data['results'][0]['formatted_address']);

                  $('#edit-field-property-address-0-normalized-address-lattitude')
                    .val(data['results'][0]['geometry']['location']['lat']);

                  $('#edit-field-property-address-0-normalized-address-longtitude')
                    .val(data['results'][0]['geometry']['location']['lng']);
                }
                else {
                  $('.form-item-field-property-address-0-normalized-address-full')
                    .append('<div class="google-cant-recognize error" style="color: red;">Google can\'t recognize this address. Please try again.</div>');
                  $("#edit-field-property-address-0-normalized-address-street-address")
                    .val('');
                  $("#edit-field-property-address-0-normalized-address-building-number")
                    .val('');
                  $("#edit-field-property-address-0-normalized-address-city")
                    .val('');
                  $("#edit-field-property-address-0-normalized-address-province")
                    .val('');
                  $("#edit-field-property-address-0-normalized-address-postal-code")
                    .val('');
                }
              },
            });
          }

        });

    }
  };

}(Drupal, jQuery, this));
