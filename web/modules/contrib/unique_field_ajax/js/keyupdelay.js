(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.unique_field_ajax = {
    attach: function (context, settings) {
      $.each(drupalSettings.unique_field_ajax, function (index, data) {
        var input_selector = data.id;
        var typingTimer;
        var doneTypingInterval = 1100;
        $(input_selector).on('keyup', function (e) {
          clearTimeout(typingTimer);
          if ($(this).val) {
            var trigid = $(this);
            typingTimer = setTimeout(function () {
              trigid.triggerHandler('finishedinput');
            }, doneTypingInterval);
          }
        });
        var last_tape = 0;
        if ($(input_selector).val) {
          last_tape = $(input_selector).val().length;
        }
        if (/chrom(e|ium)/.test(navigator.userAgent.toLowerCase())) {
          if ($(input_selector).attr('type') !== 'email') {
            $(input_selector).focus(function () {
              $(this)[0].setSelectionRange(last_tape, last_tape);
            });
          }
        }
        else {
          $(input_selector).focus(function () {
            $(this)[0].setSelectionRange(last_tape, last_tape);
          });
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
