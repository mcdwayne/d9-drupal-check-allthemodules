(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.bringLookup = {
    attach: function (context, settings) {
        var invalidInput = Drupal.t('Invalid postcode.');
        var selectors = drupalSettings.bring_postal_code.selectors;

        $.each(selectors, function(id , data) {
          var $input = $('body ' + data.input);
          $input.on('keyup', function () {
            var $output = $(data.output);
            var $country = $(data.country);
            // Override country value if selector present.
            var foundCountryValue = $country.val();
            var currentCountry = drupalSettings.bring_postal_code.country;
            if (typeof foundCountryValue == 'string' && foundCountryValue.length > 1 ) {
              currentCountry = foundCountryValue;
            }
            var params = {
              'country': currentCountry,
              'clientUrl': window.location.host
            };
            var val = $input.val();
            if (val.length >= drupalSettings.bring_postal_code.triggerLength) {
              params['pnr'] = val;
              var APIurl = drupalSettings.bring_postal_code.clientUrl;
              $.ajax({
                url: APIurl + $.param(params),
                dataType: 'jsonp',
                cache: true,
                success: function (data, textStatus, jqXHR) {
                  if (data.valid) {
                    $output.val(data.result);
                  } else {
                    $output.val(invalidInput);
                  }
                }
              });
            }
          });
        });
    }
  };

})(jQuery, Drupal, drupalSettings)