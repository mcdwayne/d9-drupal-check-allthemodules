/**
 * @file
 */

(function ($) {
  'use strict';
  Drupal.behaviors.smsPhoneNumberFormElement = {
    attach: function (context, settings) {
      $('.sms-phone-number-field .local-number', context).once('field-setup').each(function () {
        var $input = $(this);
        var val = $input.val();
        $input.keyup(function (e) {
          if (val !== $(this).val()) {
            val = $(this).val();
            $input.parents('.sms-phone-number-field').find('.send-button').addClass('show');
            $input.parents('.sms-phone-number-field').find('.verified').addClass('hide');
          }
        });
      });

      $('.sms-phone-number-field .country', context).once('field-setup').each(function () {
        var val = $(this).val();
        $(this).change(function (e) {
          if (val !== $(this).val()) {
            val = $(this).val();
            $input.parents('.sms-phone-number-field').find('.send-button').addClass('show');
            $input.parents('.sms-phone-number-field').find('.verified').addClass('hide');
          }
        });
      });
      $('.sms-phone-number-field .send-button', context).once('field-setup').click(function () {
        var $button = $(this);
        $button.parent().find('[type="hidden"]').val('');
      });

      if (settings['smsPhoneNumberVerificationPrompt']) {
        $('#' + settings['smsPhoneNumberVerificationPrompt'] + ' .verification').addClass('show');
        $('#' + settings['smsPhoneNumberVerificationPrompt'] + ' .verification input[type="text"]').val('');
      }

      if (settings['smsPhoneNumberHideVerificationPrompt']) {
        $('#' + settings['smsPhoneNumberHideVerificationPrompt'] + ' .verification').removeClass('show');
      }

      if (settings['smsPhoneNumberVerified']) {
        $('#' + settings['smsPhoneNumberVerified'] + ' .send-button').removeClass('show');
        $('#' + settings['smsPhoneNumberVerified'] + ' .verified').addClass('show');
      }
    }
  };
})(jQuery);
