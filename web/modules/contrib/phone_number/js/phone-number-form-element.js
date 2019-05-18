/**
 * @file
 */

(function ($) {
  'use strict';
  Drupal.behaviors.phoneNumberFormElement = {
    attach: function (context, settings) {
      $('.phone-number-field .country', context).once('field-setup').each(function () {
        var $input = $(this);
        var val = $input.val();
        $input.data('value', val);
        $input.wrap('<div class="country-select"></div>').before('<div class="phone-number-flag"></div><span class="arrow"></span><div class="prefix"></div>');
        setCountry(val);
        $input.change(function (e) {
          if (val !== $(this).val()) {
            val = $(this).val();
          }

          setCountry(val);
        });

        function setCountry(country) {
          $input.parents('.country-select').find('.phone-number-flag').removeClass($input.data('value'));
          $input.parents('.country-select').find('.phone-number-flag').addClass(country.toLowerCase());
          $input.data('value', country.toLowerCase());

          var options = $input.get(0).options;
          for (var i = 0; i < options.length; i++) {
            if (options[i].value === country) {
              var prefix = options[i].label.match(/(\d+)/)[0];
              $input.parents('.country-select').find('.prefix').text('(+' + prefix + ')');
            }
          }
        }
      });
    }
  };
})(jQuery);
