
(function ($, Drupal) {

  'use strict';

  $(document).ready(function () {

    $('.captcha-keypad-keypad-used').val('');

    $('.captcha-keypad-input').val('').keyup(function () {
      var wrapper = $(this).parent().parent().parent().parent();
      var textfield = wrapper.find('.captcha-keypad-input');
      var message = wrapper.find('.message');
      textfield.val('');
      message.css('color', 'red');
      message.html(Drupal.t('Use keypad ->'));
    });

    $('.form-item-captcha-keypad-input').append(
      '<span class="clear">' +
      Drupal.t('Clear') +
      '</span><br/>' +
      '<span class="message"></span>'
    );

    $('.form-item-captcha-keypad-input .clear').click(function () {
      var wrapper = $(this).parent().parent().parent().parent();
      var textfield = wrapper.find('.captcha-keypad-input');
      var message = wrapper.find('.message');
      textfield.val('');
      message.html('');
    });

    $('.captcha-keypad .inner span').click(function () {
      var wrapper = $(this).parent().parent().parent().parent();
      var textfield = wrapper.find('.captcha-keypad-input');
      var keypadused = wrapper.find('.captcha-keypad-keypad-used');
      var message = wrapper.find('.message');
      var value = textfield.val();
      textfield.val(value + $(this).text());
      keypadused.val('Yes');
      message.html('');
    });
  });

})(jQuery, Drupal);
