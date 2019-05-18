/**
 * @file
 * It Generates qrcode for faircoin addresses when the field is displayed.
 *
 * It uses jquery.qrcode library.
 */

(function ($) {

  "use strict";

  Drupal.behaviors.faircoin_address_field = {
    attach: function (context, settings) {
      $('code.faircoin-address-qrcode-text', context).each(function (index) {
        var text = $(this).text();
        $(this).siblings('div.faircoin-address-qrcode-wrapper').children('div.faircoin-address-qrcode-image').addClass('div-qrcode-hidden').qrcode({
          width: 100,
          height: 100,
          text: 'faircoin:' + text
        });
      });
      $('div.faircoin-address-qrcode-icon', context).click(function () {
        var div_qrcode = $(this).siblings('div.faircoin-address-qrcode-wrapper').children('div.faircoin-address-qrcode-image');
        if (div_qrcode.hasClass('div-qrcode-visible')) {
          div_qrcode.removeClass('div-qrcode-visible').addClass('div-qrcode-hidden');
        }
        else {
          $('div.faircoin-address-qrcode-image').removeClass('div-qrcode-visible').addClass('div-qrcode-hidden');
          div_qrcode.removeClass('div-qrcode-hidden').addClass('div-qrcode-visible');
        }
      });
      $(document).click(function (event) {
        if (!$(event.target).closest('div.faircoin-address-qrcode-icon').length) {
          $('div.faircoin-address-qrcode-image').removeClass('div-qrcode-visible').addClass('div-qrcode-hidden');
        }
      });
    }
  };
})(jQuery);
