/**
 * @file
 * Javascript for Random Passcode Field Widget.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.custom_passcode_field = {
    attach: function (context, settings) {
      $('.passcode_generate_btn').off().on('click', function (e) {
        e.preventDefault();
        if (confirm('Are you sure of regenerating the passcode ?')) {
          var code = alphanumeric_unique();
          $(this).parent().find('.passcode_random_number').val(code);
        }
        return false;
      });
    }
  };

  function alphanumeric_unique() {
    var digits = drupalSettings.passcode_field.digits;
    var str = 'abcdefghijklmnopqrstuvwxyz0123456789.-+=_,!@$#*%<>[]{}';
    var chars = '';
    if (digits === '' || digits === null) {
      digits = 6;
    }
    for (var i = 0; i < digits; i++) {
      chars += str.charAt(Math.floor(Math.random() * str.length));
    }
    chars = shuffle(chars);
    return chars;
  }

  function shuffle(chars) {
    var array = chars.split('');
    var tmp = '';
    var current = '';
    var top = array.length;
    if (top) {
      while (--top) {
        current = Math.floor(Math.random() * (top + 1));
        tmp = array[current];
        array[current] = array[top];
        array[top] = tmp;
      }
    }
    return array.join('');
  }
})(jQuery);
