(function ($) {
  'use strict';
  Drupal.behaviors.custom_voice_search = {
    attach: function (context, settings) {
      $('.voice-search-block').parent('.field-suffix').css('display', 'inline-block');
      $(drupalSettings.custom_voice_search.custom_voice_search.ids).each(function (index, value) {
        var str = value.id;
        var res = str.replace(/_/g, '-');
        $('.' + res + ' .voice-search-block').click(function () {
          if (window.hasOwnProperty('webkitSpeechRecognition')) {
            var recognition = new webkitSpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-US';
            recognition.start();
            recognition.onstart = function () {
            };
            recognition.onend = function () {
            };
            recognition.onresult = function (e) {
              $('.' + res + ' #' + value.input_id).val(e.results[0][0].transcript);
              recognition.stop();
            };
          }
        });
      });
    }
  };
})(jQuery);
