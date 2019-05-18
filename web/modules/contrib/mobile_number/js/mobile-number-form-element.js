/**
 * @file
 */

(function ($) {
  'use strict';
  Drupal.behaviors.mobileNumberFormElement = {
    attach: function (context, settings) {
      $('.mobile-number-field .local-number', context).once('field-setup').each(function () {
        var $input = $(this);
        var val = $input.val();
        $input.keyup(function (e) {
          if (val !== $(this).val()) {
            val = $(this).val();
            $input.parents('.mobile-number-field').find('.send-button').addClass('show');
            $input.parents('.mobile-number-field').find('.verified').addClass('hide');
          }
        });
      });

      $('.mobile-number-field .country', context).once('field-setup').each(function () {
        var $input = $(this);
        var val = $input.val();
        $input.data('value', val);
        $input.wrap('<div class="country-select"></div>').before('<div class="mobile-number-flag"></div><span class="arrow"></span><div class="prefix"></div>');
        setCountry(val);
        $input.change(function (e) {
          if (val !== $(this).val()) {
            val = $(this).val();
            $input.parents('.mobile-number-field').find('.send-button').addClass('show');
            $input.parents('.mobile-number-field').find('.verified').addClass('hide');
          }

          setCountry(val);
        });

        function setCountry(country) {
          $input.parents('.country-select').find('.mobile-number-flag').removeClass($input.data('value'));
          $input.parents('.country-select').find('.mobile-number-flag').addClass(country.toLowerCase());
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
      $('.mobile-number-field .send-button', context).once('field-setup').click(function () {
        var $button = $(this);
        $button.parent().find('[type="hidden"]').val('');
      });

      if (settings['mobileNumberVerificationPrompt']) {
        $('#' + settings['mobileNumberVerificationPrompt'] + ' .verification').addClass('show');
        $('#' + settings['mobileNumberVerificationPrompt'] + ' .verification input[type="text"]').val('');
      }

      if (settings['mobileNumberHideVerificationPrompt']) {
        $('#' + settings['mobileNumberHideVerificationPrompt'] + ' .verification').removeClass('show');
      }

      if (settings['mobileNumberVerified']) {
        $('#' + settings['mobileNumberVerified'] + ' .send-button').removeClass('show');
        $('#' + settings['mobileNumberVerified'] + ' .verified').addClass('show');
      }
    }
  };
})(jQuery);
